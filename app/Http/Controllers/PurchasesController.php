<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Koi;
use App\Models\Purchase;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchasesController extends Controller
{
    public function index(): Response
    {
        $purchases = Purchase::select(['id', 'koi_id', 'date', 'qty', 'price_jpy', 'price_idr', 'status', 'created_at'])
            ->with('koi:id,code,name')
            ->orderBy('date', 'desc')
            ->get();

        $kois = Koi::select(['id', 'code', 'name'])->orderBy('code')->get();

        return Inertia::render('Purchases/Index', [
            'purchases' => $purchases,
            'kois' => $kois,
            'exchange_rate' => config('app.exchange_rate', 110),
        ]);
    }

    public function print(): Response
    {
        $purchases = Purchase::select(['id', 'koi_id', 'date', 'qty', 'price_jpy', 'price_idr', 'status', 'created_at'])
            ->with('koi:id,code,name')
            ->orderBy('date', 'desc')
            ->get();

        $kois = Koi::select(['id', 'code', 'name'])->orderBy('code')->get();

        return Inertia::render('Purchases/Print', [
            'purchases' => ['data' => $purchases],
            'kois' => $kois,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'koi_id' => [
                'required',
                'exists:kois,id',
                function ($attribute, $value, $fail) {
                    $exists = Purchase::where('koi_id', $value)->exists();
                    if ($exists) {
                        $koi = Koi::find($value);
                        $koiName = $koi ? "{$koi->code} - {$koi->name}" : "ID {$value}";
                        $fail("Koi \"{$koiName}\" sudah pernah dibeli. Tidak bisa menambahkan data pembelian yang sama.");
                    }
                },
            ],
            'qty' => 'required|integer|min:1',
            'price_jpy' => 'required|numeric|min:0',
            'price_idr' => 'required|numeric|min:0',
        ]);

        $validated['status'] = 'completed';

        $purchase = Purchase::create($validated);
        $this->syncPurchaseStockMovement($purchase);

        $koiName = $purchase->koi?->name ?? 'Unknown Koi';
        $koiCode = $purchase->koi?->code ?? 'Unknown Code';
        ActivityLog::log('create', "Menambahkan data Pembelian \"{$koiCode} - {$koiName}\" (ID: {$purchase->id})", 'purchases');

        return to_route('purchases.index');
    }

    public function show(Purchase $purchase): Response
    {
        $purchase->load('koi:id,code,name');

        return Inertia::render('Purchases/Show', [
            'purchase' => $purchase,
        ]);
    }

    public function edit(Purchase $purchase): Response
    {
        $purchase->load('koi:id,code,name');
        $kois = Koi::select(['id', 'code', 'name'])->orderBy('code')->get();

        return Inertia::render('Purchases/Edit', [
            'purchase' => $purchase,
            'kois' => $kois,
            'exchange_rate' => config('app.exchange_rate', 110),
        ]);
    }

    public function update(Request $request, Purchase $purchase)
    {
        $rules = [
            'date' => 'required|date',
            'koi_id' => [
                'required',
                'exists:kois,id',
                function ($attribute, $value, $fail) use ($purchase) {
                    // Izinkan koi yang sama kalau memang milik purchase ini sendiri
                    $exists = Purchase::where('koi_id', $value)
                        ->where('id', '!=', $purchase->id)
                        ->exists();
                    if ($exists) {
                        $koi = Koi::find($value);
                        $koiName = $koi ? "{$koi->code} - {$koi->name}" : "ID {$value}";
                        $fail("Koi \"{$koiName}\" sudah pernah dibeli di transaksi lain.");
                    }
                },
            ],
            'qty' => 'required|integer|min:1',
            'price_jpy' => 'required|numeric|min:0',
            'price_idr' => 'required|numeric|min:0',
        ];

        if ($request->filled('status')) {
            $rules['status'] = 'in:completed,cancelled';
        }

        $validated = $request->validate($rules);
        $validated['status'] = $request->filled('status') ? $validated['status'] : ($purchase->status ?? 'completed');

        $purchase->update($validated);
        $this->syncPurchaseStockMovement($purchase);

        $koiName = $purchase->koi?->name ?? 'Unknown Koi';
        $koiCode = $purchase->koi?->code ?? 'Unknown Code';
        ActivityLog::log('update', "Mengubah data Pembelian \"{$koiCode} - {$koiName}\" (ID: {$purchase->id})", 'purchases');

        return to_route('purchases.index');
    }

    public function destroy(Purchase $purchase)
    {
        $koiName = $purchase->koi?->name ?? 'Unknown Koi';
        $koiCode = $purchase->koi?->code ?? 'Unknown Code';
        $id = $purchase->id;
        ActivityLog::log('delete', "Menghapus data Pembelian \"{$koiCode} - {$koiName}\" (ID: {$id})", 'purchases');

        $this->deletePurchaseStockMovement($purchase);
        $purchase->delete();

        return to_route('purchases.index');
    }

    protected function syncPurchaseStockMovement(Purchase $purchase): void
    {
        StockMovement::where('reference_type', 'purchase')
            ->where('reference_id', $purchase->id)
            ->delete();

        if ($purchase->status !== 'completed') {
            return;
        }

        StockMovement::create([
            'koi_id' => $purchase->koi_id,
            'type' => 'in',
            'qty' => $purchase->qty,
            'reference_type' => 'purchase',
            'reference_id' => $purchase->id,
            'notes' => "Purchase #{$purchase->id}",
        ]);
    }

    protected function deletePurchaseStockMovement(Purchase $purchase): void
    {
        StockMovement::where('reference_type', 'purchase')
            ->where('reference_id', $purchase->id)
            ->delete();
    }
}