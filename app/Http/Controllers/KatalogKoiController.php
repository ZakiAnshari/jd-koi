<?php

namespace App\Http\Controllers;

use App\Models\KatalogKoi;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KatalogKoiController extends Controller
{
    private function mapKoiImages($koi): array
    {
        return $koi->images->map(fn($img) => [
            'id'  => $img->id,
            'url' => asset('storage/' . $img->image_path),
        ])->values()->all();
    }

    public function index()
    {
        $katalogs = KatalogKoi::with(['koi.breeder', 'koi.images', 'pembelian'])
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
                        'images'  => $this->mapKoiImages($k->koi),
                    ] : null,
                    'pembelian' => $k->pembelian ? [
                        'id'        => $k->pembelian->id,
                        'date'      => $k->pembelian->date,
                        'qty'       => $k->pembelian->qty,
                        'price_jpy' => $k->pembelian->price_jpy,
                        'price_idr' => $k->pembelian->price_idr,
                    ] : null,
                ];
            });

        $pembelians = Purchase::whereDoesntHave('katalogKoi')
            ->with(['koi.breeder'])
            ->get();

        return Inertia::render('Admin/KatalogKoi/Index', [
            'katalogs'   => $katalogs,
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
        ]);

        $validated['fee'] = $validated['fee'] ?? 0;

        KatalogKoi::create($validated);

        return redirect()->route('admin.katalog-koi.index')
            ->with('success', 'Katalog Koi berhasil ditambahkan.');
    }

    public function show(KatalogKoi $katalogKoi)
    {
        $katalogKoi->load(['koi.breeder', 'koi.images', 'pembelian']);

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
                    'images'  => $this->mapKoiImages($katalogKoi->koi),
                ] : null,
                'pembelian' => $katalogKoi->pembelian ? [
                    'date'      => $katalogKoi->pembelian->date,
                    'price_jpy' => $katalogKoi->pembelian->price_jpy,
                    'price_idr' => $katalogKoi->pembelian->price_idr,
                ] : null,
            ],
        ]);
    }

    public function edit(KatalogKoi $katalogKoi)
    {
        $katalogKoi->load(['koi', 'pembelian']);

        return Inertia::render('Admin/KatalogKoi/Edit', [
            'katalog' => [
                'id'          => $katalogKoi->id,
                'tanggal'     => $katalogKoi->tanggal,
                'qty'         => $katalogKoi->qty,
                'sell_jpy'    => $katalogKoi->sell_jpy,
                'kurs'        => $katalogKoi->kurs,
                'sell_idr'    => $katalogKoi->sell_idr,
                'fee'         => $katalogKoi->fee,
                'stock_jpy'   => $katalogKoi->stock_jpy,
                'stock_idr'   => $katalogKoi->stock_idr,
                'koi'         => $katalogKoi->koi ? [
                    'id'   => $katalogKoi->koi->id,
                    'code' => $katalogKoi->koi->code,
                    'name' => $katalogKoi->koi->name,
                ] : null,
                'pembelian'   => $katalogKoi->pembelian ? [
                    'id'        => $katalogKoi->pembelian->id,
                    'date'      => $katalogKoi->pembelian->date,
                    'price_jpy' => $katalogKoi->pembelian->price_jpy,
                    'price_idr' => $katalogKoi->pembelian->price_idr,
                ] : null,
            ],
        ]);
    }

    public function update(Request $request, KatalogKoi $katalogKoi)
    {
        $validated = $request->validate([
            'tanggal'  => 'required|date',
            'qty'      => 'required|integer|min:1',
            'sell_jpy' => 'required|numeric|min:0',
            'kurs'     => 'required|numeric|min:0',
            'sell_idr' => 'required|numeric|min:0',
            'fee'      => 'nullable|numeric|min:0',
        ]);

        $validated['fee'] = $validated['fee'] ?? 0;
        $katalogKoi->update($validated);

        return redirect()->to('/master-data/koi?tab=katalog')
            ->with('success', 'Katalog Koi berhasil diperbarui.');
    }

    public function destroy(KatalogKoi $katalogKoi)
    {
        $katalogKoi->delete();

        return redirect()->route('admin.katalog-koi.index')
            ->with('success', 'Katalog Koi berhasil dihapus.');
    }

    // Customer Routes
    public function katalogCustomer(Request $request)
    {
        $sort = $request->input('sort', 'latest');

        $query = KatalogKoi::with(['koi.breeder', 'koi.images'])
            ->join('kois', 'katalog_kois.koi_id', '=', 'kois.id')
            ->select('katalog_kois.*');

        match ($sort) {
            'oldest'     => $query->orderBy('katalog_kois.created_at', 'asc'),
            'price_high' => $query->orderBy('sell_idr', 'desc'),
            'price_low'  => $query->orderBy('sell_idr', 'asc'),
            'name_asc'   => $query->orderBy('kois.name', 'asc'),
            'name_desc'  => $query->orderBy('kois.name', 'desc'),
            default      => $query->orderBy('katalog_kois.created_at', 'desc'),
        };

        $katalogs = $query->paginate(12)
            ->appends(['sort' => $sort])
            ->through(function ($katalog) {
                return [
                    'id'       => $katalog->id,
                    'tanggal'  => $katalog->tanggal,
                    'qty'      => $katalog->qty,
                    'sell_idr' => $katalog->sell_idr,
                    'koi'      => [
                        'id'      => $katalog->koi->id,
                        'nama'    => $katalog->koi->name,
                        'kode'    => $katalog->koi->code,
                        'breeder' => [
                            'name_breeder' => $katalog->koi->breeder->name_breeder ?? null,
                        ],
                        'images'  => $this->mapKoiImages($katalog->koi),
                    ],
                ];
            });

        return Inertia::render('Customer/KatalogKoi/Index', [
            'katalogs' => $katalogs,
            'sort'     => $sort,
        ]);
    }

    public function katalogDetail(KatalogKoi $katalogKoi)
    {
        $katalogKoi->load(['koi.breeder', 'koi.images']);

        $katalog = [
            'id'       => $katalogKoi->id,
            'tanggal'  => $katalogKoi->tanggal,
            'qty'      => $katalogKoi->qty,
            'sell_idr' => $katalogKoi->sell_idr,
            'koi'      => [
                'id'        => $katalogKoi->koi->id,
                'nama'      => $katalogKoi->koi->name,
                'kode'      => $katalogKoi->koi->code,
                'deskripsi' => $katalogKoi->koi->deskripsi ?? null,
                'breeder'   => [
                    'name_breeder' => $katalogKoi->koi->breeder->name_breeder ?? null,
                ],
                'images'    => $this->mapKoiImages($katalogKoi->koi),
            ],
        ];

        return Inertia::render('Customer/KatalogKoi/Detail', [
            'katalog' => $katalog,
        ]);
    }
}
