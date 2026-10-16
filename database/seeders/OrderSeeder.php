<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

/**
 * 30 đơn hàng mẫu (lịch sử mua hàng) để module khách hàng có số liệu.
 *
 * Các đơn được ghi trực tiếp vào CSDL (không gọi OrderService), nên KHÔNG trừ lại tồn kho:
 * stock_quantity trong ProductSeeder được xem là tồn kho SAU các đơn này.
 * Đơn đã xác nhận / hoàn thành được ghi kèm phiếu xuất kho (OUT);
 * InventorySeeder ghi phiếu "tồn đầu kỳ" để sổ kho khớp với tồn kho hiện tại.
 */
class OrderSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(2026); // cố định ngẫu nhiên: chạy lại seed luôn ra cùng một bộ dữ liệu

        // Nhân viên lập đơn: tài khoản "nhanvien" do UserSeeder tạo (mục 5.8)
        $userId = DB::table('users')->where('username', 'nhanvien')->value('id')
            ?? DB::table('users')->value('id');

        $products  = DB::table('products')->where('status', 'active')->get(['id', 'selling_price'])->all();
        $customers = DB::table('customers')->orderBy('id')->get(['id', 'created_at'])->keyBy('id');

        // Khách mua nhiều lần xuất hiện nhiều lần trong danh sách (khách 15–20 là khách mới, chưa mua)
        $buyers = [1, 1, 1, 1, 2, 2, 2, 3, 4, 4, 4, 5, 5, 5, 6, 7, 7, 8, 8, 9, 10, 10, 10, 11, 12, 13, 13, 14, 14, 14];

        // Tạo ngày đặt hàng hợp lệ (sau ngày khách được tạo, trước hôm nay)
        $orders = [];
        foreach ($buyers as $customerId) {
            $from = \Illuminate\Support\Carbon::parse($customers[$customerId]->created_at)->addDay();
            $days = max(1, (int) $from->diffInDays(now()));
            $date = $from->copy()->addDays(mt_rand(0, $days - 1))->setTime(mt_rand(8, 20), mt_rand(0, 59));
            $orders[] = ['customer_id' => $customerId, 'order_date' => $date];
        }
        usort($orders, fn ($a, $b) => $a['order_date'] <=> $b['order_date']);

        foreach ($orders as $i => $o) {
            $ageDays = (int) $o['order_date']->diffInDays(now());

            // Đơn cũ: phần lớn hoàn thành, một số bị hủy; đơn gần đây: chờ xác nhận / đã xác nhận
            $status = match (true) {
                $i === count($orders) - 1 => 'pending',   // đơn mới nhất luôn chờ xác nhận
                $ageDays <= 2 => mt_rand(0, 1) ? 'pending' : 'confirmed',
                $ageDays <= 6 => 'confirmed',
                default       => mt_rand(1, 10) <= 2 ? 'cancelled' : 'completed',
            };

            // 1–4 mặt hàng khác nhau, mỗi mặt hàng 1–3 đơn vị
            $picked = (array) array_rand($products, mt_rand(1, 4));
            $lines  = [];
            $total  = 0;
            foreach ($picked as $idx) {
                $p   = $products[$idx];
                $qty = mt_rand(1, 3);
                $sub = $qty * (float) $p->selling_price;
                $total  += $sub;
                $lines[] = ['product_id' => $p->id, 'quantity' => $qty, 'unit_price' => $p->selling_price, 'subtotal' => $sub];
            }

            $orderId = DB::table('orders')->insertGetId([
                'order_code'   => 'DH' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'customer_id'  => $o['customer_id'],
                'user_id'      => $userId,
                'order_date'   => $o['order_date'],
                'status'       => $status,
                'total_amount' => $total,
                'note'         => $status === 'cancelled' ? 'Khách đổi ý, hủy đơn.' : null,
                'created_at'   => $o['order_date'],
                'updated_at'   => $o['order_date'],
            ]);

            foreach ($lines as &$line) {
                $line += ['order_id' => $orderId, 'created_at' => $o['order_date'], 'updated_at' => $o['order_date']];
            }
            unset($line);
            DB::table('order_details')->insert($lines);

            // Đơn đã xác nhận / hoàn thành => đã xuất kho: ghi phiếu xuất (OUT) tương ứng.
            // (Đơn bị hủy trong dữ liệu mẫu được xem là hủy trước khi xác nhận nên không có giao dịch kho.)
            if (in_array($status, ['confirmed', 'completed'], true)) {
                $code = 'DH' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
                DB::table('inventory_transactions')->insert(array_map(fn ($l) => [
                    'product_id'       => $l['product_id'],
                    'user_id'          => $userId,
                    'order_id'         => $orderId,
                    'transaction_type' => 'OUT',
                    'quantity'         => $l['quantity'],
                    'note'             => "Xuất kho theo đơn {$code}",
                    'created_at'       => $o['order_date']->copy()->addMinutes(30),
                ], $lines));
            }
        }
    }
}
