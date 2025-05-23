<?php

use App\Enums\EnumVersionType;
use App\Enums\PaymentGateway;
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
        Schema::table('event_variables', function (Blueprint $table) {
            // Faspay
            $table->text('faspay_merchant_name')->nullable();
            $table->text('faspay_merchant_id')->nullable();
            $table->text('faspay_user_id')->nullable();
            $table->text('faspay_password')->nullable();
            $table->text('faspay_signature')->nullable();
            $table->boolean('faspay_is_production')->default(false);
            $table->boolean('faspay_use_novatix')->default(false);

            // Tripay
            $table->text('tripay_api_key_dev')->nullable();
            $table->text('tripay_private_key_dev')->nullable();
            $table->text('tripay_api_key_prod')->nullable();
            $table->text('tripay_private_key_prod')->nullable();
            $table->boolean('tripay_is_production')->default(false);
            $table->boolean('tripay_use_novatix')->default(false);

            // ENUM
            $table->enum('payment_gateway', PaymentGateway::getByVersion('v1', EnumVersionType::ARRAY))->default(PaymentGateway::getByVersion('v1'))->after('privacy_policy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_variables', function (Blueprint $table) {
            // Faspay
            $table->dropColumn([
                'faspay_merchant_name',
                'faspay_merchant_id',
                'faspay_user_id',
                'faspay_password',
                'faspay_signature',
                'faspay_is_production',
                'faspay_use_novatix',
            ]);

            // Tripay
            $table->dropColumn([
                'tripay_api_key_dev',
                'tripay_private_key_dev',
                'tripay_api_key_prod',
                'tripay_private_key_prod',
                'tripay_is_production',
                'tripay_use_novatix',
            ]);

            // ENUM
            $table->dropColumn('payment_gateway');
        });
    }
};
