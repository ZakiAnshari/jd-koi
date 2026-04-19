<?php

namespace App\Http\Controllers;

use App\Models\KatalogKoi;
use App\Models\KatalogKoiImage;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class KatalogKoiController extends Controller
{
    public function index()
    {
        $katalogs = KatalogKoi::with(['koi.breeder', 'pembelian', 'images'])
            ->get()
            ->map(function ($k) {
                return [
                    'id'        => $k->id,
                    'tanggal'   => $k->tanggal,
                    'qty'       => $k->qty,
                    'sell_jpy'  => $k->sell_jpy,
                    'kurs'      => $k->kurs,
                    'sell_idr'  => $k->sell_idr,
                    'margin'    => $k->margin,
                    'stock_jpy' => $k->stock_jpy,
                    'stock_idr' => $k->stock_idr,
                    'fee'       => $k->fee,
                    'profit'    => $k->profit,
                    'koi'       => $k->koi ? [
                        'id'      => $k->koi->id,
                        'code'    => $k->koi->code,
                        'name'    => $k->koi->name,
                        'breeder' => $k->koi->breeder
                            ? ['name_breeder' => $k->koi->breeder->name_breeder]
                            : null,
                    ] : null,
                    'pembelian' => $k->pembelian ? [
                        'id'        => $k->pembelian->id,
                        'date'      => $k->pembelian->date,
                        'qty'       => $k->pembelian->qty,
                        'price_jpy' => $k->pembelian->price_jpy,
                        'price_idr' => $k->pembelian->price_idr,
                    ] : null,
                    'images' => $k->images->map(fn($img) => [
                        'id'  => $img->id,
                        'url' => asset('storage/' . $img->image_path),
                    ])->values(),
                ];
            });

        $pembelians = Purchase::whereDoesntHave('katalogKoi')
            ->with(['koi.breeder'])
            ->get();

        return Inertia::render('Admin/KatalogKoi/Index', [
            'katalogs'  => $katalogs,
            'pembelians' => $pembelians,
        ]);
    }

    public function create()
    {
        return redirect()->route('admin.katalog-koi.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal'      => 'required|date',
            'koi_id'       => 'required|exists:kois,id',
            'pembelian_id' => 'required|exists:purchases,id|unique:katalog_kois,pembelian_id',
            'qty'          => 'required|integer|min:1',
            'sell_jpy'     => 'required|numeric|min:0',
            'kurs'         => 'required|numeric|min:0',
            'sell_idr'     => 'required|numeric|min:0',
            'stock_jpy'    => 'required|numeric|min:0',
            'stock_idr'    => 'required|numeric|min:0',
            'fee'          => 'nullable|numeric|min:0',
            'fotos'        => 'nullable|array|max:10',
            'fotos.*'      => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $validated['fee'] = $validated['fee'] ?? 0;

        $katalog = KatalogKoi::create($validated);

        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $i => $foto) {
                $path = $foto->store('katalog-koi', 'public');
                $katalog->images()->create(['image_path' => $path, 'sort_order' => $i]);
            }
        }

        return redirect()->route('admin.katalog-koi.index')
            ->with('success', 'Katalog Koi berhasil ditambahkan.');
    }

    public function show(KatalogKoi $katalogKoi)
    {
        $katalogKoi->load(['koi.breeder', 'pembelian', 'images']);

        return Inertia::render('Admin/KatalogKoi/Show', [
            'katalog' => [
                'id'        => $katalogKoi->id,
                'tanggal'   => $katalogKoi->tanggal,
                'qty'       => $katalogKoi->qty,
                'sell_jpy'  => $katalogKoi->sell_jpy,
                'kurs'      => $katalogKoi->kurs,
                'sell_idr'  => $katalogKoi->sell_idr,
                'margin'    => $katalogKoi->margin,
                'stock_jpy' => $katalogKoi->stock_jpy,
                'stock_idr' => $katalogKoi->stock_idr,
                'fee'       => $katalogKoi->fee,
                'profit'    => $katalogKoi->profit,
                'koi'       => $katalogKoi->koi ? [
                    'code'    => $katalogKoi->koi->code,
                    'name'    => $katalogKoi->koi->name,
                    'breeder' => $katalogKoi->koi->breeder?->name_breeder,
                ] : null,
                'pembelian' => $katalogKoi->pembelian ? [
                    'date'      => $katalogKoi->pembelian->date,
                    'price_jpy' => $katalogKoi->pembelian->price_jpy,
                    'price_idr' => $katalogKoi->pembelian->price_idr,
                ] : null,
                'images' => $katalogKoi->images->map(fn($img) => [
                    'id'  => $img->id,
                    'url' => asset('storage/' . $img->image_path),
                ])->values(),
            ],
        ]);
    }

    public function edit(KatalogKoi $katalogKoi)
    {
        return redirect()->route('admin.katalog-koi.index');
    }

    public function update(Request $request, KatalogKoi $katalogKoi)
    {
        $validated = $request->validate([
            'tanggal'        => 'required|date',
            'koi_id'         => 'required|exists:kois,id',
            'pembelian_id'   => 'required|exists:purchases,id',
            'qty'            => 'required|integer|min:1',
            'sell_jpy'       => 'required|numeric|min:0',
            'kurs'           => 'required|numeric|min:0',
            'sell_idr'       => 'required|numeric|min:0',
            'stock_jpy'      => 'required|numeric|min:0',
            'stock_idr'      => 'required|numeric|min:0',
            'fee'            => 'nullable|numeric|min:0',
            'fotos'          => 'nullable|array|max:10',
            'fotos.*'        => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'delete_images'  => 'nullable|array',
            'delete_images.*'=> 'integer|exists:katalog_koi_images,id',
        ]);

        $validated['fee'] = $validated['fee'] ?? 0;

        // Delete removed images
        if (!empty($validated['delete_images'])) {
            $toDelete = $katalogKoi->images()->whereIn('id', $validated['delete_images'])->get();
            foreach ($toDelete as $img) {
                Storage::disk('public')->delete($img->image_path);
                $img->delete();
            }
        }

        // Add new images
        if ($request->hasFile('fotos')) {
            $offset = $katalogKoi->images()->max('sort_order') ?? -1;
            foreach ($request->file('fotos') as $i => $foto) {
                $path = $foto->store('katalog-koi', 'public');
                $katalogKoi->images()->create(['image_path' => $path, 'sort_order' => $offset + $i + 1]);
            }
        }

        unset($validated['fotos'], $validated['delete_images']);
        $katalogKoi->update($validated);

        return redirect()->route('admin.katalog-koi.index')
            ->with('success', 'Katalog Koi berhasil diperbarui.');
    }

    public function destroy(KatalogKoi $katalogKoi)
    {
        $katalogKoi->load('images');
        foreach ($katalogKoi->images as $img) {
            Storage::disk('public')->delete($img->image_path);
        }

        $katalogKoi->delete();

        return redirect()->route('admin.katalog-koi.index')
            ->with('success', 'Katalog Koi berhasil dihapus.');
    }

    public function destroyImage(KatalogKoi $katalogKoi, KatalogKoiImage $katalogKoiImage)
    {
        abort_if($katalogKoiImage->katalog_koi_id !== $katalogKoi->id, 403);
        Storage::disk('public')->delete($katalogKoiImage->image_path);
        $katalogKoiImage->delete();

        return back();
    }

    // Customer Routes
    public function katalogCustomer()
    {
        $katalogs = KatalogKoi::with(['koi.breeder', 'images'])
            ->paginate(12)
            ->through(function ($katalog) {
                return [
                    'id'       => $katalog->id,
                    'tanggal'  => $katalog->tanggal,
                    'qty'      => $katalog->qty,
                    'sell_idr' => $katalog->sell_idr,
                    'koi'      => [
                        'id'      => $katalog->koi->id,
                        'nama'    => $katalog->koi->nama,
                        'kode'    => $katalog->koi->kode,
                        'photo'   => $katalog->koi->photo,
                        'breeder' => [
                            'name_breeder' => $katalog->koi->breeder->name_breeder ?? null,
                        ],
                    ],
                    'images' => $katalog->images->map(fn($img) => [
                        'id'  => $img->id,
                        'url' => asset('storage/' . $img->image_path),
                    ])->values(),
                ];
            });

        return Inertia::render('Customer/KatalogKoi/Index', [
            'katalogs' => $katalogs,
        ]);
    }

    public function katalogDetail(KatalogKoi $katalogKoi)
    {
        $katalogKoi->load(['koi.breeder', 'images']);

        $katalog = [
            'id'       => $katalogKoi->id,
            'tanggal'  => $katalogKoi->tanggal,
            'qty'      => $katalogKoi->qty,
            'sell_idr' => $katalogKoi->sell_idr,
            'koi'      => [
                'id'        => $katalogKoi->koi->id,
                'nama'      => $katalogKoi->koi->nama,
                'kode'      => $katalogKoi->koi->kode,
                'photo'     => $katalogKoi->koi->photo,
                'deskripsi' => $katalogKoi->koi->deskripsi ?? null,
                'breeder'   => [
                    'name_breeder' => $katalogKoi->koi->breeder->name_breeder ?? null,
                ],
            ],
            'images' => $katalogKoi->images->map(fn($img) => [
                'id'  => $img->id,
                'url' => asset('storage/' . $img->image_path),
            ])->values(),
        ];

        return Inertia::render('Customer/KatalogKoi/Detail', [
            'katalog' => $katalog,
        ]);
    }
}
