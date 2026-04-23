<?php

namespace App\Http\Controllers;

use App\Models\Breeder;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RekapTransaksiController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Pesanan::with([
            'customer:id,name',
            'katalogKoi:id,koi_id,sell_jpy,sell_idr',
            'katalogKoi.koi:id,breeder_id,jenis_koi_id,code,name,age,size,box_qty,weight,total_weight',
            'katalogKoi.koi.breeder:id,name_breeder',
            'katalogKoi.koi.jenisKoi:id,nama_jenis',
        ])->latest('pesanans.created_at');

        // Filter: status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter: kode / invoice
        if ($request->filled('invoice')) {
            $query->where('kode_pesanan', 'like', '%' . $request->invoice . '%');
        }

        // Filter: koi code
        if ($request->filled('code')) {
            $query->whereHas('katalogKoi.koi', function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->code . '%');
            });
        }

        // Filter: sales date range
        if ($request->filled('date_from')) {
            $query->whereDate('pesanans.created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('pesanans.created_at', '<=', $request->date_to);
        }

        // Filter: breeder
        if ($request->filled('breeder_id')) {
            $query->whereHas('katalogKoi.koi', function ($q) use ($request) {
                $q->where('breeder_id', $request->breeder_id);
            });
        }

        // Filter: customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $pesanans  = $query->paginate(50)->withQueryString();
        $breeders  = Breeder::select(['id', 'name_breeder'])->orderBy('name_breeder')->get();
        $customers = User::select(['id', 'name'])->where('role_id', '!=', 1)->orderBy('name')->get();

        return Inertia::render('Admin/RekapTransaksi/Index', [
            'pesanans'  => $pesanans,
            'breeders'  => $breeders,
            'customers' => $customers,
            'filters'   => $request->only(['status', 'invoice', 'code', 'date_from', 'date_to', 'breeder_id', 'customer_id']),
        ]);
    }

    public function show(Pesanan $pesanan): Response
    {
        $pesanan->load([
            'customer:id,name,email',
            'katalogKoi:id,koi_id,pembelian_id,sell_jpy,kurs,sell_idr,stock_jpy,stock_idr,fee,profit',
            'katalogKoi.koi:id,breeder_id,jenis_koi_id,code,name,age,size,box_qty,weight,total_weight',
            'katalogKoi.koi.breeder:id,name_breeder',
            'katalogKoi.koi.jenisKoi:id,nama_jenis',
            'katalogKoi.koi.images',
        ]);

        return Inertia::render('Admin/RekapTransaksi/Show', [
            'pesanan' => $pesanan,
        ]);
    }
}
