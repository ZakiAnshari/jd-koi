<?php

namespace App\Http\Controllers;

use App\Models\Breeder;
use Illuminate\Http\Request;
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
            'name_breeder' => 'required|string|max:255',
        ]);

        Breeder::create($validated);

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
            'name_breeder' => 'required|string|max:255',
        ]);

        $breeder->fill($validated)->save();

        return to_route('breeders.index');
    }

    public function destroy(Breeder $breeder)
    {
        $breeder->delete();

        return to_route('breeders.index');
    }
}