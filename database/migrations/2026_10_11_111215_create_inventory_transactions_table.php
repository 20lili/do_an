<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng inventory_transactions – Lịch sử giao dịch kho (mục 4.5.7).
 * Mỗi lần tồn kho thay đổi (nhập kho, xuất kho theo đơn, hoàn kho khi hủy đơn)
 * đều được ghi một dòng tại đây.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();      // người thực hiện
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete(); // đơn liên quan (nếu có)
            $table->string('transaction_type', 20);         // IN: nhập/hoàn kho, OUT: xuất kho
            $table->unsignedInteger('quantity');            // luôn > 0, chiều tăng/giảm xác định bởi transaction_type
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();    // thời điểm phát sinh

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
