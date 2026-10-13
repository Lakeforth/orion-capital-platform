<?php

namespace Database\Seeders;

use App\Models\AssetType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssetTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'Stocks',
            'Crypto',
            'Forex',
            'Commodities',
            'Indices',
        ];



        if(AssetType::count() < 2){
            foreach ($data as $item) {
                if(!AssetType::whereName($item)->first()){
                    AssetType::create([
                        'name' => $item,
                        'slug' => Str::slug($item),
                    ]);
                }
            }
        }
    }
}
