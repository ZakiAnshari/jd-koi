<?php

namespace App\Http\Controllers;

use App\Models\Breeder;
use App\Models\Koi;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\InvoiceExport;
use Maatwebsite\Excel\Facades\Excel;

class RekapTransaksiController extends Controller
{
    public function show(Request $request, Koi $koi): Response
    {
        $user = $request->user();

        // Customer hanya boleh lihat koi miliknya
        if ($user->role_id !== 1 && $koi->customer_id !== $user->id) {
            abort(403);
        }

        $koi->load(['breeder:id,name_breeder', 'jenisKoi:id,nama_jenis', 'images', 'customer:id,name']);

        $batchMates = collect();
        if ($user->role_id === 1 && $koi->shipping_batch) {
            $batchMates = Koi::where('shipping_batch', $koi->shipping_batch)
                ->where('id', '!=', $koi->id)
                ->with('customer:id,name')
                ->get(['id', 'code', 'customer_id', 'sale_qty', 'shipping_batch']);
        }

        return Inertia::render('Admin/RekapTransaksi/Show', [
            'koi'        => $koi,
            'isAdmin'    => $user->role_id === 1,
            'batchMates' => $batchMates,
        ]);
    }

    public function index(Request $request): Response
    {
        $user    = $request->user();
        $isAdmin = $user->role_id === 1;

        $query = Koi::with([
            'breeder:id,name_breeder',
            'jenisKoi:id,nama_jenis',
            'customer:id,name',
            'images:id,koi_id,image_path,sort_order',
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

        // Filter: breeder
        if ($request->filled('breeder_id')) {
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
        $kois      = $query->orderByDesc('sale_tanggal')->orderByDesc('id')->paginate($perPage)->withQueryString();
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

    public function exportPdf(Request $request)
    {
        $user  = $request->user();
        $isAdmin = $user->role_id === 1;
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada item yang dipilih');
        }

        $query = Koi::whereIn('id', $ids)->with([
            'breeder:id,name_breeder',
            'jenisKoi:id,nama_jenis',
            'images',
            'customer:id,name',
        ]);

        // Customer hanya bisa export miliknya sendiri
        if (!$isAdmin) {
            $query->where('customer_id', $user->id);
        }

        $kois = $query->orderBy('sale_tanggal')->get();

        if ($kois->isEmpty()) {
            return back()->with('error', 'Data tidak ditemukan atau akses ditolak');
        }

        $pdf = Pdf::loadView('pdf.rekap-transaksi', [
            'kois' => $kois,
            'isAdmin' => $isAdmin,
            'user' => $user,
        ])->setPaper('A4', 'portrait')
        ->setOption('enable_php', true)
        ->setOption('dpi', 150)
        ->setOption('margin-top', 20)
        ->setOption('margin-right', 20)
        ->setOption('margin-bottom', 20)
        ->setOption('margin-left', 20);

        $firstKoi     = $kois->first();
        $customerName = $firstKoi->customer?->name ?? 'Pelanggan';
        $invoiceNo    = $firstKoi->sale_invoice ?? now()->format('Ymd-His');
        $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $customerName);

        return $pdf->download($safeFilename . ' - ' . $invoiceNo . '.pdf');
    }

    public function exportInvoicePdf(Request $request)
    {
        $invoiceNumber = $request->query('invoice');

        if (!$invoiceNumber) {
            abort(400, 'No invoice number provided');
        }

        $user    = $request->user();
        $isAdmin = $user->role_id === 1;

        $query = Koi::where('sale_invoice', $invoiceNumber)
            ->with(['breeder:id,name_breeder', 'jenisKoi:id,nama_jenis', 'images', 'customer:id,name']);

        if (!$isAdmin) {
            $query->where('customer_id', $user->id);
        }

        $kois = $query->orderBy('sale_tanggal')->get();

        if ($kois->isEmpty()) {
            abort(404, 'Invoice tidak ditemukan');
        }

        $customerName  = $kois->first()->customer?->name ?? 'Pelanggan';
        $grandTotal    = $kois->sum(fn($k) => ($k->unit_price ?? 0) * ($k->sale_qty ?? 1) * ($k->kurs ?? 0) ?: ($k->sell_idr ?? 0));
        $totalFee      = $kois->sum(fn($k) => $k->fee ?? 0);
        $totalShipping = $kois->sum(function ($k) {
            if (!$k->shipping_cost || !$k->shipping_qty) return 0;
            return round(floatval($k->shipping_cost) / intval($k->shipping_qty) * intval($k->sale_qty ?? 1));
        });
        // Shipping ditampilkan per sub-row, tidak ditambah ke grand total
        $pdf = Pdf::loadView('pdf.invoice', [
            'kois'           => $kois,
            'invoiceNumber'  => $invoiceNumber,
            'customerName'   => $customerName,
            'grandTotal'     => $grandTotal,
            'totalFee'       => $totalFee,
            'totalShipping'  => $totalShipping,
            'isAdmin'        => $isAdmin,
            'paymentName'    => 'Kurniadi Prakasa Pieth',
            'paymentBank'    => 'BCA / Mandiri / GoPay / OVO',
            'paymentAccount' => 'BCA: 2533 2222 34 | Mandiri: 1690009 44789',
        ])->setPaper('A4', 'portrait')
          ->setOption('enable_php', true)
          ->setOption('dpi', 150);

        $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $customerName);
        return $pdf->download($safeFilename . ' - ' . $invoiceNumber . '.pdf');
    }

    public function exportInvoiceExcel(Request $request)
    {
        $invoiceNumber = $request->query('invoice');

        if (!$invoiceNumber) abort(400, 'No invoice number provided');

        $user    = $request->user();
        $isAdmin = $user->role_id === 1;

        $query = Koi::where('sale_invoice', $invoiceNumber)
            ->with(['breeder:id,name_breeder', 'images', 'customer:id,name']);

        if (!$isAdmin) $query->where('customer_id', $user->id);

        $kois = $query->orderBy('sale_tanggal')->get();

        if ($kois->isEmpty()) abort(404, 'Invoice tidak ditemukan');

        $customerName  = $kois->first()->customer?->name ?? 'Pelanggan';
        $grandTotal    = $kois->sum(fn($k) => ($k->unit_price ?? 0) * ($k->sale_qty ?? 1) * ($k->kurs ?? 0) ?: ($k->sell_idr ?? 0));
        $totalFee      = $kois->sum(fn($k) => $k->fee ?? 0);
        $totalShipping = $kois->sum(function ($k) {
            if (!$k->shipping_cost || !$k->shipping_qty) return 0;
            return round(floatval($k->shipping_cost) / intval($k->shipping_qty) * intval($k->sale_qty ?? 1));
        });
        // Shipping sudah ditampilkan per sub-row, tidak ditambah ke grand total Excel

        $safeFilename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $customerName);

        return Excel::download(
            new InvoiceExport($invoiceNumber, $kois, $customerName, (float) $grandTotal, (float) $totalFee, (float) $totalShipping, $isAdmin),
            $safeFilename . ' - ' . $invoiceNumber . '.xlsx'
        );
    }
}
