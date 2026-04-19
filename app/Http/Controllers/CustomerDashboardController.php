<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Pesanan;
use Inertia\Inertia;

class CustomerDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->role_id === 1) {
            return redirect()->route('dashboard');
        }

        $pesananTerbaru = $user->pesanans()
            ->with(['katalogKoi.koi:id,name'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Pesanan $p) => [
                'id'           => $p->id,
                'kode_pesanan' => $p->kode_pesanan,
                'qty'          => $p->qty,
                'total_harga'  => (float) $p->total_harga,
                'status'       => $p->status,
                'created_at'   => $p->created_at->toDateTimeString(),
                'koi_nama'     => $p->katalogKoi?->koi?->name ?? '-',
            ]);

        $notifikasiTerbaru = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Notification $n) => [
                'id'         => $n->id,
                'title'      => $n->title,
                'message'    => $n->message,
                'type'       => $n->type,
                'created_at' => $n->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Customer/Dashboard', [
            'customerStats' => [
                'nama'               => $user->name,
                'total_pesanan'      => $user->pesanans()->count(),
                'pesanan_diproses'   => $user->pesanans()->whereIn('status', ['pending', 'confirmed'])->count(),
                'pesanan_completed'  => $user->pesanans()->where('status', 'completed')->count(),
                'pesanan_cancelled'  => $user->pesanans()->where('status', 'cancelled')->count(),
                'total_belanja'      => (float) $user->pesanans()->where('status', 'completed')->sum('total_harga'),
                'pesanan_terbaru'    => $pesananTerbaru,
                'notifikasi_terbaru' => $notifikasiTerbaru,
            ],
        ]);
    }
}
