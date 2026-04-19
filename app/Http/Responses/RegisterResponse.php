<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        $home = match (true) {
            $user?->role_id === 1 => '/dashboard',
            $user?->role_id === 2 => '/customer/dashboard',
            default               => '/customer/dashboard',
        };

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect($home);
    }
}
