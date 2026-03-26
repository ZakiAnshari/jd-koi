<?php

namespace App\Http\Controllers;

use App\Models\Koi;
use App\Models\Breeder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KoisController extends Controller
{
    public function index(Request $request): Response
    {
        $kois = Koi::select(['id', 'breeder_id', 'code', 'name', 'created_at'])
            ->with('breeder:id,name_breeder')
            ->orderBy('code')
            ->paginate(10);

        $breeders = Breeder::select(['id', 'name_breeder'])->orderBy('name_breeder')->get();

        return Inertia::render('MasterData/Koi/Index', [
            'kois' => $kois,
            'breeders' => $breeders,
        ]);
    }

    public function create(): Response
    {
        $breeders = Breeder::select(['id', 'name_breeder'])->orderBy('name_breeder')->get();

        return Inertia::render('MasterData/Koi/Create', [
            'breeders' => $breeders,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'breeder_id' => 'required|exists:breeders,id',
            'code' => 'required|string|max:255|unique:kois,code',
            'name' => 'required|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('kois', 'public');
        }

        Koi::create($validated);

        return to_route('kois.index');
    }

    public function edit(Koi $koi): Response
    {
        $breeders = Breeder::select(['id', 'name_breeder'])->orderBy('name_breeder')->get();

        return Inertia::render('MasterData/Koi/Edit', [
            'koi' => $koi->only(['id', 'breeder_id', 'code', 'name', 'photo']),
            'breeders' => $breeders,
        ]);
    }

    public function show(Koi $koi): Response
    {
        $koi->load('breeder:id,name_breeder');

        return Inertia::render('MasterData/Koi/Show', [
            'koi' => $koi->only(['id', 'breeder_id', 'code', 'name', 'photo', 'created_at', 'updated_at', 'breeder']),
        ]);
    }

    public function update(Request $request, Koi $koi)
    {
        $validated = $request->validate([
            'breeder_id' => 'required|exists:breeders,id',
            'code' => 'required|string|max:255|unique:kois,code,' . $koi->id,
            'name' => 'required|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('kois', 'public');
        }

        $koi->fill($validated)->save();

        return to_route('kois.index');
    }

    public function destroy(Koi $koi)
    {
        $koi->delete();

        return to_route('kois.index');
    }
}
