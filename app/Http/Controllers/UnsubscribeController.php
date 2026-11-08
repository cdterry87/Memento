<?php

namespace App\Http\Controllers;

use App\Enums\EmailType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Turns off one kind of email from a signed link. Opening the link shows a
 * confirmation button rather than unsubscribing straight away, because some
 * mail scanners open every link in an email. Mail apps that support one-click
 * unsubscribe post to the same link instead, via the List-Unsubscribe header.
 */
class UnsubscribeController extends Controller
{
    public function show(Request $request, User $user, EmailType $type): Response
    {
        return Inertia::render('Unsubscribe', [
            'emailLabel' => $type->label(),
            'isSubscribed' => (bool) $user->{$type->preference()},
            'confirmUrl' => $request->fullUrl(),
        ]);
    }

    public function store(Request $request, User $user, EmailType $type): RedirectResponse|HttpResponse
    {
        $user->update([$type->preference() => false]);

        if ($request->header('X-Inertia')) {
            return back();
        }

        return response("You won't get {$type->label()} any more.");
    }
}
