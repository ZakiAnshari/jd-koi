<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Sale;
use App\Models\Koi;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    /**
     * Status yang perlu isi Sell JPY, Kurs, Sell IDR, Margin
     */
    protected const STATUS_NEEDS_SELL = ['azukari', 'shipped'];

    /**
     * Status yang perlu isi Customer (Sell To)
     */
    protected const STATUS_NEEDS_CUSTOMER = ['azukari', 'shipped', 'sold'];

    /**
     * Status yang mengurangi stok (buat StockMovement OUT)
     */
    protected const STATUS_REDUCES_STOCK = ['azukari', 'shipped', 'sold'];

    /**
     * Semua status yang valid
     */
    protected const ALL_STATUSES = [
        'azukari',
        'cancel',
        'cancel_dead',
        'mudpond_hiroshima',
        'na',
        'oyagoi',
        'pending',
        'shipped',
        'sold',
    ];

    public function index(Request $request): Response
    {
        $transactions = Sale::select([
                'id', 'customer_id', 'koi_id', 'date', 'status', 'qty',
                'sell_jpy', 'sell_idr', 'stock_jpy', 'stock_idr',
                'fee', 'margin', 'profit', 'created_at',
            ])
            ->with(['koi:id,code,name', 'customer:id,name'])
            ->orderBy('date', 'desc')
            ->get();

        $kois = Koi::select(['id', 'code', 'name'])->orderBy('code')->get();
        $customers = Customer::select(['id', 'name'])->orderBy('name')->get();

        $koiSummaries = $this->buildKoiSummaries($kois);

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'kois' => $kois,
            'customers' => $customers,
            'koiSummaries' => $koiSummaries,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateSale($request);
        $validated = $this->prepareSaleData($validated);

        // Cek stok hanya untuk status yang mengurangi stok
        if (in_array($validated['status'], self::STATUS_REDUCES_STOCK)) {
            $available = $this->saleAvailableQty(null, $validated);
            if ($available < $validated['qty']) {
                return back()->withErrors(['qty' => 'Stok koi tidak mencukupi untuk transaksi ini.'])->withInput();
            }
        }

        $transaction = Sale::create($validated);
        $this->syncSaleStockMovement($transaction);

        $koiCode = $transaction->koi?->code ?? 'Unknown Code';
        $koiName = $transaction->koi?->name ?? 'Unknown Koi';
        $customerName = $transaction->customer?->name ?? 'Unknown Customer';
        ActivityLog::log('create', "Menambahkan data Transaksi \"{$koiCode} - {$koiName} ke {$customerName}\" (ID: {$transaction->id})", 'transactions');

        $routeName = $request->is('penjualan*') ? 'penjualan.index' : 'transactions.index';
        return to_route($routeName);
    }

    public function show(Sale $transaction): Response
    {
        $transaction->load(['koi:id,code,name', 'customer:id,name']);

        return Inertia::render('Transactions/Show', [
            'transaction' => array_merge(
                $transaction->only([
                    'id', 'customer_id', 'koi_id', 'date', 'status', 'qty',
                    'sell_jpy', 'sell_idr', 'stock_jpy', 'stock_idr',
                    'fee', 'margin', 'profit', 'created_at',
                ]),
                [
                    'koi' => $transaction->koi,
                    'customer' => $transaction->customer,
                ],
            ),
        ]);
    }

    public function edit(Sale $transaction): Response
    {
        $transaction->load('customer:id,name');
        $kois = Koi::select(['id', 'code', 'name'])->orderBy('code')->get();
        $customers = Customer::select(['id', 'name'])->orderBy('name')->get();

        $koiSummaries = $this->buildKoiSummaries($kois);

        return Inertia::render('Transactions/Edit', [
            'transaction' => array_merge(
                $transaction->only([
                    'id', 'date', 'status', 'koi_id', 'customer_id', 'qty',
                    'sell_jpy', 'sell_idr', 'stock_jpy', 'stock_idr',
                    'fee', 'margin', 'profit',
                ]),
                ['customer' => $transaction->customer],
            ),
            'kois' => $kois,
            'customers' => $customers,
            'koiSummaries' => $koiSummaries,
            'exchange_rate' => config('app.exchange_rate', 110),
        ]);
    }

    public function update(Request $request, Sale $transaction)
    {
        $validated = $this->validateSale($request);
        $validated = $this->prepareSaleData($validated);

        // Cek stok hanya untuk status yang mengurangi stok
        if (in_array($validated['status'], self::STATUS_REDUCES_STOCK)) {
            $available = $this->saleAvailableQty($transaction, $validated);
            if ($available < $validated['qty']) {
                return back()->withErrors(['qty' => 'Stok koi tidak mencukupi untuk transaksi ini.'])->withInput();
            }
        }

        $transaction->fill($validated)->save();
        $this->syncSaleStockMovement($transaction);

        $koiCode = $transaction->koi?->code ?? 'Unknown Code';
        $koiName = $transaction->koi?->name ?? 'Unknown Koi';
        $customerName = $transaction->customer?->name ?? 'Unknown Customer';
        ActivityLog::log('update', "Mengubah data Transaksi \"{$koiCode} - {$koiName} ke {$customerName}\" (ID: {$transaction->id})", 'transactions');

        $routeName = $request->is('penjualan*') ? 'penjualan.index' : 'transactions.index';
        return to_route($routeName);
    }

    public function destroy(Request $request, Sale $transaction)
    {
        $koiCode = $transaction->koi?->code ?? 'Unknown Code';
        $koiName = $transaction->koi?->name ?? 'Unknown Koi';
        $customerName = $transaction->customer?->name ?? 'Unknown Customer';
        $id = $transaction->id;
        ActivityLog::log('delete', "Menghapus data Transaksi \"{$koiCode} - {$koiName} ke {$customerName}\" (ID: {$id})", 'transactions');

        $this->deleteSaleStockMovement($transaction);
        $transaction->delete();

        $routeName = $request->is('penjualan*') ? 'penjualan.index' : 'transactions.index';
        return to_route($routeName);
    }

    // ─── Validation ─────────────────────────────────────────

    /**
     * Validasi dinamis berdasarkan status.
     *
     * - Semua status: date, status, koi_id, qty wajib
     * - Azukari & Shipped: sell_jpy, sell_idr, margin wajib + customer_id wajib
     * - Sold: customer_id wajib, sell fields tidak perlu
     * - Lainnya: customer_id & sell fields tidak perlu
     */
    protected function validateSale(Request $request): array
    {
        $statusList = implode(',', self::ALL_STATUSES);

        // Step 1: Validasi field dasar dulu (termasuk status)
        $base = $request->validate([
            'date' => 'required|date',
            'status' => "required|in:{$statusList}",
            'koi_id' => 'required|exists:kois,id',
            'qty' => 'required|integer|min:1',
        ]);

        $status = $base['status'];

        // Step 2: Validasi conditional berdasarkan status
        $conditionalRules = [];

        if (in_array($status, self::STATUS_NEEDS_SELL)) {
            $conditionalRules['sell_jpy'] = 'required|numeric|min:0';
            $conditionalRules['sell_idr'] = 'required|numeric|min:0';
            $conditionalRules['margin'] = 'required|numeric|min:0';
        }

        if (in_array($status, self::STATUS_NEEDS_CUSTOMER)) {
            $conditionalRules['customer_id'] = 'required|exists:customers,id';
        }

        $conditional = [];
        if (!empty($conditionalRules)) {
            $conditional = $request->validate($conditionalRules);
        }

        return array_merge($base, $conditional);
    }

    // ─── Data Preparation ───────────────────────────────────

    /**
     * Siapkan data sebelum simpan:
     * - Set default 0 untuk field yang tidak diisi
     * - Ambil snapshot stock_jpy/stock_idr dari purchase
     * - Hitung fee dan profit
     */
    protected function prepareSaleData(array $validated): array
    {
        $status = $validated['status'];

        // Default 0 untuk field yang tidak diisi berdasarkan status
        if (!in_array($status, self::STATUS_NEEDS_SELL)) {
            $validated['sell_jpy'] = 0;
            $validated['sell_idr'] = 0;
            $validated['margin'] = 0;
        }

        if (!in_array($status, self::STATUS_NEEDS_CUSTOMER)) {
            $validated['customer_id'] = null;
        }

        // Snapshot harga modal dari purchase
        $averagePurchase = $this->getAveragePurchaseCost($validated['koi_id']);
        $validated['stock_jpy'] = $averagePurchase['price_jpy'];
        $validated['stock_idr'] = $averagePurchase['price_idr'];

        // Hitung fee dan profit
        $validated = $this->computeSaleMetrics($validated);

        return $validated;
    }

    /**
     * Hitung Fee dan Profit berdasarkan status.
     *
     * Semua harga = per ekor.
     *
     * Fee = Sell IDR × (Margin / 100)
     *
     * Profit per status:
     *   azukari  → Fee (komisi titipan)
     *   sold     → Stock IDR (modal per ekor jadi profit)
     *   shipped  → (Sell IDR - Stock IDR) + Fee
     *   lainnya  → 0
     */
    protected function computeSaleMetrics(array $validated): array
    {
        $sellIdr = (float) ($validated['sell_idr'] ?? 0);
        $margin = (float) ($validated['margin'] ?? 0);
        $stockIdr = (float) ($validated['stock_idr'] ?? 0);
        $status = $validated['status'];

        // Fee = Sell IDR × Margin%
        $fee = round($sellIdr * ($margin / 100), 2);

        // Profit berdasarkan status (per ekor)
        $profit = match ($status) {
            'azukari' => $fee,
            'sold' => $stockIdr,
            'shipped' => ($sellIdr - $stockIdr) + $fee,
            default => 0,
        };

        $validated['fee'] = $fee;
        $validated['profit'] = round($profit, 2);

        return $validated;
    }

    // ─── Stock Movement ─────────────────────────────────────

    /**
     * Sync stock movement berdasarkan status.
     * Hanya status yang mengurangi stok yang buat movement OUT.
     */
    protected function syncSaleStockMovement(Sale $transaction): void
    {
        // Hapus movement lama
        StockMovement::where('reference_type', 'sale')
            ->where('reference_id', $transaction->id)
            ->delete();

        // Buat movement OUT hanya untuk status yang mengurangi stok
        if (!in_array($transaction->status, self::STATUS_REDUCES_STOCK)) {
            Stock::refreshQty($transaction->koi_id);
            return;
        }

        StockMovement::create([
            'koi_id' => $transaction->koi_id,
            'type' => 'out',
            'qty' => $transaction->qty,
            'reference_type' => 'sale',
            'reference_id' => $transaction->id,
            'notes' => "Sale #{$transaction->id} ({$transaction->status})",
        ]);

        Stock::refreshQty($transaction->koi_id);
    }

    protected function deleteSaleStockMovement(Sale $transaction): void
    {
        StockMovement::where('reference_type', 'sale')
            ->where('reference_id', $transaction->id)
            ->delete();

        Stock::refreshQty($transaction->koi_id);
    }

    // ─── Helpers ────────────────────────────────────────────

    protected function getAveragePurchaseCost(int $koiId): array
    {
        $purchase = Purchase::where('koi_id', $koiId)
            ->where('status', 'completed')
            ->select(
                DB::raw('SUM(price_jpy * qty) as total_jpy'),
                DB::raw('SUM(price_idr * qty) as total_idr'),
                DB::raw('SUM(qty) as total_qty')
            )
            ->first();

        $totalQty = (int) ($purchase?->total_qty ?? 0);

        if ($totalQty === 0) {
            return ['price_jpy' => 0, 'price_idr' => 0];
        }

        return [
            'price_jpy' => round((float) ($purchase?->total_jpy ?? 0) / $totalQty, 2),
            'price_idr' => round((float) ($purchase?->total_idr ?? 0) / $totalQty, 2),
        ];
    }

    protected function saleAvailableQty(?Sale $transaction, array $validated): int
    {
        $available = Stock::currentQty($validated['koi_id']);

        // Kembalikan qty dari transaksi lama jika sedang update koi yang sama
        if ($transaction
            && in_array($transaction->status, self::STATUS_REDUCES_STOCK)
            && $transaction->koi_id === $validated['koi_id']
        ) {
            $available += $transaction->qty;
        }

        return $available;
    }

    /**
     * Build koiSummaries (harga modal + stok tersedia) untuk semua koi.
     */
    protected function buildKoiSummaries($kois): array
    {
        $purchaseCosts = Purchase::where('status', 'completed')
            ->orderBy('date', 'desc')
            ->get()
            ->unique('koi_id')
            ->keyBy('koi_id');

        $stockSummary = Stock::pluck('qty', 'koi_id');

        $koiSummaries = [];
        foreach ($kois as $koi) {
            $purchase = $purchaseCosts->get($koi->id);
            $koiSummaries[$koi->id] = [
                'modal_jpy' => $purchase?->price_jpy ?? 0,
                'modal_idr' => $purchase?->price_idr ?? 0,
                'available_qty' => $stockSummary->get($koi->id, 0),
            ];
        }

        return $koiSummaries;
    }
}