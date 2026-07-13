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

        Schema::table('products', function (Blueprint $table) {
            //
            $table->boolean('feature')->default(false);
            $table->string('sub_title')->nullable();
        });

        Schema::create('product_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid("product_id");
            $table->string('title');
            $table->text('description');
            $table->timestamps();
        });

        Schema::table('product_descriptions', function ( $table) {
            $table->foreign('product_id')
                ->references('id')->on('products')->onUpdate('cascade');

        });

        Schema::create('product_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid("product_id");
            $table->string('title');
            $table->text('svg');
            $table->timestamps();
        });

        Schema::table('product_tags', function ( $table) {
            $table->foreign('product_id')
                ->references('id')->on('products')->onUpdate('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
            $table->dropColumn('feature');
            $table->dropColumn('sub_title');
        });
    }
};
