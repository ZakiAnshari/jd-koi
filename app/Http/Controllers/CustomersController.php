<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomersController extends Controller
{
    public function index(Request $request): Response
    {
        $customers = Customer::select(['id', 'name', 'phone', 'created_at'])
            ->orderBy('name')
            ->paginate(10);

        return Inertia::render('MasterData/Customer/Index', [
            'customers' => $customers,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('MasterData/Customer/Create');
    }

    public function store(Request $request)
    {
        $input = $request->all();

        if (array_key_exists('phone', $input) && !is_null($input['phone'])) {
            $input['phone'] = (string) $input['phone'];
        }

        $validated = validator($input, [
            'name' => 'required|string|max:255|unique:customers,name',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ])->validate();

        $customer = Customer::create($validated);
        ActivityLog::log('create', "Menambahkan data Customer \"{$customer->name}\" (ID: {$customer->id})", 'customers');

        return to_route('customers.index');
    }

    public function edit(Customer $customer): Response
    {
        return Inertia::render('MasterData/Customer/Edit', [
            'customer' => $customer->only(['id', 'name', 'phone', 'address']),
        ]);
    }

    public function show(Customer $customer): Response
    {
        return Inertia::render('MasterData/Customer/Show', [
            'customer' => $customer->only(['id', 'name', 'phone', 'address', 'created_at', 'updated_at']),
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $input = $request->all();

        if (array_key_exists('phone', $input) && !is_null($input['phone'])) {
            $input['phone'] = (string) $input['phone'];
        }

        $validated = validator($input, [
            'name' => 'required|string|max:255|unique:customers,name,' . $customer->id,
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ])->validate();

        $customer->fill($validated)->save();
        ActivityLog::log('update', "Mengubah data Customer \"{$customer->name}\" (ID: {$customer->id})", 'customers');

        return to_route('customers.index');
    }

    public function destroy(Customer $customer)
    {
        $name = $customer->name;
        $id = $customer->id;
        $customer->delete();
        ActivityLog::log('delete', "Menghapus data Customer \"{$name}\" (ID: {$id})", 'customers');

        return to_route('customers.index');
    }
}
