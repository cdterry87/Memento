<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Http\Responses\LogoutResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signing out also clears the encrypted page history, so the browser's back
 * button can't show journal pages to the next person on a shared device.
 */
class SignedOut extends LogoutResponse
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        Inertia::clearHistory();

        return parent::toResponse($request);
    }
}
