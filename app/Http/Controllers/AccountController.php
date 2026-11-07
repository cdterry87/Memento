<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePreferencesRequest;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Show the account settings.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('Account', [
            'timezones' => Inertia::once(fn () => DateTimeZone::listIdentifiers()),
        ]);
    }

    /**
     * Save one or more preferences.
     */
    public function updatePreferences(UpdatePreferencesRequest $request): RedirectResponse
    {
        $request->user()?->update($request->validated());

        return back();
    }
}
