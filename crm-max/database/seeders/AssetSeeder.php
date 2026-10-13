<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Services\AssetService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if(Asset::count() < 50){
            $assetService = new AssetService();
            $assetService->syncAssetsFromApi();
        }
    }
}
