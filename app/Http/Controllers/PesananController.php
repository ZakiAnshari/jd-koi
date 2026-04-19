<?php

namespace App\Http\Controllers;

use App\Models\KatalogKoi;
use App\Models\Pesanan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PesananController extends Controller
{
    private function mapKoiImages($koi): array
    {
        return $koi->images->map(fn($img) => [
            'id'  => $img->id,
            'url' => asset('storage/' . $img->image_path),
        ])->values()->all();
    }

    public function checkout(KatalogKoi $katalogKoi)
    {
        $katalogKoi->load(['koi.breeder', 'koi.images']);

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
                'images' => $this->mapKoiImages($katalogKoi->koi),
                'breeder' => [
                    'name_breeder' => $katalogKoi->koi->breeder->name_breeder ?? null,
                ],
            ],
        ];

        $payment_info = [
            'bank' => config('app.bank_name', 'BCA'),
            'account_name' => config('app.bank_account_name', 'Admin JD-KOI'),
            'account_number' => config('app.bank_account_number', '2741477086'),
        ];

        $user = auth()->user();

        return Inertia::render('Customer/Pesanan/Checkout', [
            'katalog'      => $katalog,
            'payment_info' => $payment_info,
            'customer'     => [
                'name'        => $user->name,
                'no_telepon'  => $user->no_telepon ?? '',
                'alamat'      => $user->alamat ?? '',
            ],
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

        $pesanan->load(['katalogKoi.koi.breeder', 'katalogKoi.koi.images']);

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
            'account_name' => config('app.bank_account_name', 'Admin JD-KOI'),
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
                        'images' => $this->mapKoiImages($pesanan->katalogKoi->koi),
                        'breeder' => [
                            'name_breeder' => $pesanan->katalogKoi->koi->breeder->name_breeder ?? null,
                        ],
                    ],
                ],
            ],
            'customer' => [
                'name'       => $customer->name,
                'email'      => $customer->email,
                'no_telepon' => $customer->no_telepon ?? '',
                'alamat'     => $customer->alamat ?? '',
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
            ->get();

        return Inertia::render('Customer/Pesanan/Index', [
            'pesanans' => $pesanans,
        ]);
    }

    public function downloadNota(Pesanan $pesanan)
    {
        if ($pesanan->customer_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        if (!in_array($pesanan->status, ['confirmed', 'completed'])) {
            abort(403, 'Nota hanya tersedia untuk pesanan yang sudah dikonfirmasi atau selesai.');
        }

        $pesanan->load(['katalogKoi.koi.breeder', 'katalogKoi.koi.images']);

        $customer = auth()->user();
        $payment_info = [
            'bank'           => config('app.bank_name', 'BCA'),
            'account_name'   => config('app.bank_account_name', 'Admin JD-KOI'),
            'account_number' => config('app.bank_account_number', '2741477086'),
        ];

        $pdf = Pdf::loadView('customer.nota', compact('pesanan', 'customer', 'payment_info'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("nota-pesanan-{$pesanan->kode_pesanan}.pdf");
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
