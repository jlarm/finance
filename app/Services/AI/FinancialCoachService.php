<?php

namespace App\Services\AI;

use App\AI\Prompts\InvalidPromptResponseException;
use App\AI\Prompts\Prompt;
use App\Models\AiInsight;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

/**
 * Orchestrates the full AI insight pipeline:
 * summary build → prompt build → AI SDK call → validate → persist.
 *
 * Controllers and jobs only touch this service.
 * The AI SDK is isolated behind callAiSdk() so swapping SDK versions
 * or providers is a one-method change.
 */
class FinancialCoachService
{
    /** How long a (user, kind, period) pair stays locked after generation. */
    private const GENERATE_LOCK_HOURS = 24;

    /** Minimum gap between manual regenerations of the same kind. */
    private const REGENERATE_LOCK_MINUTES = 60;

    public function __construct(
        private FinancialDataSummaryBuilder $summaries,
        private InsightPromptBuilder $prompts,
    ) {}

    public function generateMonthlySummary(User $user, ?Carbon $month = null): ?AiInsight
    {
        $month = ($month ?? today()->copy()->subMonthNoOverflow())->startOfMonth();

        return $this->generate(
            user: $user,
            kind: 'monthly_summary',
            context: $this->summaries->forMonthly($user, $month),
            period: $month,
        );
    }

    public function generateSpendingInsights(User $user): ?AiInsight
    {
        return $this->generate(
            user: $user,
            kind: 'spending_pattern',
            context: $this->summaries->forSpendingPattern($user),
        );
    }

    public function generateCashFlowRisk(User $user): ?AiInsight
    {
        return $this->generate(
            user: $user,
            kind: 'cashflow_risk',
            context: $this->summaries->forCashFlowRisk($user),
        );
    }

    public function generateDebtCoaching(User $user, float $extraPayment = 0.0): ?AiInsight
    {
        return $this->generate(
            user: $user,
            kind: 'debt_coaching',
            context: $this->summaries->forDebtCoaching($user, $extraPayment),
        );
    }

    public function generateSavingsPacing(User $user): ?AiInsight
    {
        return $this->generate(
            user: $user,
            kind: 'savings_pacing',
            context: $this->summaries->forSavingsPacing($user),
        );
    }

    /**
     * Re-run an existing insight. Dismisses the old record and creates a new one.
     */
    public function regenerate(AiInsight $insight): ?AiInsight
    {
        $user = $insight->user;

        if ($user === null || ! $this->prompts->supports($insight->kind)) {
            return null;
        }

        $regenKey = $this->regenerateLockKey($user, $insight->kind);

        if (Cache::has($regenKey)) {
            return null;
        }

        $context = match ($insight->kind) {
            'monthly_summary' => $this->summaries->forMonthly(
                $user,
                $insight->generated_for_period ?? today()->copy()->subMonthNoOverflow()->startOfMonth()
            ),
            'spending_pattern' => $this->summaries->forSpendingPattern($user),
            'cashflow_risk' => $this->summaries->forCashFlowRisk($user),
            'debt_coaching' => $this->summaries->forDebtCoaching($user),
            'savings_pacing' => $this->summaries->forSavingsPacing($user),
            default => null,
        };

        if ($context === null) {
            return null;
        }

        $period = $insight->kind === 'monthly_summary' ? $insight->generated_for_period : null;

        $fresh = $this->generate(
            user: $user,
            kind: $insight->kind,
            context: $context,
            period: $period,
            bypassThrottle: true,
        );

        if ($fresh !== null) {
            $insight->update(['status' => 'dismissed']);
            Cache::put($regenKey, true, now()->addMinutes(self::REGENERATE_LOCK_MINUTES));
        }

        return $fresh;
    }

    public function canGenerate(User $user, string $kind, ?Carbon $period = null): bool
    {
        if (! $user->financeSettings?->ai_enabled) {
            return false;
        }

        return ! Cache::has($this->generateLockKey($user, $kind, $period));
    }

    /**
     * Shared pipeline step used by every generate* method.
     *
     * @param  array<string, mixed>  $context
     */
    private function generate(
        User $user,
        string $kind,
        array $context,
        ?Carbon $period = null,
        bool $bypassThrottle = false,
    ): ?AiInsight {
        if (! $user->financeSettings?->ai_enabled) {
            return null;
        }

        if (! $bypassThrottle && ! $this->canGenerate($user, $kind, $period)) {
            return null;
        }

        $prompt = $this->prompts->for($kind);

        try {
            $raw = $this->callAiSdk($prompt, $context);
            $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);

            if (! is_array($decoded)) {
                throw new InvalidPromptResponseException('Model returned non-object JSON.');
            }

            $payload = $prompt->validate($decoded);
        } catch (InvalidPromptResponseException|JsonException $e) {
            $this->logFailure($user, $kind, 'invalid_response', $e);

            return null;
        } catch (Throwable $e) {
            $this->logFailure($user, $kind, 'sdk_error', $e);

            return null;
        }

        $insight = AiInsight::create([
            'user_id' => $user->id,
            'kind' => $payload['kind'] ?? $kind,
            'severity' => $payload['severity'] ?? 'info',
            'title' => $payload['title'] ?? null,
            'body' => $payload['body'] ?? '',
            'data' => [
                'referenced_values' => $payload['referenced_values'] ?? [],
                'context' => $context,
            ],
            'status' => 'new',
            'generated_for_period' => $period?->toDateString(),
        ]);

        Cache::put(
            $this->generateLockKey($user, $kind, $period),
            true,
            now()->addHours(self::GENERATE_LOCK_HOURS),
        );

        return $insight;
    }

    /**
     * ADAPTER: the ONLY method in this codebase that calls the Laravel AI SDK.
     *
     * SDK APIs vary across versions; if laravel/ai changes its fluent shape,
     * this is the single place to adjust. Returns the raw JSON string from
     * the model — do not parse here.
     *
     * The fluent chain below matches laravel/ai v0 conventions. Adapt the
     * method names ( ->using / ->withMessages / ->withResponseFormat ) to
     * the exact signatures exposed by your installed version.
     *
     * @param  array<string, mixed>  $context
     */
    protected function callAiSdk(Prompt $prompt, array $context): string
    {
        // The actual facade is imported lazily so the rest of this service
        // stays testable without the SDK installed in the test environment.
        /** @var mixed $ai */
        $ai = app('laravel-ai');

        $response = $ai->text()
            ->using($prompt->model())
            ->withMessages($prompt->buildMessages($context))
            ->withResponseFormat($prompt->schema())
            ->withMaxTokens($prompt->maxTokens())
            ->withTemperature($prompt->temperature())
            ->generate();

        return (string) $response->text();
    }

    private function generateLockKey(User $user, string $kind, ?Carbon $period): string
    {
        $periodKey = $period?->format('Y-m') ?? 'current';

        return "ai:coach:{$user->id}:{$kind}:{$periodKey}";
    }

    private function regenerateLockKey(User $user, string $kind): string
    {
        return "ai:coach:regen:{$user->id}:{$kind}";
    }

    private function logFailure(User $user, string $kind, string $reason, Throwable $e): void
    {
        Log::warning('AI insight generation failed', [
            'user_id' => $user->id,
            'kind' => $kind,
            'reason' => $reason,
            'message' => $e->getMessage(),
        ]);
    }
}
