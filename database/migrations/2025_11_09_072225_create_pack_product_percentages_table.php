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
        Schema::create('pack_product_percentages', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('pack_id');
            $table->foreignUuid('product_id');
            $table->decimal('discount', 18, 2);
            $table->timestamps();
        });

        Schema::table('pack_product_percentages', function ( $table) {
            $table->foreign('pack_id')
                ->references('id')->on('packs')->onUpdate('cascade');

            $table->foreign('product_id')
                ->references('id')->on('products')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pack_product_percentages');
    }
};
