<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng customers – Khách hàng (mục 4.5.4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();                                   // BIGINT, PK, AUTO_INCREMENT
            $table->string('customer_name', 100);           // Họ tên khách hàng
            $table->string('phone', 20)->unique();          // Số điện thoại, không trùng
            $table->string('address', 255)->nullable();     // Địa chỉ
            $table->string('email', 100)->nullable();       // Email
            $table->text('note')->nullable();               // Ghi chú (sở thích, thú cưng đang nuôi…)
            $table->timestamps();

            $table->index('customer_name');                 // tăng tốc tìm kiếm theo tên
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
