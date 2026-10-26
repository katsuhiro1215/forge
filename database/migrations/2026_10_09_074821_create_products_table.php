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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('type')->default('theme'); // theme|block|ui|dashboard
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->string('status')->default('draft'); // draft|published
            $table->string('demo_path')->nullable(); // demo.forge.smartsprouts.jp 配下のパス（例: ork）
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
