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
        Schema::create('report_user_news', function (Blueprint $table) {
            $table->id();
            $table->integer("userId");
            $table->integer("countChildren")->default(0);
            $table->text("codeUsers");
            $table->date("dateSync")->default(date("Y-m-d"));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_user_news');
    }
};
