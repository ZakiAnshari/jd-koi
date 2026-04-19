<?php

namespace App\Http\Controllers;

use App\Models\KatalogKoi;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'nama' => $katalogKoi->koi->name,
                'kode' => $katalogKoi->koi->code,
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

        try {
            $result = DB::transaction(function () use ($validated) {
                $katalogKoi = KatalogKoi::with(['koi.breeder'])
                    ->lockForUpdate()
                    ->findOrFail($validated['katalog_koi_id']);

                if ($validated['qty'] > $katalogKoi->qty) {
                    throw new \Exception("Stok tidak mencukupi. Stok tersedia: {$katalogKoi->qty}");
                }

                $harga_satuan = $katalogKoi->sell_idr;
                $total_harga  = $validated['qty'] * $harga_satuan;

                $pesanan = Pesanan::create([
                    'customer_id'    => auth()->id(),
                    'katalog_koi_id' => $validated['katalog_koi_id'],
                    'qty'            => $validated['qty'],
                    'harga_satuan'   => $harga_satuan,
                    'total_harga'    => $total_harga,
                    'catatan'        => $validated['catatan'] ?? null,
                    'status'         => 'pending',
                ]);

                return compact('pesanan', 'katalogKoi');
            });
        } catch (\Exception $e) {
            return back()->withErrors(['qty' => $e->getMessage()]);
        }

        $pesanan    = $result['pesanan'];
        $katalogKoi = $result['katalogKoi'];
        $customer   = auth()->user();

        $namaKoi     = $katalogKoi->koi->name;
        $breeder     = $katalogKoi->koi->breeder->name_breeder ?? 'N/A';
        $hargaFormat = number_format($pesanan->harga_satuan, 0, ',', '.');
        $totalFormat = number_format($pesanan->total_harga, 0, ',', '.');

        $whatsappMessage  = "Halo Admin, saya ingin memesan koi berikut:\n";
        $whatsappMessage .= "Kode Pesanan: {$pesanan->kode_pesanan}\n";
        $whatsappMessage .= "Nama Koi: {$namaKoi}\n";
        $whatsappMessage .= "Breeder: {$breeder}\n";
        $whatsappMessage .= "Harga Satuan: Rp {$hargaFormat}\n";
        $whatsappMessage .= "Quantity: {$validated['qty']}\n";
        $whatsappMessage .= "Total: Rp {$totalFormat}\n";
        $whatsappMessage .= "Nama Customer: {$customer->name}\n";
        $whatsappMessage .= "Email: {$customer->email}";

        $whatsappUrl = 'https://wa.me/6282387444002?text=' . urlencode($whatsappMessage);

        return redirect()->route('customer.pesanan.show', $pesanan)->with([
            'success'      => true,
            'message'      => "Pesanan {$pesanan->kode_pesanan} berhasil dibuat!",
            'pesanan'      => ['id' => $pesanan->id, 'kode_pesanan' => $pesanan->kode_pesanan],
            'whatsapp_url' => $whatsappUrl,
        ]);
    }

    public function show(Pesanan $pesanan)
    {
        // Ensure customer can only view their own orders
        if ($pesanan->customer_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $pesanan->load(['katalogKoi.koi.breeder']);

        // Generate WhatsApp message
        $customer = auth()->user();
        $namaKoi = $pesanan->katalogKoi->koi->name;
        $breeder = $pesanan->katalogKoi->koi->breeder->name_breeder ?? 'N/A';
        $hargaFormat = number_format($pesanan->harga_satuan, 0, ',', '.');
        $totalFormat = number_format($pesanan->total_harga, 0, ',', '.');

        $whatsappMessage = "Halo Admin, saya ingin memesan koi berikut:\n";
        $whatsappMessage .= "Kode Pesanan: {$pesanan->kode_pesanan}\n";
        $whatsappMessage .= "Nama Koi: {$namaKoi}\n";
        $whatsappMessage .= "Breeder: {$breeder}\n";
        $whatsappMessage .= "Harga Satuan: Rp {$hargaFormat}\n";
        $whatsappMessage .= "Quantity: {$pesanan->qty}\n";
        $whatsappMessage .= "Total: Rp {$totalFormat}\n";
        $whatsappMessage .= "Nama Customer: {$customer->name}\n";
        $whatsappMessage .= "Email: {$customer->email}";

        $whatsappUrl = 'https://wa.me/6282387444002?text=' . urlencode($whatsappMessage);

        $payment_info = [
            'bank' => config('app.bank_name', 'BCA'),
            'account_name' => config('app.bank_account_name', 'Chandra Kirana'),
            'account_number' => config('app.bank_account_number', '2741477086'),
        ];

        return Inertia::render('Customer/Pesanan/Show', [
            'pesanan' => [
                'id' => $pesanan->id,
                'kode_pesanan' => $pesanan->kode_pesanan,
                'qty' => $pesanan->qty,
                'harga_satuan' => $pesanan->harga_satuan,
                'total_harga' => $pesanan->total_harga,
                'status' => $pesanan->status,
                'catatan' => $pesanan->catatan,
                'created_at' => $pesanan->created_at,
                'katalogKoi' => [
                    'id' => $pesanan->katalogKoi->id,
                    'sell_idr' => $pesanan->katalogKoi->sell_idr,
                    'koi' => [
                        'id' => $pesanan->katalogKoi->koi->id,
                        'nama' => $pesanan->katalogKoi->koi->name,
                        'kode' => $pesanan->katalogKoi->koi->code,
                        'photo' => $pesanan->katalogKoi->koi->photo,
                        'breeder' => [
                            'name_breeder' => $pesanan->katalogKoi->koi->breeder->name_breeder ?? null,
                        ],
                    ],
                ],
            ],
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email,
            ],
            'payment_info' => $payment_info,
            'whatsapp_url' => $whatsappUrl,
        ]);
    }

    public function index()
    {
        $pesanans = auth()->user()
            ->pesanans()
            ->with(['katalogKoi.koi.breeder'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Customer/Pesanan/Index', [
            'pesanans' => $pesanans,
        ]);
    }

    public function destroy(Pesanan $pesanan)
    {
        // Ensure customer can only delete their own orders
        if ($pesanan->customer_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Only allow deletion if status is pending
        if ($pesanan->status !== 'pending') {
            return back()->withErrors(['status' => 'Hanya pesanan dengan status pending yang dapat dihapus.']);
        }

        $kode_pesanan = $pesanan->kode_pesanan;
        $pesanan->delete();

        return redirect()->route('customer.pesanan.index')->with('success', "Pesanan {$kode_pesanan} berhasil dihapus.");
    }
}
