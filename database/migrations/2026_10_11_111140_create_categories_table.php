<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng categories – Danh mục sản phẩm (mục 4.5.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();                                        // BIGINT, PK, AUTO_INCREMENT
            $table->string('category_name', 100);                // Tên danh mục
            $table->text('description')->nullable();             // Mô tả danh mục
            $table->string('status', 20)->default('active');     // active | inactive
            $table->timestamps();                                // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
