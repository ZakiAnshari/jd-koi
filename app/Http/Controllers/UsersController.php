<?php

namespace App\Http\Controllers;

use App\Exports\CustomerExport;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class UsersController extends Controller
{
    public function index(): Response
    {
        $users = User::select(['id', 'name', 'email', 'role_id', 'created_at'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Users/Index', [
            'users'      => $users,
            'activeRole' => '',
        ]);
    }

    public function hakAkses(string $role): Response
    {
        $roleId = $role === 'admin' ? 1 : 2;

        $columns = ['id', 'name', 'email', 'role_id', 'created_at'];
        if ($role === 'customer') {
            $columns[] = 'no_telepon';
            $columns[] = 'alamat';
        }

        $users = User::select($columns)
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
        $user = User::create(array_merge($request->validated(), ['role_id' => 1]));
        ActivityLog::log('create', "Menambahkan data User \"{$user->name}\" (ID: {$user->id})", 'users');

        return to_route('admin.hak-akses.admin');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Users/Edit', [
            'user' => $user->only(['id', 'name', 'email', 'role_id']),
        ]);
    }

    public function show(User $user): Response
    {
        return Inertia::render('Users/Show', [
            'user' => $user->only(['id', 'name', 'email', 'created_at', 'updated_at']),
        ]);
    }

    public function editAdmin(User $user): Response
    {
        return Inertia::render('Users/Edit', [
            'user'    => $user->only(['id', 'name', 'email', 'role_id']),
            'formUrl' => "/admin/hak-akses/admin/{$user->id}",
        ]);
    }

    public function updateAdmin(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->validated())->save();
        ActivityLog::log('update', "Mengubah data User \"{$user->name}\" (ID: {$user->id})", 'users');

        return to_route('admin.hak-akses.admin');
    }

    public function showCustomer(User $user): Response
    {
        return Inertia::render('Users/CustomerShow', [
            'customer' => $user->only(['id', 'name', 'email', 'no_telepon', 'alamat', 'created_at']),
        ]);
    }

    public function exportCustomerExcel()
    {
        $filename = 'Data_Customer_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new CustomerExport(), $filename);
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->validated())->save();
        ActivityLog::log('update', "Mengubah data User \"{$user->name}\" (ID: {$user->id})", 'users');

        return to_route($this->redirectRouteByRole($user->role_id));
    }

    public function destroy(User $user): RedirectResponse
    {
        // Do not allow deleting the currently logged in user.
        if (Auth::id() === $user->id) {
            return back();
        }

        $roleId = $user->role_id;
        $name = $user->name;
        $id = $user->id;
        $user->delete();
        ActivityLog::log('delete', "Menghapus data User \"{$name}\" (ID: {$id})", 'users');

        return to_route($this->redirectRouteByRole($roleId));
    }

    private function redirectRouteByRole(?int $roleId): string
    {
        if ($roleId === 1) return 'admin.hak-akses.admin';
        if ($roleId === 2) return 'admin.hak-akses.customer';
        return 'users.index';
    }
}
