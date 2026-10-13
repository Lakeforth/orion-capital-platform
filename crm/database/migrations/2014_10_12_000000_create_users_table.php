<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('account_id')->nullable();

            $table->uuid('manager_id')->nullable();
            $table->uuid('referred_by')->nullable();
            $table->unsignedBigInteger('currency_id')->default(1);

            $table->string('first_name')->nullable();;
            $table->string('last_name')->nullable();;
            $table->string('avatar')->nullable();;
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('username')->nullable();

            $table->boolean('require_otp')->default(true);

            $table->boolean('is_online')->default(false)->comment('Indicates if the user is currently online');
            $table->timestamp('last_seen')->nullable();

            $table->decimal('total_deposit', 20)->default(0);


            $table->decimal('savings_wallet', 15, 2)->default(0);

            $table->timestamp('welcome_email_at')->nullable();


            $table->string('phone');

            $table->integer('pin')->nullable();
            $table->boolean('approved')->default(true);
            $table->boolean('banned')->default(false);
            $table->string('passport')->nullable();
            $table->boolean('can_email')->default(1);
            $table->string('password');

            $table->decimal('balance',20,2)->default(0);
            $table->decimal('reserved',20,2)->default(0);

            $table->string('date_of_birth')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('address')->nullable();

            $table->boolean('copy_trader')->default(false);

            $table->boolean('funds_privilege')->default(false);

            $table->uuid('created_by')->nullable();
            $table->string('status')->nullable();
            $table->string('source')->nullable();
            $table->string('campaign')->nullable();

            $table->decimal('ftd_amount', 12)->default(0);
            $table->string('ftd')->nullable();


            $table->string('can_trade')->nullable();
            $table->string('pin_key')->nullable();
            $table->string('ftd_date')->nullable();
            $table->string('label')->nullable();
            $table->string('team')->nullable();
            $table->string('brand')->nullable();
            $table->string('desk')->nullable();

            $table->string('login_token')->nullable();


            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');

            $table->unique(['company_id', 'email']);

            $table->index('company_id');

            $table->string('autotrader_status')->default('inactive');
            $table->string('autotrader_type')->nullable();
            $table->string('birthdate')->nullable();
            $table->string('verification_status')->default('pending');
            $table->foreignUuid('plan_id')->nullable()
                ->references('id')->on('account_plans')->nullOnDelete()->cascadeOnUpdate();


            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

    }



    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
