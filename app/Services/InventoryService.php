<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Nghiệp vụ kho (mục 4.8): nhập kho và điều chỉnh kiểm kê.
 * Xuất kho theo đơn hàng / hoàn kho khi hủy đơn nằm trong OrderService.
 *
 * Nguyên tắc: mỗi lần tồn kho thay đổi đều đi kèm một dòng inventory_transactions,
 * cả hai được ghi trong cùng một DB::transaction.
 */
class InventoryService
{
    /**
     * Ghi phiếu nhập kho gồm nhiều dòng sản phẩm.
     *
     * @param  array<int, array{product_id:int, quantity:int}>  $items
     * @return int tổng số lượng đã nhập
     */
    public function receive(array $items, int $userId, ?string $note = null): int
    {
        return DB::transaction(function () use ($items, $userId, $note) {
            $products = Product::whereIn('id', array_column($items, 'product_id'))
                ->lockForUpdate()->get()->keyBy('id');

            $errors = [];
            foreach ($items as $i => $item) {
                if ($products[$item['product_id']]->status !== 'active') {
                    $errors["items.$i.product_id"] = "{$products[$item['product_id']]->product_name} đã ngừng kinh doanh, không nhập kho.";
                }
            }
            if ($errors) {
                throw ValidationException::withMessages($errors);
            }

            $total = 0;
            foreach ($items as $item) {
                $p = $products[$item['product_id']];
                $p->increment('stock_quantity', $item['quantity']);           // Tồn mới = Tồn cũ + SL nhập

                InventoryTransaction::create([
                    'product_id'       => $p->id,
                    'user_id'          => $userId,
                    'order_id'         => null,
                    'transaction_type' => InventoryTransaction::TYPE_IN,
                    'quantity'         => $item['quantity'],
                    'note'             => 'Nhập kho' . ($note ? ": {$note}" : ''),
                ]);
                $total += $item['quantity'];
            }

            return $total;
        });
    }

    /**
     * Điều chỉnh tồn kho theo số lượng kiểm kê thực tế.
     * Chênh lệch dương -> IN, chênh lệch âm -> OUT.
     *
     * @return int chênh lệch (thực tế - hệ thống)
     */
    public function adjust(Product $product, int $actual, string $reason, int $userId): int
    {
        return DB::transaction(function () use ($product, $actual, $reason, $userId) {
            $p    = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $diff = $actual - $p->stock_quantity;

            if ($diff === 0) {
                throw new DomainException('Số lượng thực tế bằng tồn kho trên hệ thống, không cần điều chỉnh.');
            }

            $diff > 0
                ? $p->increment('stock_quantity', $diff)
                : $p->decrement('stock_quantity', -$diff);

            InventoryTransaction::create([
                'product_id'       => $p->id,
                'user_id'          => $userId,
                'order_id'         => null,
                'transaction_type' => $diff > 0 ? InventoryTransaction::TYPE_IN : InventoryTransaction::TYPE_OUT,
                'quantity'         => abs($diff),
                'note'             => "Điều chỉnh kiểm kê (hệ thống " . ($actual - $diff) . ", thực tế {$actual}): {$reason}",
            ]);

            return $diff;
        });
    }
}
