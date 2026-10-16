<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Ghi phiếu nhập "Tồn đầu kỳ" cho từng sản phẩm, để sổ kho cân đối:
 *     Tồn hiện tại = Σ nhập (IN) − Σ xuất (OUT)
 * Số tồn đầu kỳ = tồn hiện tại + số lượng đã xuất theo các đơn mẫu.
 * Chạy SAU OrderSeeder.
 */
class InventorySeeder extends Seeder
{
    public function run(): void
    {
        // Phiếu tồn đầu kỳ do quản trị viên lập (tài khoản "admin" – UserSeeder, mục 5.8)
        $userId = DB::table('users')->where('username', 'admin')->value('id')
            ?? DB::table('users')->value('id');

        $out = DB::table('inventory_transactions')
            ->where('transaction_type', 'OUT')
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->pluck('qty', 'product_id');

        $openingDate = now()->subDays(125)->setTime(8, 0);
        $rows = [];

        foreach (DB::table('products')->get(['id', 'stock_quantity']) as $p) {
            $opening = (int) $p->stock_quantity + (int) ($out[$p->id] ?? 0);
            if ($opening <= 0) {
                continue;
            }
            $rows[] = [
                'product_id'       => $p->id,
                'user_id'          => $userId,
                'order_id'         => null,
                'transaction_type' => 'IN',
                'quantity'         => $opening,
                'note'             => 'Tồn đầu kỳ khi đưa hệ thống vào sử dụng',
                'created_at'       => $openingDate,
            ];
        }

        DB::table('inventory_transactions')->insert($rows);
    }
}
