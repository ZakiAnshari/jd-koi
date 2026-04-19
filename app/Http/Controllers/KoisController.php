<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Koi;
use App\Models\KoiImage;
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
            ->get();

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
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $koi = Koi::create($validated);

        // Handle multiple image uploads
        if ($request->hasFile('images')) {
            $sortOrder = 0;
            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('kois', 'public');
                KoiImage::create([
                    'koi_id' => $koi->id,
                    'image_path' => $imagePath,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        ActivityLog::log('create', "Menambahkan data Koi \"{$koi->code} - {$koi->name}\" (ID: {$koi->id})", 'kois');

        return to_route('kois.index');
    }

    public function edit(Koi $koi): Response
    {
        $breeders = Breeder::select(['id', 'name_breeder'])->orderBy('name_breeder')->get();
        $koi->load('images');

        return Inertia::render('MasterData/Koi/Edit', [
            'koi' => $koi->only(['id', 'breeder_id', 'code', 'name', 'photo', 'images']),
            'breeders' => $breeders,
        ]);
    }

    public function show(Koi $koi): Response
    {
        $koi->load(['breeder:id,name_breeder', 'images']);

        return Inertia::render('MasterData/Koi/Show', [
            'koi' => $koi->only(['id', 'breeder_id', 'code', 'name', 'photo', 'images', 'created_at', 'updated_at', 'breeder']),
        ]);
    }

    public function update(Request $request, Koi $koi)
    {
        $validated = $request->validate([
            'breeder_id' => 'required|exists:breeders,id',
            'code' => 'required|string|max:255|unique:kois,code,' . $koi->id,
            'name' => 'required|string|max:255',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'delete_images' => 'nullable|array',
            'delete_images.*' => 'integer|exists:koi_images,id',
        ]);

        $koi->fill($validated)->save();

        // Handle deleted images
        if ($request->has('delete_images') && is_array($request->input('delete_images'))) {
            KoiImage::whereIn('id', $request->input('delete_images'))->delete();
        }

        // Handle multiple image uploads
        if ($request->hasFile('images')) {
            $sortOrder = $koi->images()->max('sort_order') ?? -1;
            $sortOrder++;
            
            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('kois', 'public');
                KoiImage::create([
                    'koi_id' => $koi->id,
                    'image_path' => $imagePath,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        ActivityLog::log('update', "Mengubah data Koi \"{$koi->code} - {$koi->name}\" (ID: {$koi->id})", 'kois');

        return to_route('kois.index');
    }

    public function destroy(Koi $koi)
    {
        $name = "{$koi->code} - {$koi->name}";
        $id = $koi->id;
        $koi->delete();
        ActivityLog::log('delete', "Menghapus data Koi \"{$name}\" (ID: {$id})", 'kois');

        return to_route('kois.index');
    }

    public function deleteImage(KoiImage $image)
    {
        $koiId = $image->koi_id;
        $image->delete();

        return response()->json(['success' => true]);
    }
}
