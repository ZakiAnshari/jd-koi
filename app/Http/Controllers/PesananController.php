<?php

namespace App\Http\Controllers;

use App\Models\KatalogKoi;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PesananController extends Controller
{
    public function checkout(KatalogKoi $katalogKoi)
    {
        $katalogKoi->load(['koi.breeder']);

        $katalog = [
            'id' => $katalogKoi->id,
            'tanggal' => $katalogKoi->tanggal,
            'qty' => $katalogKoi->qty,
            'sell_idr' => $katalogKoi->sell_idr,
            'koi' => [
                'id' => $katalogKoi->koi->id,
                'nama' => $katalogKoi->koi->nama,
                'kode' => $katalogKoi->koi->kode,
                'photo' => $katalogKoi->koi->photo,
                'breeder' => [
                    'name_breeder' => $katalogKoi->koi->breeder->name_breeder ?? null,
                ],
            ],
        ];

        $payment_info = [
            'bank' => config('app.bank_name', 'BCA'),
            'account_name' => config('app.bank_account_name', 'Chandra Kirana'),
            'account_number' => config('app.bank_account_number', '2741477086'),
        ];

        return Inertia::render('Customer/Pesanan/Checkout', [
            'katalog' => $katalog,
            'payment_info' => $payment_info,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'katalog_koi_id' => 'required|integer|exists:katalog_kois,id',
            'qty' => 'required|integer|min:1',
            'catatan' => 'nullable|string',
        ]);

        $katalogKoi = KatalogKoi::findOrFail($validated['katalog_koi_id']);

        // Check if quantity exceeds available stock
        if ($validated['qty'] > $katalogKoi->qty) {
            return back()->withErrors(['qty' => 'Kuantitas melebihi stok yang tersedia.']);
        }

        // Calculate total price
        $harga_satuan = $katalogKoi->sell_idr;
        $total_harga = $validated['qty'] * $harga_satuan;

        // Create pesanan
        $pesanan = Pesanan::create([
            'customer_id' => auth()->id(),
            'katalog_koi_id' => $validated['katalog_koi_id'],
            'qty' => $validated['qty'],
            'harga_satuan' => $harga_satuan,
            'total_harga' => $total_harga,
            'catatan' => $validated['catatan'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('customer.pesanan.index')->with('success', "Pesanan {$pesanan->kode_pesanan} berhasil dibuat!");
    }

    public function index()
    {
        $pesanans = auth()->user()
            ->pesanans()
            ->with(['katalogKoi.koi'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Customer/Pesanan/Index', [
            'pesanans' => $pesanans,
        ]);
    }
}
