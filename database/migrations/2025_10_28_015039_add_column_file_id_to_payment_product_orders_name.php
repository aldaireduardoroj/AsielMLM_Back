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
        Schema::table('payment_product_orders', function (Blueprint $table) {
            //
            $table->integer('file')->after('token')->nullable();
        });

        Schema::table('payment_logs', function (Blueprint $table) {
            //
            $table->integer('file_id')->after('confirm')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_product_orders', function (Blueprint $table) {
            //
            $table->dropColumn('file');
        });

        Schema::table('payment_logs', function (Blueprint $table) {
            //
            $table->dropColumn('file_id');
        });
    }
};
