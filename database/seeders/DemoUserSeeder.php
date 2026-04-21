<?php

namespace Database\Seeders;

use App\Models\AiInsight;
use App\Models\Bill;
use App\Models\BudgetTarget;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\IncomeSource;
use App\Models\SavingsGoal;
use App\Models\User;
use App\Models\UserFinanceSetting;
use Carbon\CarbonInterface;
use Database\Factories\AiInsightFactory;
use Database\Factories\BillFactory;
use Database\Factories\ExpenseCategoryFactory;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Creates a single coherent demo user with enough data to exercise
 * every dashboard, report, and insight view. Idempotent — rerunning
 * `db:seed --class=DemoUserSeeder` updates the existing demo user.
 */
class DemoUserSeeder extends Seeder
{
    private const DEMO_EMAIL = 'demo@example.com';

    private const HISTORY_MONTHS = 6;

    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => self::DEMO_EMAIL],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        UserFinanceSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'currency' => 'USD',
                'locale' => 'en-US',
                'monthly_cycle_start_day' => 1,
                'debt_strategy' => 'avalanche',
                'ai_tone' => 'supportive',
                'ai_enabled' => true,
                'timezone' => 'America/Chicago',
            ],
        );

        // Start clean so the seeder stays idempotent.
        $user->expenses()->delete();
        $user->bills()->delete();
        $user->debts()->delete();
        $user->incomeSources()->delete();
        $user->savingsGoals()->delete();
        $user->budgetTargets()->delete();
        $user->aiInsights()->delete();
        $user->expenseCategories()->delete();

        $categories = $this->seedCategories($user);
        $this->seedIncome($user);
        $this->seedBills($user, $categories);
        $this->seedDebts($user);
        $this->seedSavingsGoals($user);
        $this->seedExpenses($user, $categories);
        $this->seedBudgetTargets($user, $categories);
        $this->seedInsights($user);

        $this->command?->info(sprintf(
            'Demo user ready: %s (password: password)',
            self::DEMO_EMAIL,
        ));
    }

    /**
     * @return array<string, ExpenseCategory>
     */
    private function seedCategories(User $user): array
    {
        $categories = [];

        foreach (ExpenseCategoryFactory::DEFAULTS as $row) {
            $categories[$row['name']] = ExpenseCategory::create([
                'user_id' => $user->id,
                'name' => $row['name'],
                'color' => $row['color'],
                'is_archived' => false,
            ]);
        }

        return $categories;
    }

    private function seedIncome(User $user): void
    {
        // Two paychecks a month for six months, plus an occasional freelance invoice.
        for ($i = self::HISTORY_MONTHS - 1; $i >= 0; $i--) {
            $month = today()->copy()->subMonthsNoOverflow($i)->startOfMonth();

            IncomeSource::factory()
                ->for($user)
                ->paycheck()
                ->create(['received_on' => $month->copy()->day(1)->toDateString()]);

            IncomeSource::factory()
                ->for($user)
                ->paycheck()
                ->create(['received_on' => $month->copy()->day(15)->toDateString()]);

            if ($i % 2 === 0) {
                IncomeSource::factory()
                    ->for($user)
                    ->freelance()
                    ->create(['received_on' => $month->copy()->day(22)->toDateString()]);
            }
        }
    }

    /**
     * @param  array<string, ExpenseCategory>  $categories
     */
    private function seedBills(User $user, array $categories): void
    {
        foreach (BillFactory::DEFAULTS as $row) {
            $category = $categories[$row['category']] ?? null;

            if ($category === null) {
                continue;
            }

            Bill::create([
                'user_id' => $user->id,
                'expense_category_id' => $category->id,
                'name' => $row['name'],
                'amount' => $row['amount'],
                'frequency' => $row['frequency'],
                'interval_days' => null,
                'next_due_on' => $this->nextDueFor($row['frequency']),
                'last_paid_on' => today()->copy()->subMonthNoOverflow()->day(min(5, today()->day))->toDateString(),
                'autopay_reminder' => in_array($row['name'], ['Rent', 'Electric'], true),
                'is_active' => true,
            ]);
        }
    }

    private function nextDueFor(string $frequency): string
    {
        return match ($frequency) {
            'weekly' => today()->copy()->addDays(3)->toDateString(),
            'biweekly' => today()->copy()->addDays(10)->toDateString(),
            'quarterly' => today()->copy()->addDays(20)->toDateString(),
            'annual' => today()->copy()->addMonths(2)->toDateString(),
            default => today()->copy()->addDays(random_int(2, 25))->toDateString(),
        };
    }

    private function seedDebts(User $user): void
    {
        $archetypes = [
            ['name' => 'Chase Visa', 'type' => 'credit_card', 'balance' => 2840.00, 'original_balance' => 4200.00, 'apr' => 22.99, 'minimum_payment' => 75.00, 'due_day' => 18],
            ['name' => 'Federal Student Loan', 'type' => 'student', 'balance' => 14200.00, 'original_balance' => 21000.00, 'apr' => 5.50, 'minimum_payment' => 185.00, 'due_day' => 1],
            ['name' => 'Auto Loan', 'type' => 'auto', 'balance' => 9650.00, 'original_balance' => 18500.00, 'apr' => 6.90, 'minimum_payment' => 312.00, 'due_day' => 10],
        ];

        foreach ($archetypes as $row) {
            Debt::create([
                'user_id' => $user->id,
                ...$row,
                'is_active' => true,
            ]);
        }
    }

    private function seedSavingsGoals(User $user): void
    {
        SavingsGoal::create([
            'user_id' => $user->id,
            'name' => 'Emergency Fund',
            'target_amount' => 6000.00,
            'current_amount' => 2450.00,
            'target_date' => today()->copy()->addMonths(10)->toDateString(),
            'is_achieved' => false,
        ]);

        SavingsGoal::create([
            'user_id' => $user->id,
            'name' => 'Vacation',
            'target_amount' => 2500.00,
            'current_amount' => 900.00,
            'target_date' => today()->copy()->addMonths(8)->toDateString(),
            'is_achieved' => false,
        ]);

        SavingsGoal::create([
            'user_id' => $user->id,
            'name' => 'Holiday Gifts',
            'target_amount' => 800.00,
            'current_amount' => 800.00,
            'target_date' => today()->copy()->addMonths(6)->toDateString(),
            'is_achieved' => true,
        ]);
    }

    /**
     * @param  array<string, ExpenseCategory>  $categories
     */
    private function seedExpenses(User $user, array $categories): void
    {
        // Monthly targets per category (rough averages, USD).
        $monthlySpend = [
            'Groceries' => 520,
            'Dining Out' => 240,
            'Transportation' => 180,
            'Utilities' => 220,
            'Housing' => 1450,
            'Entertainment' => 90,
            'Health' => 75,
            'Shopping' => 160,
            'Subscriptions' => 40,
            'Personal Care' => 55,
        ];

        for ($i = self::HISTORY_MONTHS - 1; $i >= 0; $i--) {
            $monthStart = today()->copy()->subMonthsNoOverflow($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            $isCurrent = $i === 0;

            foreach ($monthlySpend as $name => $target) {
                $category = $categories[$name] ?? null;

                if ($category === null) {
                    continue;
                }

                // Spread the monthly target across 3-8 expenses; a small drift
                // across months keeps trend signals believable.
                $count = random_int(3, 8);
                $drift = random_int(-15, 15) / 100;
                $budget = (float) $target * (1 + $drift);

                // The current month is only partially elapsed — scale down so
                // the dashboard doesn't read as overshooting on day 5.
                if ($isCurrent) {
                    $elapsed = today()->day / max($monthEnd->day, 1);
                    $budget *= $elapsed;
                    $count = max(2, (int) round($count * $elapsed));
                }

                $this->seedExpensesForCategory(
                    user: $user,
                    category: $category,
                    from: $monthStart,
                    to: $isCurrent ? today() : $monthEnd,
                    totalAmount: $budget,
                    count: $count,
                );
            }
        }
    }

    private function seedExpensesForCategory(
        User $user,
        ExpenseCategory $category,
        CarbonInterface $from,
        CarbonInterface $to,
        float $totalAmount,
        int $count,
    ): void {
        if ($count < 1 || $totalAmount <= 0 || $to < $from) {
            return;
        }

        $descriptions = ExpenseFactory::DESCRIPTIONS[$category->name] ?? ['Misc expense'];
        $remaining = $totalAmount;

        for ($i = 0; $i < $count; $i++) {
            $last = ($i === $count - 1);
            $share = $last
                ? $remaining
                : round($totalAmount / $count * (random_int(70, 130) / 100), 2);

            $share = max(1.0, min($share, max(1.0, $remaining)));
            $remaining = max(0.0, $remaining - $share);

            Expense::create([
                'user_id' => $user->id,
                'expense_category_id' => $category->id,
                'amount' => round($share, 2),
                'occurred_on' => Carbon::createFromTimestamp(
                    random_int($from->timestamp, $to->timestamp),
                )->toDateString(),
                'description' => $descriptions[array_rand($descriptions)],
            ]);
        }
    }

    /**
     * @param  array<string, ExpenseCategory>  $categories
     */
    private function seedBudgetTargets(User $user, array $categories): void
    {
        $targets = [
            'Groceries' => 550,
            'Dining Out' => 200,
            'Transportation' => 200,
            'Entertainment' => 100,
            'Shopping' => 150,
            'Subscriptions' => 50,
        ];

        $month = today()->copy()->startOfMonth()->toDateString();

        foreach ($targets as $name => $amount) {
            $category = $categories[$name] ?? null;

            if ($category === null) {
                continue;
            }

            BudgetTarget::create([
                'user_id' => $user->id,
                'expense_category_id' => $category->id,
                'period_month' => $month,
                'amount' => $amount,
            ]);
        }
    }

    private function seedInsights(User $user): void
    {
        $period = today()->copy()->startOfMonth()->toDateString();

        foreach (AiInsightFactory::SAMPLES as $sample) {
            AiInsight::create([
                'user_id' => $user->id,
                'kind' => $sample['kind'],
                'severity' => $sample['severity'],
                'title' => $sample['title'],
                'body' => $sample['body'],
                'data' => null,
                'status' => 'new',
                'generated_for_period' => $period,
            ]);
        }
    }
}
