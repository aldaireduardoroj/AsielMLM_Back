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
        Schema::create('range_residual_points', function (Blueprint $table) {
            $table->id();
            $table->integer('range_id');
            $table->decimal('level1' , 18, 4);
            $table->decimal('level2' , 18, 4);
            $table->decimal('level3' , 18, 4);
            $table->decimal('level4' , 18, 4);
            $table->decimal('level5' , 18, 4);
            $table->decimal('level6' , 18, 4);
            $table->decimal('level7' , 18, 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('range_residual_points');
    }
};
