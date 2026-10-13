<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->string('server_id')->nullable()->after('domain');
            $table->string('site_id')->nullable()->after('server_id');
        });

        $defaultServerId = (string) config('app.server_id');
        $defaultSiteId = (string) config('app.site_id');

        DB::table('domains')
            ->whereNull('server_id')
            ->update([
                'server_id' => $defaultServerId,
                'site_id' => $defaultSiteId,
            ]);

        Schema::table('domains', function (Blueprint $table) {
            $table->dropUnique('domains_domain_unique');
            $table->unique(['domain', 'server_id'], 'domains_domain_server_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropUnique('domains_domain_server_unique');
            $table->unique('domain');
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn(['server_id', 'site_id']);
        });
    }
};
