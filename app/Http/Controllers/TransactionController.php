<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Koi;
use App\Models\Stock;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $transactions = Sale::select(['id', 'koi_id', 'date', 'status', 'qty', 'sell_jpy', 'sell_idr', 'margin', 'fee', 'stock_jpy', 'stock_idr', 'sell_to', 'profit', 'created_at'])
            ->with('koi:id,code,name')
            ->orderBy('date', 'desc')
            ->paginate(10);

        $kois = Koi::select(['id', 'code', 'name'])->orderBy('code')->get();

        $stocks = Stock::select(['id', 'koi_id', 'price_jpy', 'price_idr'])
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy('koi_id')
            ->map(function ($group) {
                return $group->first(); // Ambil yang terbaru
            });

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'kois' => $kois,
            'stocks' => $stocks,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'status' => 'required|string|max:50',
            'koi_id' => 'required|exists:kois,id',
            'qty' => 'required|integer|min:1',
            'sell_jpy' => 'required|numeric|min:0',
            'sell_idr' => 'required|numeric|min:0',
            'margin' => 'required|numeric|min:0',
            'fee' => 'required|numeric|min:0',
            'stock_jpy' => 'required|numeric|min:0',
            'stock_idr' => 'required|numeric|min:0',
            'sell_to' => 'required|string|max:255',
            'profit' => 'required|numeric|min:0',
        ]);

        Sale::create($validated);

        return to_route('transactions.index');
    }

    public function show(Sale $transaction): Response
    {
        $transaction->load('koi:id,code,name');

        return Inertia::render('Transactions/Show', [
            'transaction' => $transaction->only(['id', 'koi_id', 'date', 'status', 'qty', 'sell_jpy', 'sell_idr', 'margin', 'fee', 'stock_jpy', 'stock_idr', 'sell_to', 'profit', 'created_at', 'koi']),
        ]);
    }

    public function edit(Sale $transaction): Response
    {
        $kois = Koi::select(['id', 'code', 'name'])->orderBy('code')->get();

        return Inertia::render('Transactions/Edit', [
            'transaction' => $transaction->only(['id', 'date', 'status', 'koi_id', 'qty', 'sell_jpy', 'sell_idr', 'margin', 'fee', 'stock_jpy', 'stock_idr', 'sell_to', 'profit']),
            'kois' => $kois,
        ]);
    }

    public function update(Request $request, Sale $transaction)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'status' => 'required|string|max:50',
            'koi_id' => 'required|exists:kois,id',
            'qty' => 'required|integer|min:1',
            'sell_jpy' => 'required|numeric|min:0',
            'sell_idr' => 'required|numeric|min:0',
            'margin' => 'required|numeric|min:0',
            'fee' => 'required|numeric|min:0',
            'stock_jpy' => 'required|numeric|min:0',
            'stock_idr' => 'required|numeric|min:0',
            'sell_to' => 'required|string|max:255',
            'profit' => 'required|numeric|min:0',
        ]);

        $transaction->fill($validated)->save();

        return to_route('transactions.index');
    }

    public function destroy(Sale $transaction)
    {
        $transaction->delete();

        return to_route('transactions.index');
    }
}
