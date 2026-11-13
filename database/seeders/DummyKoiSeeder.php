<?php

namespace Database\Seeders;

use App\Models\Breeder;
use App\Models\JenisKoi;
use App\Models\Koi;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data dummy: 10 koi berbeda, 1 customer, 1 invoice, 1 batch shipping, fee 5%.
 *
 * Jalankan: php artisan db:seed --class=DummyKoiSeeder
 * Aman dijalankan ulang (data di-update berdasarkan kode koi).
 */
class DummyKoiSeeder extends Seeder
{
    private const INVOICE       = '131126001';
    private const BATCH         = 'SHP-20261113-01';
    private const KURS          = 106.50;   // 1 JPY = Rp 106,50
    private const FEE_PERCENT   = 5;
    private const SHIPPING_COST = 3500000;  // total biaya shipping 1 batch (IDR)

    public function run(): void
    {
        $customer = User::where('role_id', 2)->orderBy('id')->firstOrFail();

        // [kode, nama, breeder, jenis, harga jual JPY/ekor, modal JPY/ekor, umur, ukuran cm, berat kg]
        $items = [
            ['JDK-001', 'Kohaku Tategoi',        'Dainichi', 'GR-Kohaku', 120000,  85000, 2, 45.0, 1.250],
            ['JDK-002', 'Showa Sanshoku Jumbo',  'Dainichi', 'GR-Showa',  150000, 105000, 3, 55.5, 2.100],
            ['JDK-003', 'Taisho Sanke Nisai',    'Aoki',     'D-Sanke',    95000,  65000, 2, 42.0, 1.050],
            ['JDK-004', 'Shiro Utsuri Female',   'Chogoro',  'GR-Shiro',   80000,  55000, 2, 40.5, 0.950],
            ['JDK-005', 'Ochiba Shigure Jumbo',  'Hoshikin', 'GR-Ochiba',  60000,  40000, 3, 50.0, 1.800],
            ['JDK-006', 'Chagoi Big Body',       'Beppu',    'GR-Chagoi',  70000,  48000, 3, 58.0, 2.400],
            ['JDK-007', 'Goshiki Tosai',         'Aoki',     'GR-Goshiki', 45000,  30000, 1, 30.0, 0.450],
            ['JDK-008', 'Kujaku Hikari',         'Hoshikin', 'D-Hikari',   55000,  37000, 2, 38.0, 0.800],
            ['JDK-009', 'Showa Kindai Female',   'Chogoro',  'D-Showa',   110000,  78000, 2, 47.5, 1.400],
            ['JDK-010', 'Benigoi Jumbo',         'Beppu',    'Beni',       65000,  44000, 3, 52.0, 1.950],
        ];

        foreach ($items as [$code, $name, $breederName, $jenisName, $unitJpy, $modalJpy, $age, $size, $weight]) {
            $qty     = 1;
            $sellIdr = round($unitJpy * $qty * self::KURS);
            $fee     = round($sellIdr * self::FEE_PERCENT / 100);

            Koi::updateOrCreate(
                ['code' => $code],
                [
                    'name'         => $name,
                    'breeder_id'   => Breeder::where('name_breeder', $breederName)->value('id') ?? Breeder::min('id'),
                    'jenis_koi_id' => JenisKoi::where('nama_jenis', $jenisName)->value('id'),
                    'description'  => 'Data dummy',
                    'age'          => $age,
                    'size'         => $size,
                    'box_qty'      => 1,
                    'weight'       => $weight,
                    'total_weight' => $weight,

                    // Penjualan
                    'customer_id'  => $customer->id,
                    'sale_tanggal' => '2026-11-13',
                    'sale_qty'     => $qty,
                    'unit_price'   => $unitJpy,
                    'sell_jpy'     => $unitJpy * $qty,
                    'stock_price'  => $modalJpy,
                    'buy_jpy'      => $modalJpy,
                    'kurs'         => self::KURS,
                    'sell_idr'     => $sellIdr,
                    'fee'          => $fee,
                    'sale_status'  => 'shipped',
                    'sale_invoice' => self::INVOICE,
                    'sale_shipped_date' => '2026-11-13',

                    // Shipping: 1 batch berisi 10 ekor → biaya dibagi rata per ekor
                    'shipping_cost'  => self::SHIPPING_COST,
                    'shipping_qty'   => count($items),
                    'shipping_note'  => 'Cargo Jakarta',
                    'shipping_batch' => self::BATCH,
                    'shipping_date'  => '2026-11-13',
                ]
            );
        }

        $this->command?->info("10 koi dummy untuk {$customer->name} (invoice " . self::INVOICE . ') berhasil dibuat.');
    }
}
