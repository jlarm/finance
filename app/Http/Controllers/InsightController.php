<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InsightController extends Controller
{
    public function index(Request $request): Response
    {
        $insights = $request->user()->aiInsights()
            ->active()
            ->latest()
            ->paginate(20);

        return Inertia::render('insights/Index', [
            'insights' => $insights,
        ]);
    }

    public function update(Request $request, AiInsight $insight): RedirectResponse
    {
        abort_if($insight->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['new', 'dismissed', 'acted_on'])],
        ]);

        $insight->update($validated);

        return back();
    }

    public function destroy(Request $request, AiInsight $insight): RedirectResponse
    {
        abort_if($insight->user_id !== $request->user()->id, 403);

        $insight->delete();

        return back();
    }
}
