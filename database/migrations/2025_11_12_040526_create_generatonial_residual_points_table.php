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
        Schema::create('generatonial_residual_points', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('range_id');
            $table->integer('point_id');
            $table->decimal('points', 18, 4);
            $table->integer('level');
            $table->boolean("state")->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generatonial_residual_points');
    }
};
