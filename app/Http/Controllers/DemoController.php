<?php

namespace App\Http\Controllers;

use App\Rules\Turnstile;
use App\Services\DemoAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DemoController extends Controller
{
    /**
     * Signs in as the shared demo user, after a Turnstile check so only
     * people, not bots, get in. Only available in demo mode.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless(config('memento.demo_mode'), 404);

        $request->validate(
            ['turnstile' => ['required', new Turnstile($request->ip())]],
            ['turnstile.required' => 'Please wait for the security check to finish, then try again.'],
        );

        $user = DemoAccount::user();

        if ($user === null) {
            throw ValidationException::withMessages([
                'turnstile' => "The demo isn't ready yet. Please try again later.",
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('today');
    }
}
