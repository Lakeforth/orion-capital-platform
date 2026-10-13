<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('campaigns', 'tracking_script')) {
            return;
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->text('tracking_script')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('campaigns', 'tracking_script')) {
            return;
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('tracking_script');
        });
    }
};
