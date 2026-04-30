<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Koi;
use App\Models\KoiImage;
use App\Models\Breeder;
use App\Models\JenisKoi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KoisController extends Controller
{
    public function index(): Response
    {
        $kois = Koi::select(['id', 'breeder_id', 'jenis_koi_id', 'code', 'name', 'age', 'size', 'box_qty', 'weight', 'total_weight', 'sale_status', 'customer_id', 'created_at'])
            ->with([
                'breeder:id,name_breeder',
                'jenisKoi:id,nama_jenis',
                'customer:id,name',
                'images:id,koi_id,image_path,sort_order',
            ])
            ->orderBy('code')
            ->get();

        $breeders  = Breeder::select(['id', 'name_breeder'])->orderBy('name_breeder')->get();
        $jenisKois = JenisKoi::select(['id', 'nama_jenis'])->orderBy('nama_jenis')->get();
        $customers = User::select(['id', 'name'])->where('role_id', 2)->orderBy('name')->get();

        $invoices = Koi::select('sale_invoice')
            ->whereNotNull('sale_invoice')
            ->groupBy('sale_invoice')
            ->selectRaw('sale_invoice, COUNT(*) as koi_count')
            ->orderByDesc('sale_invoice')
            ->get();

        return Inertia::render('MasterData/Koi/Index', [
            'kois'      => $kois,
            'breeders'  => $breeders,
            'jenisKois' => $jenisKois,
            'customers' => $customers,
            'invoices'  => $invoices,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'breeder_id'   => 'required|exists:breeders,id',
            'jenis_koi_id' => 'nullable|exists:jenis_kois,id',
            'code'         => 'required|string|max:255|unique:kois,code',
            'name'         => 'required|string|max:255',
            'age'          => 'nullable|integer|min:0',
            'size'         => 'nullable|numeric|min:0',
            'box_qty'      => 'nullable|integer|min:0',
            'weight'       => 'nullable|numeric|min:0',
            'total_weight' => 'nullable|numeric|min:0',
            'images.*'     => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'customer_id'  => 'nullable|exists:users,id',
            'sale_tanggal' => 'nullable|date',
            'sale_qty'     => 'nullable|integer|min:1',
            'sell_jpy'     => 'nullable|numeric|min:0',
            'buy_jpy'      => 'nullable|numeric|min:0',
            'kurs'         => 'nullable|numeric|min:0',
            'sell_idr'    => 'nullable|numeric|min:0',
            'unit_price'  => 'nullable|numeric|min:0',
            'stock_price' => 'nullable|numeric|min:0',
            'fee'          => 'nullable|numeric|min:0',
            'sale_status'     => 'nullable|in:cancel,dead_refund,refund,cancel_refund,pending,shipped,azukari,dead',
            'sale_invoice'    => 'nullable|string|max:100',
            'azukari_period'  => 'nullable|string|max:255',
            'shipping_cost'   => 'nullable|numeric|min:0',
            'shipping_qty'    => 'nullable|integer|min:1',
            'shipping_note'   => 'nullable|string|max:255',
        ]);

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
            'customer_id'  => $validated['customer_id'] ?? null,
            'sale_tanggal' => $validated['sale_tanggal'] ?? null,
            'sale_qty'     => $validated['sale_qty'] ?? null,
            'sell_jpy'     => $validated['sell_jpy'] ?? null,
            'buy_jpy'      => $validated['buy_jpy'] ?? null,
            'kurs'         => $validated['kurs'] ?? null,
            'sell_idr'    => $validated['sell_idr'] ?? null,
            'unit_price'  => $validated['unit_price'] ?? null,
            'stock_price' => $validated['stock_price'] ?? null,
            'fee'          => $validated['fee'] ?? 0,
            'sale_status'  => $validated['sale_status'] ?? null,
            'sale_invoice'   => $validated['sale_invoice'] ?? $this->generateInvoice(),
            'azukari_period' => $validated['azukari_period'] ?? null,
            'shipping_cost'  => $validated['shipping_cost'] ?? null,
            'shipping_qty'   => $validated['shipping_qty'] ?? null,
            'shipping_note'  => $validated['shipping_note'] ?? null,
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

        return redirect()->route('kois.index');
    }

    public function edit(Koi $koi): Response
    {
        $breeders  = Breeder::select(['id', 'name_breeder'])->orderBy('name_breeder')->get();
        $jenisKois = JenisKoi::select(['id', 'nama_jenis'])->orderBy('nama_jenis')->get();
        $customers = User::select(['id', 'name'])->where('role_id', 2)->orderBy('name')->get();
        $koi->load('images');

        return Inertia::render('MasterData/Koi/Edit', [
            'koi'       => $koi->only(['id', 'breeder_id', 'jenis_koi_id', 'code', 'name', 'photo', 'age', 'size', 'box_qty', 'weight', 'total_weight', 'images', 'customer_id', 'sale_tanggal', 'sale_qty', 'sell_jpy', 'buy_jpy', 'kurs', 'sell_idr', 'unit_price', 'stock_price', 'fee', 'sale_status', 'sale_invoice', 'sale_shipped_date', 'azukari_period', 'shipping_cost', 'shipping_qty', 'shipping_note']),
            'breeders'  => $breeders,
            'jenisKois' => $jenisKois,
            'customers' => $customers,
        ]);
    }

    public function show(Koi $koi): Response
    {
        $koi->load(['breeder:id,name_breeder', 'jenisKoi:id,nama_jenis', 'images', 'customer:id,name']);

        return Inertia::render('MasterData/Koi/Show', [
            'koi' => $koi->only(['id', 'breeder_id', 'jenis_koi_id', 'code', 'name', 'photo', 'age', 'size', 'box_qty', 'weight', 'total_weight', 'images', 'created_at', 'updated_at', 'breeder', 'jenis_koi', 'customer', 'customer_id', 'sale_tanggal', 'sale_qty', 'unit_price', 'buy_jpy', 'kurs', 'sell_idr', 'stock_price', 'fee', 'sale_status', 'sale_invoice', 'sale_shipped_date', 'azukari_period', 'shipping_cost', 'shipping_qty', 'shipping_note']),
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
            'customer_id'     => 'nullable|exists:users,id',
            'sale_tanggal'    => 'nullable|date',
            'sale_qty'        => 'nullable|integer|min:1',
            'sell_jpy'        => 'nullable|numeric|min:0',
            'buy_jpy'         => 'nullable|numeric|min:0',
            'kurs'            => 'nullable|numeric|min:0',
            'sell_idr'        => 'nullable|numeric|min:0',
            'unit_price'      => 'nullable|numeric|min:0',
            'stock_price'     => 'nullable|numeric|min:0',
            'fee'             => 'nullable|numeric|min:0',
            'sale_status'       => 'nullable|in:cancel,dead_refund,refund,cancel_refund,pending,shipped,azukari,dead',
            'sale_shipped_date' => 'nullable|date',
            'azukari_period'    => 'nullable|string|max:255',
            'shipping_cost'     => 'nullable|numeric|min:0',
            'shipping_qty'      => 'nullable|integer|min:1',
            'shipping_note'     => 'nullable|string|max:255',
        ]);

        $koi->fill($validated)->save();

        if ($request->has('delete_images') && is_array($request->input('delete_images'))) {
            KoiImage::whereIn('id', $request->input('delete_images'))->delete();
        }

        if ($request->hasFile('images')) {
            $sortOrder = $koi->images()->max('sort_order') ?? -1;
            $sortOrder++;
            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('kois', 'public');
                KoiImage::create([
                    'koi_id'     => $koi->id,
                    'image_path' => $imagePath,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        ActivityLog::log('update', "Mengubah data Koi \"{$koi->code} - {$koi->name}\" (ID: {$koi->id})", 'kois');

        return redirect()->to('/master-data/koi');
    }

    public function destroy(Koi $koi)
    {
        $name = "{$koi->code} - {$koi->name}";
        $id   = $koi->id;
        $koi->delete();
        ActivityLog::log('delete', "Menghapus data Koi \"{$name}\" (ID: {$id})", 'kois');

        return to_route('kois.index');
    }

    public function updateStatus(Request $request, Koi $koi)
    {
        $validated = $request->validate([
            'sale_status' => 'required|in:cancel,dead_refund,refund,cancel_refund,pending,shipped,azukari,dead',
        ]);

        $koi->sale_status = $validated['sale_status'];
        $koi->save();

        ActivityLog::log('update', "Mengubah status Koi \"{$koi->code} - {$koi->name}\" menjadi \"{$validated['sale_status']}\" (ID: {$koi->id})", 'kois');

        return to_route('kois.index');
    }

    public function deleteImage(KoiImage $image)
    {
        $image->delete();

        return response()->json(['success' => true]);
    }

    private function generateInvoice(): string
    {
        $prefix = now()->format('dmy'); // e.g. 270426

        $todayCount = Koi::whereDate('created_at', today())
            ->whereNotNull('sale_invoice')
            ->count();

        $sequence = str_pad($todayCount + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . $sequence;
    }
}
