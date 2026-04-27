<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class CustomerDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->role_id === 1) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Customer/Dashboard', [
            'customerStats' => [
                'nama' => $user->name,
            ],
        ]);
    }
}
