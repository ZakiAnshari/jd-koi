<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Breeder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BreedersController extends Controller
{
    public function index(Request $request): Response
    {
        $breeders = Breeder::select(['id', 'name_breeder', 'created_at'])
            ->orderBy('name_breeder')
            ->paginate(10);

        return Inertia::render('MasterData/Breeder/Index', [
            'breeders' => $breeders,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('MasterData/Breeder/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_breeder' => 'required|string|max:255|unique:breeders,name_breeder',
        ], [
            'name_breeder.unique' => 'Breeder ":input" sudah ada. Tidak bisa menambahkan data yang sama.',
        ]);

        $breeder = Breeder::create($validated);
        ActivityLog::log('create', "Menambahkan data Breeder \"{$breeder->name_breeder}\" (ID: {$breeder->id})", 'breeders');

        return to_route('breeders.index');
    }

    public function edit(Breeder $breeder): Response
    {
        return Inertia::render('MasterData/Breeder/Edit', [
            'breeder' => $breeder->only(['id', 'name_breeder']),
        ]);
    }

    public function show(Breeder $breeder): Response
    {
        return Inertia::render('MasterData/Breeder/Show', [
            'breeder' => $breeder->only(['id', 'name_breeder', 'created_at', 'updated_at']),
        ]);
    }

    public function update(Request $request, Breeder $breeder)
    {
        $validated = $request->validate([
            'name_breeder' => [
                'required',
                'string',
                'max:255',
                Rule::unique('breeders', 'name_breeder')->ignore($breeder->id),
            ],
        ], [
            'name_breeder.unique' => 'Breeder ":input" sudah ada. Tidak bisa menggunakan nama yang sama.',
        ]);

        $breeder->fill($validated)->save();
        ActivityLog::log('update', "Mengubah data Breeder \"{$breeder->name_breeder}\" (ID: {$breeder->id})", 'breeders');

        return to_route('breeders.index');
    }

    public function destroy(Breeder $breeder)
    {
        $name = $breeder->name_breeder;
        $id = $breeder->id;
        $breeder->delete();
        ActivityLog::log('delete', "Menghapus data Breeder \"{$name}\" (ID: {$id})", 'breeders');

        return to_route('breeders.index');
    }
}