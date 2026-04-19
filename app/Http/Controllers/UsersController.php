<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::select(['id', 'name', 'email', 'role_id', 'created_at'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Users/Index', [
            'users'      => $users,
            'activeRole' => '',
        ]);
    }

    public function hakAkses(Request $request, string $role): Response
    {
        $roleId = $role === 'admin' ? 1 : 2;

        $users = User::select(['id', 'name', 'email', 'role_id', 'created_at'])
            ->where('role_id', $roleId)
            ->orderBy('name')
            ->get();

        return Inertia::render('Users/Index', [
            'users'      => $users,
            'activeRole' => $role,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create');
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());
        ActivityLog::log('create', "Menambahkan data User \"{$user->name}\" (ID: {$user->id})", 'users');

        return to_route('users.index');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Users/Edit', [
            'user' => $user->only(['id', 'name', 'email']),
        ]);
    }

    public function show(User $user): Response
    {
        return Inertia::render('Users/Show', [
            'user' => $user->only(['id', 'name', 'email', 'created_at', 'updated_at']),
        ]);
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->validated())->save();
        ActivityLog::log('update', "Mengubah data User \"{$user->name}\" (ID: {$user->id})", 'users');

        return to_route('users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        // Do not allow deleting the currently logged in user.
        if (auth()->id() === $user->id) {
            return back();
        }

        $name = $user->name;
        $id = $user->id;
        $user->delete();
        ActivityLog::log('delete', "Menghapus data User \"{$name}\" (ID: {$id})", 'users');

        return to_route('users.index');
    }
}
