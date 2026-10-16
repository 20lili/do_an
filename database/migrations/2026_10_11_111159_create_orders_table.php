<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng orders – Đơn hàng (mục 4.5.5).
 * Tạo sẵn ở mục 5.4 để trang chi tiết khách hàng hiển thị được lịch sử đơn hàng;
 * chức năng lập/xử lý đơn sẽ xây dựng ở mục 5.5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 50)->unique();                       // VD: DH001
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete(); // người lập đơn
            $table->dateTime('order_date');
            $table->string('status', 30)->default('pending');                  // pending|confirmed|completed|cancelled
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['status', 'order_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
