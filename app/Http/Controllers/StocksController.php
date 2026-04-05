<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Stock;
use App\Models\Koi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StocksController extends Controller
{
    public function index(): Response
    {
        $stocks = Stock::with('koi:id,code,name,photo')
            ->orderBy('koi_id')
            ->get();

        return Inertia::render('Inventory/Index', [
            'stocks' => $stocks,
        ]);
    }

    public function create()
    {
        return to_route('stocks.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'koi_id' => 'required|exists:kois,id|unique:stocks,koi_id',
            'qty' => 'required|integer|min:0',
        ]);

        $stock = Stock::create($validated);

        $koiName = $stock->koi?->name ?? 'Unknown Koi';
        $koiCode = $stock->koi?->code ?? 'Unknown Code';
        ActivityLog::log('create', "Menambahkan data Stok \"{$koiCode} - {$koiName}\" (ID: {$stock->id})", 'stocks');

        return to_route('stocks.index');
    }

    public function show(Stock $stock): Response
    {
        $stock->load('koi:id,code,name,photo');

        return Inertia::render('Inventory/Show', [
            'stock' => $stock,
        ]);
    }

    public function edit(Stock $stock): Response
    {
        $stock->load('koi:id,code,name');
        $kois = Koi::select(['id', 'code', 'name'])->orderBy('code')->get();

        return Inertia::render('Inventory/Edit', [
            'stock' => $stock,
            'kois' => $kois,
        ]);
    }

    public function update(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'koi_id' => 'required|exists:kois,id|unique:stocks,koi_id,' . $stock->id,
            'qty' => 'required|integer|min:0',
        ]);

        $stock->update($validated);

        $koiName = $stock->koi?->name ?? 'Unknown Koi';
        $koiCode = $stock->koi?->code ?? 'Unknown Code';
        ActivityLog::log('update', "Mengubah data Stok \"{$koiCode} - {$koiName}\" (ID: {$stock->id})", 'stocks');

        return to_route('stocks.index');
    }

    public function destroy(Stock $stock)
    {
        $koiName = $stock->koi?->name ?? 'Unknown Koi';
        $koiCode = $stock->koi?->code ?? 'Unknown Code';
        $id = $stock->id;
        ActivityLog::log('delete', "Menghapus data Stok \"{$koiCode} - {$koiName}\" (ID: {$id})", 'stocks');

        $stock->delete();

        return to_route('stocks.index');
    }
}
