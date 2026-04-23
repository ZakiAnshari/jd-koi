<?php

namespace Database\Seeders;

use App\Models\JenisKoi;
use Illuminate\Database\Seeder;

class JenisKoiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jenisKoiData = [
            ['nama_jenis' => 'GR-Kohaku', 'deskripsi' => null],
            ['nama_jenis' => 'GR-Showa', 'deskripsi' => null],
            ['nama_jenis' => 'GR-Shiro', 'deskripsi' => null],
            ['nama_jenis' => 'GR-Ochiba', 'deskripsi' => null],
            ['nama_jenis' => 'GR-Chagoi', 'deskripsi' => null],
            ['nama_jenis' => 'GR-Goshiki', 'deskripsi' => null],
            ['nama_jenis' => 'D-Mix', 'deskripsi' => null],
            ['nama_jenis' => 'Nigata Sale', 'deskripsi' => null],
            ['nama_jenis' => 'D-Sanke', 'deskripsi' => null],
            ['nama_jenis' => 'D-Showa', 'deskripsi' => null],
            ['nama_jenis' => 'D-Hikari', 'deskripsi' => null],
            ['nama_jenis' => 'Cron', 'deskripsi' => null],
            ['nama_jenis' => 'Beni', 'deskripsi' => null],
            ['nama_jenis' => 'Ochiba', 'deskripsi' => null],
            ['nama_jenis' => 'Chagoi', 'deskripsi' => null],
            ['nama_jenis' => 'Entry Charge', 'deskripsi' => null],
            ['nama_jenis' => 'Goods', 'deskripsi' => null],
            ['nama_jenis' => 'Medicine', 'deskripsi' => null],
            ['nama_jenis' => 'Kohaku', 'deskripsi' => null],
            ['nama_jenis' => 'Showa', 'deskripsi' => null],
            ['nama_jenis' => 'Tosai Mix', 'deskripsi' => null],
            ['nama_jenis' => 'Sanke', 'deskripsi' => null],
            ['nama_jenis' => 'Hikari Kawari', 'deskripsi' => null],
            ['nama_jenis' => 'Nisai Mix', 'deskripsi' => null],
            ['nama_jenis' => 'Tancho', 'deskripsi' => null],
            ['nama_jenis' => 'Kikokuryu', 'deskripsi' => null],
            ['nama_jenis' => 'Kawari Mix', 'deskripsi' => null],
            ['nama_jenis' => 'karasu', 'deskripsi' => null],
            ['nama_jenis' => 'Goshiki', 'deskripsi' => null],
            ['nama_jenis' => 'GR-Sanke', 'deskripsi' => null],
            ['nama_jenis' => 'Koromo', 'deskripsi' => null],
            ['nama_jenis' => 'Botan', 'deskripsi' => null],
            ['nama_jenis' => 'Asagi', 'deskripsi' => null],
            ['nama_jenis' => 'Karashi', 'deskripsi' => null],
            ['nama_jenis' => 'KIn Showa', 'deskripsi' => null],
            ['nama_jenis' => 'Hikari Muji', 'deskripsi' => null],
            ['nama_jenis' => 'Kigoi', 'deskripsi' => null],
            ['nama_jenis' => 'Azukari Cost', 'deskripsi' => null],
            ['nama_jenis' => 'Shusui', 'deskripsi' => null],
            ['nama_jenis' => 'Tancho sanke', 'deskripsi' => null],
            ['nama_jenis' => 'membership', 'deskripsi' => null],
            ['nama_jenis' => 'Com', 'deskripsi' => null],
            ['nama_jenis' => 'certificate', 'deskripsi' => null],
            ['nama_jenis' => 'shipping cost', 'deskripsi' => null],
            ['nama_jenis' => 'Sansai mix', 'deskripsi' => null],
            ['nama_jenis' => 'Aka matsuba', 'deskripsi' => null],
            ['nama_jenis' => 'Hiutsuri', 'deskripsi' => null],
            ['nama_jenis' => 'Ogon', 'deskripsi' => null],
            ['nama_jenis' => 'Gold Fish', 'deskripsi' => null],
            ['nama_jenis' => 'D-Kohaku', 'deskripsi' => null],
            ['nama_jenis' => 'Gosanke mix', 'deskripsi' => null],
            ['nama_jenis' => 'GR Mix', 'deskripsi' => null],
            ['nama_jenis' => 'Hikari Mix', 'deskripsi' => null],
            ['nama_jenis' => 'Shiroutsuri', 'deskripsi' => null],
        ];

        foreach ($jenisKoiData as $data) {
            JenisKoi::create($data);
        }
    }
}
