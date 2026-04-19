<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminPesananController extends Controller
{
    public function index()
    {
        $pesanans = Pesanan::with(['customer', 'katalogKoi.koi.breeder'])
            ->latest()
            ->get();

        return Inertia::render('Admin/Pesanan/Index', [
            'pesanans' => $pesanans,
        ]);
    }

    public function show(Pesanan $pesanan)
    {
        $pesanan->load(['customer', 'katalogKoi.koi.breeder']);

        $data = $pesanan->toArray();
        $data['customer'] = $pesanan->customer ? [
            'id'         => $pesanan->customer->id,
            'name'       => $pesanan->customer->name,
            'email'      => $pesanan->customer->email,
            'no_telepon' => $pesanan->customer->no_telepon ?? '-',
            'alamat'     => $pesanan->customer->alamat ?? '-',
        ] : null;

        return Inertia::render('Admin/Pesanan/Show', [
            'pesanan' => $data,
            'payment_info' => [
                'bank' => config('app.bank_name', 'BCA'),
                'account_name' => config('app.bank_account_name', 'Admin JD-KOI'),
                'account_number' => config('app.bank_account_number', '2741477086'),
            ],
        ]);
    }

    public function updateStatus(Request $request, Pesanan $pesanan)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);

        $oldStatus = $pesanan->status;

        if ($oldStatus === $validated['status']) {
            return back();
        }

        try {
            DB::transaction(function () use ($pesanan, $validated, $oldStatus) {
                $pesanan->update(['status' => $validated['status']]);

                $pesanan->load('katalogKoi.koi');
                $namaKoi = $pesanan->katalogKoi->koi->name ?? 'Koi';

                $notifMap = [
                    'confirmed' => [
                        'title'   => 'Pesanan Dikonfirmasi',
                        'message' => "Pesanan Anda dengan kode {$pesanan->kode_pesanan} untuk koi {$namaKoi} telah dikonfirmasi. Terima kasih atas pembayaran Anda.",
                        'type'    => 'order_confirmed',
                    ],
                    'completed' => [
                        'title'   => 'Pesanan Selesai',
                        'message' => "Pesanan Anda dengan kode {$pesanan->kode_pesanan} untuk koi {$namaKoi} telah selesai. Terima kasih telah berbelanja!",
                        'type'    => 'order_completed',
                    ],
                    'cancelled' => [
                        'title'   => 'Pesanan Dibatalkan',
                        'message' => "Pesanan Anda dengan kode {$pesanan->kode_pesanan} untuk koi {$namaKoi} telah dibatalkan. Silakan hubungi admin untuk informasi lebih lanjut.",
                        'type'    => 'order_cancelled',
                    ],
                ];

                if (isset($notifMap[$validated['status']])) {
                    Notification::create([
                        'user_id'    => $pesanan->customer_id,
                        'pesanan_id' => $pesanan->id,
                        ...$notifMap[$validated['status']],
                    ]);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', "Status pesanan {$pesanan->kode_pesanan} berhasil diperbarui menjadi {$validated['status']}.");
    }
}
