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
        Schema::create('video_image_stories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_id');
            $table->foreign('file_id')
                ->references('id')->on('files')->onUpdate('cascade');
            $table->integer('preview_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')->on('users')->onUpdate('cascade');
            $table->string("name")->nullable();    
            $table->string("description")->nullable(); 
            $table->text("link")->nullable();
            $table->boolean("is_story");
            $table->boolean("state")->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_image_stories');
    }
};
