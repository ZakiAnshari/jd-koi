<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerExport implements FromCollection, WithHeadings, WithMapping
{
    private int $no = 0;

    public function collection()
    {
        return User::where('role_id', 2)
            ->select(['id', 'name', 'email', 'no_telepon', 'alamat', 'created_at'])
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return ['No', 'Nama', 'Email', 'No Telepon', 'Alamat', 'Tanggal Daftar'];
    }

    public function map($row): array
    {
        return [
            ++$this->no,
            $row->name,
            $row->email,
            $row->no_telepon ?? '-',
            $row->alamat ?? '-',
            \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i'),
        ];
    }
}
