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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('code'); // single|multi
            $table->string('name');
            $table->unsignedInteger('price_jpy'); // 税込
            $table->unsignedSmallInteger('site_limit')->nullable(); // null=無制限
            $table->unsignedSmallInteger('support_months')->default(12);
            $table->unsignedSmallInteger('update_months')->default(12);
            $table->string('status')->default('active'); // active|archived
            $table->timestamps();
            $table->unique(['product_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
