<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserFinanceSettingRequest;
use App\Models\UserFinanceSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $settings = $request->user()->financeSettings
            ?? UserFinanceSetting::make(['user_id' => $request->user()->id]);

        return Inertia::render('settings/Finance', [
            'settings' => $settings,
        ]);
    }

    public function update(UpdateUserFinanceSettingRequest $request): RedirectResponse
    {
        $request->user()->financeSettings()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Settings saved.')]);

        return to_route('finance-settings.edit');
    }
}
