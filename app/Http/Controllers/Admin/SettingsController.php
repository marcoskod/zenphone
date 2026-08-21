<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\PricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function margin(PricingService $pricing): View
    {
        return view('admin.settings.margin', [
            'currentMargin' => $pricing->marginPercent(),
            'envMargin' => (float) config('fivesim.margin_percent'),
        ]);
    }

    /**
     * Saved to the settings table, which PricingService::marginPercent() now checks
     * before falling back to FIVESIM_MARGIN_PERCENT - so this takes effect on the very
     * next price lookup, no deploy needed. Global only for now (per-country/service
     * overrides would need a keying scheme this simple key/value table doesn't have yet).
     */
    public function updateMargin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'margin_percent' => ['required', 'numeric', 'min:0', 'max:500'],
        ]);

        Setting::set(Setting::MARGIN_PERCENT_KEY, $validated['margin_percent']);

        return back()->with('status', 'Marge mise à jour : '.$validated['margin_percent'].'%.');
    }
}
