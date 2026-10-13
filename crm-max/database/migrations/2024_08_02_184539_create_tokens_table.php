<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        if (!Schema::hasTable('tokens')) {
            Schema::create('tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('user_id')->constrained();
                $table->text('token');
                $table->dateTime('expires_at');
                $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');

                $table->timestamps();

            });
        }



        $role = \App\Models\Role::where('name','affiliate')->first();
        $lead = \App\Models\Role::where('name','lead')->first();

        if(!$role){
            \App\Models\Role::create([
                'name' => 'affiliate',
                'display_name' => 'Affiliate',
                'description' => 'Affiliate',
            ]);
        }

        if(!$lead){
            \App\Models\Role::create([
                'name' => 'lead',
                'display_name' => 'Lead',
                'description' => 'Lead',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};
