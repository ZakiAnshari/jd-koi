<?php

namespace App\Http\Controllers;

use App\Models\KatalogKoi;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KatalogKoiController extends Controller
{
    public function index()
    {
        $katalogs = KatalogKoi::with(['koi.breeder', 'pembelian'])
            ->paginate(10);

        $pembelians = Purchase::whereDoesntHave('katalogKoi')
            ->with(['koi.breeder'])
            ->get();

        return Inertia::render('Admin/KatalogKoi/Index', [
            'katalogs' => $katalogs,
            'pembelians' => $pembelians,
        ]);
    }

    public function create()
    {
        return redirect()->route('admin.katalog-koi.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal'      => 'required|date',
            'koi_id'       => 'required|exists:kois,id',
            'pembelian_id' => 'required|exists:purchases,id|unique:katalog_kois',
            'qty'          => 'required|integer|min:1',
            'sell_jpy'     => 'required|numeric|min:0',
            'kurs'         => 'required|numeric|min:0',
            'sell_idr'     => 'required|numeric|min:0',
            'stock_jpy'    => 'required|numeric|min:0',
            'stock_idr'    => 'required|numeric|min:0',
            'fee'          => 'nullable|numeric|min:0',
        ]);

        $validated['fee'] = $validated['fee'] ?? 0;

        KatalogKoi::create($validated);

        return redirect()->route('admin.katalog-koi.index')
            ->with('success', 'Katalog Koi berhasil ditambahkan.');
    }

    public function show(KatalogKoi $katalogKoi)
    {
        return redirect()->route('admin.katalog-koi.index');
    }

    public function edit(KatalogKoi $katalogKoi)
    {
        return redirect()->route('admin.katalog-koi.index');
    }

    public function update(Request $request, KatalogKoi $katalogKoi)
    {
        $validated = $request->validate([
            'tanggal'      => 'required|date',
            'koi_id'       => 'required|exists:kois,id',
            'pembelian_id' => 'required|exists:purchases,id',
            'qty'          => 'required|integer|min:1',
            'sell_jpy'     => 'required|numeric|min:0',
            'kurs'         => 'required|numeric|min:0',
            'sell_idr'     => 'required|numeric|min:0',
            'stock_jpy'    => 'required|numeric|min:0',
            'stock_idr'    => 'required|numeric|min:0',
            'fee'          => 'nullable|numeric|min:0',
        ]);

        $validated['fee'] = $validated['fee'] ?? 0;

        $katalogKoi->update($validated);

        return redirect()->route('admin.katalog-koi.index')
            ->with('success', 'Katalog Koi berhasil diperbarui.');
    }

    public function destroy(KatalogKoi $katalogKoi)
    {
        $katalogKoi->delete();

        return redirect()->route('admin.katalog-koi.index')
            ->with('success', 'Katalog Koi berhasil dihapus.');
    }
}
