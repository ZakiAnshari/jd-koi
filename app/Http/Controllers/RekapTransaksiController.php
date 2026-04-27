<?php

namespace App\Http\Controllers;

use App\Models\Breeder;
use App\Models\Koi;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RekapTransaksiController extends Controller
{
    public function show(Koi $koi): Response
    {
        $koi->load(['breeder:id,name_breeder', 'jenisKoi:id,nama_jenis', 'images', 'customer:id,name']);
        return Inertia::render('Admin/RekapTransaksi/Show', ['koi' => $koi]);
    }

    public function index(Request $request): Response
    {
        $user    = $request->user();
        $isAdmin = $user->role_id === 1;

        $query = Koi::with([
            'breeder:id,name_breeder',
            'jenisKoi:id,nama_jenis',
            'customer:id,name',
        ])->whereNotNull('sale_status');

        // Customer hanya lihat data miliknya sendiri
        if (!$isAdmin) {
            $query->where('customer_id', $user->id);
        }

        // Filter: status
        if ($request->filled('status')) {
            $query->where('sale_status', $request->status);
        }

        // Filter: kode koi
        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }

        // Filter: invoice
        if ($request->filled('invoice')) {
            $query->where('sale_invoice', 'like', '%' . $request->invoice . '%');
        }

        // Filter: tanggal penjualan
        if ($request->filled('date_from')) {
            $query->whereDate('sale_tanggal', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('sale_tanggal', '<=', $request->date_to);
        }

        // Filter: breeder (admin only)
        if ($isAdmin && $request->filled('breeder_id')) {
            $query->where('breeder_id', $request->breeder_id);
        }

        // Filter: customer (admin only)
        if ($isAdmin && $request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter: description / jenis koi
        if ($request->filled('jenis_koi_id')) {
            $query->where('jenis_koi_id', $request->jenis_koi_id);
        }

        $perPage   = in_array((int) $request->per_page, [25, 50, 100]) ? (int) $request->per_page : 50;
        $kois      = $query->orderByDesc('sale_tanggal')->paginate($perPage)->withQueryString();
        $breeders  = Breeder::select(['id', 'name_breeder'])->orderBy('name_breeder')->get();
        $customers = $isAdmin
            ? User::select(['id', 'name'])->where('role_id', 2)->orderBy('name')->get()
            : collect();

        return Inertia::render('Admin/RekapTransaksi/Index', [
            'kois'      => $kois,
            'breeders'  => $breeders,
            'customers' => $customers,
            'isAdmin'   => $isAdmin,
            'filters'   => $request->only(['status', 'code', 'invoice', 'date_from', 'date_to', 'breeder_id', 'customer_id', 'jenis_koi_id', 'per_page']),
        ]);
    }
}
