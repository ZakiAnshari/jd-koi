<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        $home = match (true) {
            $user?->role_id === 1 => '/dashboard',
            $user?->role_id === 2 => '/customer/dashboard',
            default               => '/dashboard',
        };

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended($home);
    }
}
