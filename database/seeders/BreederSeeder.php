<?php

namespace Database\Seeders;

use App\Models\Breeder;
use Illuminate\Database\Seeder;

class BreederSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $breederData = [
            ['name_breeder' => 'Aoki'],
            ['name_breeder' => 'Beppu'],
            ['name_breeder' => 'Chogoro'],
            ['name_breeder' => 'Dainichi'],
            ['name_breeder' => 'Dainichi Auction'],
            ['name_breeder' => 'Dainichi Toyota'],
            ['name_breeder' => 'Dainichi Toyota Auction'],
            ['name_breeder' => 'Hoshikin'],
            ['name_breeder' => 'Hosokai'],
            ['name_breeder' => 'Ikarashi'],
            ['name_breeder' => 'ISA'],
            ['name_breeder' => 'Izumiya'],
            ['name_breeder' => 'Izumiya Event'],
            ['name_breeder' => 'Kaneko'],
            ['name_breeder' => 'Kanno'],
            ['name_breeder' => 'Kawakami'],
            ['name_breeder' => 'Konishi'],
            ['name_breeder' => 'Maruchiku'],
            ['name_breeder' => 'Marudo'],
            ['name_breeder' => 'Maruhiro'],
            ['name_breeder' => 'Marukyu'],
            ['name_breeder' => 'Marusaka'],
            ['name_breeder' => 'Marusei'],
            ['name_breeder' => 'Marushin'],
            ['name_breeder' => 'Maruyama'],
            ['name_breeder' => 'Matsue'],
            ['name_breeder' => 'Miyatora'],
            ['name_breeder' => 'Momotaro'],
            ['name_breeder' => 'Momotaro Auction'],
            ['name_breeder' => 'Murata'],
            ['name_breeder' => 'Narita'],
            ['name_breeder' => 'Nogami'],
            ['name_breeder' => 'Odakan Event'],
            ['name_breeder' => 'Ogata'],
            ['name_breeder' => 'Ogata Auction'],
            ['name_breeder' => 'Oishi'],
            ['name_breeder' => 'Okawa'],
            ['name_breeder' => 'Omosako'],
            ['name_breeder' => 'Ooya'],
            ['name_breeder' => 'Otsuka'],
            ['name_breeder' => 'Oyama'],
            ['name_breeder' => 'Ozumi Ikarashi'],
            ['name_breeder' => 'Saito'],
            ['name_breeder' => 'Sakai.Co'],
            ['name_breeder' => 'Sakamaki'],
            ['name_breeder' => 'Sakazume'],
            ['name_breeder' => 'Sekiguchi'],
            ['name_breeder' => 'SFF'],
            ['name_breeder' => 'SFF Auction'],
            ['name_breeder' => 'Shinoda'],
            ['name_breeder' => 'Tamaura'],
            ['name_breeder' => 'Tanaka'],
            ['name_breeder' => 'Taniguchi'],
            ['name_breeder' => 'Tanikoi'],
            ['name_breeder' => 'Torazo'],
            ['name_breeder' => 'Wada'],
            ['name_breeder' => 'Yagenji'],
            ['name_breeder' => 'Yamaguchi'],
            ['name_breeder' => 'Yamaju'],
        ];

        foreach ($breederData as $data) {
            Breeder::create($data);
        }
    }
}
