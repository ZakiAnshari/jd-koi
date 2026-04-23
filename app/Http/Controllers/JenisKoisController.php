<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\JenisKoi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class JenisKoisController extends Controller
{
    public function index(): Response
    {
        $jenisKois = JenisKoi::select(['id', 'nama_jenis', 'deskripsi', 'created_at'])
            ->orderBy('nama_jenis')
            ->get();

        return Inertia::render('MasterData/JenisKoi/Index', [
            'jenisKois' => $jenisKois,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_jenis' => 'required|string|max:255|unique:jenis_kois,nama_jenis',
            'deskripsi'  => 'nullable|string',
        ], [
            'nama_jenis.unique' => 'Jenis Koi ":input" sudah ada. Tidak bisa menambahkan data yang sama.',
        ]);

        $jenisKoi = JenisKoi::create($validated);
        ActivityLog::log('create', "Menambahkan data Jenis Koi \"{$jenisKoi->nama_jenis}\" (ID: {$jenisKoi->id})", 'jenis_kois');

        return to_route('jenis-kois.index');
    }

    public function update(Request $request, JenisKoi $jenisKoi)
    {
        $validated = $request->validate([
            'nama_jenis' => [
                'required', 'string', 'max:255',
                Rule::unique('jenis_kois', 'nama_jenis')->ignore($jenisKoi->id),
            ],
            'deskripsi' => 'nullable|string',
        ], [
            'nama_jenis.unique' => 'Jenis Koi ":input" sudah ada. Tidak bisa menggunakan nama yang sama.',
        ]);

        $jenisKoi->fill($validated)->save();
        ActivityLog::log('update', "Mengubah data Jenis Koi \"{$jenisKoi->nama_jenis}\" (ID: {$jenisKoi->id})", 'jenis_kois');

        return to_route('jenis-kois.index');
    }

    public function destroy(JenisKoi $jenisKoi)
    {
        $name = $jenisKoi->nama_jenis;
        $id   = $jenisKoi->id;
        $jenisKoi->delete();
        ActivityLog::log('delete', "Menghapus data Jenis Koi \"{$name}\" (ID: {$id})", 'jenis_kois');

        return to_route('jenis-kois.index');
    }
}
