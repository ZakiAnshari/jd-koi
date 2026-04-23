<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Koi;
use App\Models\KoiImage;
use App\Models\KatalogKoi;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Breeder;
use App\Models\JenisKoi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KoisController extends Controller
{
    public function index(Request $request): Response
    {
        $kois = Koi::select(['id', 'breeder_id', 'jenis_koi_id', 'code', 'name', 'age', 'size', 'box_qty', 'weight', 'total_weight', 'created_at'])
            ->with([
                'breeder:id,name_breeder',
                'jenisKoi:id,nama_jenis',
            ])
            ->orderBy('code')
            ->get();

        $breeders  = Breeder::select(['id', 'name_breeder'])->orderBy('name_breeder')->get();
        $jenisKois = JenisKoi::select(['id', 'nama_jenis'])->orderBy('nama_jenis')->get();

        $pembelians = Purchase::select(['id', 'koi_id', 'date', 'qty', 'price_jpy', 'price_idr', 'status', 'created_at'])
            ->with('koi:id,code,name')
            ->orderBy('date', 'desc')
            ->get();

        $katalogs = KatalogKoi::select(['id', 'koi_id', 'pembelian_id', 'tanggal', 'qty', 'sell_jpy', 'kurs', 'sell_idr', 'margin', 'fee', 'profit', 'created_at'])
            ->with('koi:id,code,name')
            ->orderBy('tanggal', 'desc')
            ->get();

        return Inertia::render('MasterData/Koi/Index', [
            'kois'       => $kois,
            'breeders'   => $breeders,
            'jenisKois'  => $jenisKois,
            'pembelians' => $pembelians,
            'katalogs'   => $katalogs,
            'activeTab'  => $request->get('tab', 'koi'),
        ]);
    }

    public function create(): Response
    {
        $breeders  = Breeder::select(['id', 'name_breeder'])->orderBy('name_breeder')->get();
        $jenisKois = JenisKoi::select(['id', 'nama_jenis'])->orderBy('nama_jenis')->get();

        $pembelians = Purchase::select(['id', 'koi_id', 'date', 'qty', 'price_jpy', 'price_idr', 'status'])
            ->with('koi:id,code,name')
            ->orderBy('date', 'desc')
            ->get();

        $katalogs = KatalogKoi::select(['id', 'koi_id', 'pembelian_id', 'tanggal', 'sell_idr'])
            ->with('koi:id,code,name')
            ->orderBy('tanggal', 'desc')
            ->get();

        return Inertia::render('MasterData/Koi/Create', [
            'breeders'   => $breeders,
            'jenisKois'  => $jenisKois,
            'pembelians' => $pembelians,
            'katalogs'   => $katalogs,
        ]);
    }

    public function store(Request $request)
    {
        $koiMode       = $request->input('koi_mode', 'create');
        $pembelianMode = $request->input('pembelian_mode', 'none');
        $katalogMode   = $request->input('katalog_mode', 'none');

        $rules = [];

        // ── Validasi Koi ────────────────────────────────────────────
        if ($koiMode === 'existing') {
            $rules['koi_existing_id'] = [
                'required',
                'exists:kois,id',
                // Jika akan buat pembelian baru, pastikan koi belum punya purchase
                ...($pembelianMode === 'create' ? [
                    function ($_attr, $value, $fail) {
                        if (Purchase::where('koi_id', $value)->exists()) {
                            $fail('Koi ini sudah memiliki data pembelian.');
                        }
                    },
                ] : []),
            ];
        } else {
            $rules['breeder_id']   = 'required|exists:breeders,id';
            $rules['jenis_koi_id'] = 'nullable|exists:jenis_kois,id';
            $rules['code']         = 'required|string|max:255|unique:kois,code';
            $rules['name']         = 'required|string|max:255';
            $rules['age']          = 'nullable|integer|min:0';
            $rules['size']         = 'nullable|numeric|min:0';
            $rules['box_qty']      = 'nullable|integer|min:0';
            $rules['weight']       = 'nullable|numeric|min:0';
            $rules['total_weight'] = 'nullable|numeric|min:0';
            $rules['images.*']     = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048';
        }

        // ── Validasi Pembelian ───────────────────────────────────────
        if ($pembelianMode === 'existing') {
            $existingPembelianRules = ['required', 'exists:purchases,id'];
            if ($katalogMode === 'create') {
                $existingPembelianRules[] = function ($_attr, $value, $fail) {
                    if (KatalogKoi::where('pembelian_id', $value)->exists()) {
                        $fail('Pembelian ini sudah memiliki katalog koi.');
                    }
                };
            }
            $rules['pembelian_existing_id'] = $existingPembelianRules;
        } elseif ($pembelianMode === 'create') {
            $rules['pembelian_date']      = 'required|date';
            $rules['pembelian_qty']       = 'required|integer|min:1';
            $rules['pembelian_price_jpy'] = 'required|numeric|min:0';
            $rules['pembelian_price_idr'] = 'required|numeric|min:0';
        }

        // ── Validasi Katalog ─────────────────────────────────────────
        if ($katalogMode === 'existing') {
            $rules['katalog_existing_id'] = 'required|exists:katalog_kois,id';
        } elseif ($katalogMode === 'create') {
            $rules['katalog_tanggal']  = 'required|date';
            $rules['katalog_qty']      = 'required|integer|min:1';
            $rules['katalog_sell_jpy'] = 'required|numeric|min:0';
            $rules['katalog_kurs']     = 'required|numeric|min:0';
            $rules['katalog_sell_idr'] = 'required|numeric|min:0';
            $rules['katalog_fee']      = 'nullable|numeric|min:0';
        }

        $validated = $request->validate($rules);

        // 1. Resolve / Buat Koi
        if ($koiMode === 'existing') {
            $koi = Koi::findOrFail($validated['koi_existing_id']);
        } else {
            $koi = Koi::create([
                'breeder_id'   => $validated['breeder_id'],
                'jenis_koi_id' => $validated['jenis_koi_id'] ?? null,
                'code'         => $validated['code'],
                'name'         => $validated['name'],
                'age'          => $validated['age'] ?? null,
                'size'         => $validated['size'] ?? null,
                'box_qty'      => $validated['box_qty'] ?? null,
                'weight'       => $validated['weight'] ?? null,
                'total_weight' => $validated['total_weight'] ?? null,
            ]);

            if ($request->hasFile('images')) {
                $sortOrder = 0;
                foreach ($request->file('images') as $image) {
                    $imagePath = $image->store('kois', 'public');
                    KoiImage::create([
                        'koi_id'     => $koi->id,
                        'image_path' => $imagePath,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            ActivityLog::log('create', "Menambahkan data Koi \"{$koi->code} - {$koi->name}\" (ID: {$koi->id})", 'kois');
        }

        // 2. Handle Pembelian
        $pembelianId = null;
        if ($pembelianMode === 'existing') {
            $pembelianId = (int) $validated['pembelian_existing_id'];
        } elseif ($pembelianMode === 'create') {
            $purchase = Purchase::create([
                'koi_id'    => $koi->id,
                'date'      => $validated['pembelian_date'],
                'qty'       => $validated['pembelian_qty'],
                'price_jpy' => $validated['pembelian_price_jpy'],
                'price_idr' => $validated['pembelian_price_idr'],
                'status'    => 'completed',
            ]);
            StockMovement::create([
                'koi_id'         => $purchase->koi_id,
                'type'           => 'in',
                'qty'            => $purchase->qty,
                'reference_type' => 'purchase',
                'reference_id'   => $purchase->id,
                'notes'          => "Purchase #{$purchase->id}",
            ]);
            ActivityLog::log('create', "Menambahkan Pembelian untuk Koi \"{$koi->code} - {$koi->name}\"", 'purchases');
            $pembelianId = $purchase->id;
        }

        // 3. Handle Katalog
        $processedKatalog = false;
        if ($katalogMode === 'existing') {
            $katalog = KatalogKoi::findOrFail($validated['katalog_existing_id']);
            $katalog->update(['koi_id' => $koi->id]);
            ActivityLog::log('update', "Menghubungkan Katalog Koi ke Koi \"{$koi->code} - {$koi->name}\"", 'katalog_kois');
            $processedKatalog = true;
        } elseif ($katalogMode === 'create' && $pembelianId) {
            $purchase = Purchase::find($pembelianId);
            KatalogKoi::create([
                'tanggal'      => $validated['katalog_tanggal'],
                'koi_id'       => $koi->id,
                'pembelian_id' => $pembelianId,
                'qty'          => $validated['katalog_qty'],
                'sell_jpy'     => $validated['katalog_sell_jpy'],
                'kurs'         => $validated['katalog_kurs'],
                'sell_idr'     => $validated['katalog_sell_idr'],
                'stock_jpy'    => $purchase?->price_jpy ?? 0,
                'stock_idr'    => $purchase?->price_idr ?? 0,
                'fee'          => $validated['katalog_fee'] ?? 0,
            ]);
            ActivityLog::log('create', "Menambahkan Katalog Koi untuk \"{$koi->code} - {$koi->name}\"", 'katalog_kois');
            $processedKatalog = true;
        }

        // Redirect ke tab yang sesuai
        if ($processedKatalog) {
            $redirectTab = 'katalog';
        } elseif ($pembelianId !== null) {
            $redirectTab = 'pembelian';
        } else {
            $redirectTab = 'koi';
        }

        return redirect()->route('kois.index', ['tab' => $redirectTab]);
    }

    public function edit(Koi $koi): Response
    {
        $breeders  = Breeder::select(['id', 'name_breeder'])->orderBy('name_breeder')->get();
        $jenisKois = JenisKoi::select(['id', 'nama_jenis'])->orderBy('nama_jenis')->get();
        $koi->load('images');

        return Inertia::render('MasterData/Koi/Edit', [
            'koi'       => $koi->only(['id', 'breeder_id', 'jenis_koi_id', 'code', 'name', 'photo', 'age', 'size', 'box_qty', 'weight', 'total_weight', 'images']),
            'breeders'  => $breeders,
            'jenisKois' => $jenisKois,
        ]);
    }

    public function show(Koi $koi): Response
    {
        $koi->load(['breeder:id,name_breeder', 'jenisKoi:id,nama_jenis', 'images']);

        return Inertia::render('MasterData/Koi/Show', [
            'koi' => $koi->only(['id', 'breeder_id', 'jenis_koi_id', 'code', 'name', 'photo', 'age', 'size', 'box_qty', 'weight', 'total_weight', 'images', 'created_at', 'updated_at', 'breeder', 'jenis_koi']),
        ]);
    }

    public function update(Request $request, Koi $koi)
    {
        $validated = $request->validate([
            'breeder_id'      => 'required|exists:breeders,id',
            'jenis_koi_id'    => 'nullable|exists:jenis_kois,id',
            'code'            => ['required', 'string', 'max:255', Rule::unique('kois', 'code')->ignore($koi->id)],
            'name'            => 'required|string|max:255',
            'age'             => 'nullable|integer|min:0',
            'size'            => 'nullable|numeric|min:0',
            'box_qty'         => 'nullable|integer|min:0',
            'weight'          => 'nullable|numeric|min:0',
            'total_weight'    => 'nullable|numeric|min:0',
            'images.*'        => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'delete_images'   => 'nullable|array',
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
        $image->delete();

        return response()->json(['success' => true]);
    }
}
