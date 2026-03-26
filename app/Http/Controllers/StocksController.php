<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Koi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StocksController extends Controller
{
    public function index(): Response
    {
        $stocks = Stock::select(['id', 'koi_id', 'date', 'qty', 'price_jpy', 'price_idr', 'total_jpy', 'total_idr', 'created_at'])
            ->with('koi:id,code,name')
            ->orderBy('date', 'desc')
            ->paginate(10);

        $kois = Koi::select(['id', 'code', 'name'])->orderBy('code')->get();

        return Inertia::render('Inventory/Index', [
            'stocks' => $stocks,
            'kois' => $kois,
        ]);
    }

    public function create(): Response
    {
        $kois = Koi::select(['id', 'code', 'name'])->orderBy('code')->get();

        return Inertia::render('Inventory/Create', [
            'kois' => $kois,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'koi_id' => 'required|exists:kois,id',
            'date' => 'required|date',
            'qty' => 'required|integer|min:1',
            'price_jpy' => 'required|numeric|min:0',
            'price_idr' => 'required|numeric|min:0',
            'total_jpy' => 'required|numeric|min:0',
            'total_idr' => 'required|numeric|min:0',
        ]);

        Stock::create($validated);

        return to_route('stocks.index');
    }

    public function show(Stock $stock): Response
    {
        $stock->load('koi:id,code,name,breeder_id');

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
            'koi_id' => 'required|exists:kois,id',
            'date' => 'required|date',
            'qty' => 'required|integer|min:1',
            'price_jpy' => 'required|numeric|min:0',
            'price_idr' => 'required|numeric|min:0',
            'total_jpy' => 'required|numeric|min:0',
            'total_idr' => 'required|numeric|min:0',
        ]);

        $stock->update($validated);

        return to_route('stocks.index');
    }

    public function destroy(Stock $stock)
    {
        $stock->delete();

        return to_route('stocks.index');
    }
}
