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
        Schema::table('users', function (Blueprint $table) {
            //
            $table->string('bank_name')->nullable();
            $table->string('number_account_bank')->nullable();
            $table->string('number_account_interbank')->nullable();
            $table->string('ruc')->nullable();
            $table->string('company_name')->nullable();
            $table->string('paypal')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->dropColumn('bank_name');
            $table->dropColumn('number_account_bank');
            $table->dropColumn('number_account_interbank');
            $table->dropColumn('ruc');
            $table->dropColumn('company_name');
            $table->dropColumn('paypal');
        });
    }
};
