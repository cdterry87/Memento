<?php

namespace App\Http\Controllers;

use App\Actions\DeleteAccount;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdatePreferencesRequest;
use App\Models\DataExport;
use App\Models\User;
use App\Notifications\DataExportReady;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Show the account settings.
     */
    public function show(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Account', [
            'timezones' => Inertia::once(fn () => DateTimeZone::listIdentifiers()),
            'dataExport' => $this->exportStatus($user->dataExports()->latest('id')->first()),
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

    /**
     * Delete the account, with all of its entries and files, and sign out.
     */
    public function destroy(DeleteAccountRequest $request, DeleteAccount $deleteAccount): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        Auth::guard('web')->logout();
        $deleteAccount->handle($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Inertia::clearHistory();

        return redirect()->route('home')->with('status', 'account-deleted');
    }

    /**
     * Where the latest export stands, for the account page.
     *
     * @return array{status: 'pending'|'ready'|'failed', expiresAt: string|null, downloadUrl: string|null}|null
     */
    private function exportStatus(?DataExport $export): ?array
    {
        return match (true) {
            $export === null => null,
            $export->isReady() => [
                'status' => 'ready',
                'expiresAt' => $export->expires_at?->toIso8601String(),
                'downloadUrl' => DataExportReady::downloadUrl($export),
            ],
            $export->isPending() => ['status' => 'pending', 'expiresAt' => null, 'downloadUrl' => null],
            $export->failed_at !== null => ['status' => 'failed', 'expiresAt' => null, 'downloadUrl' => null],
            default => null,
        };
    }
}
