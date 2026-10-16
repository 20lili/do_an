<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng products – Sản phẩm (mục 4.5.3, Bảng 5.4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();                                              // BIGINT, PK, AUTO_INCREMENT
            $table->foreignId('category_id')                           // FK -> categories.id
                  ->constrained('categories')
                  ->restrictOnDelete();                                // không xóa danh mục đang có sản phẩm
            $table->string('product_code', 50)->unique();              // Mã sản phẩm, duy nhất
            $table->string('product_name', 150);                       // Tên sản phẩm
            $table->string('unit', 30);                                // Đơn vị tính
            $table->decimal('import_price', 12, 2);                    // Giá nhập
            $table->decimal('selling_price', 12, 2);                   // Giá bán
            $table->unsignedInteger('stock_quantity')->default(0);     // Tồn kho hiện tại, không âm
            $table->string('image', 255)->nullable();                  // Đường dẫn hình ảnh
            $table->text('description')->nullable();                   // Mô tả
            $table->string('status', 20)->default('active');           // active | inactive
            $table->timestamps();

            $table->index('product_name');                             // tăng tốc tìm kiếm theo tên
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
