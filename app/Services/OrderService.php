<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Nghiệp vụ đơn hàng gắn với kho (mục 4.7, 4.8).
 *
 * Quy tắc trừ kho: tồn kho được trừ tại thời điểm XÁC NHẬN đơn hàng.
 *   - Lập đơn         : chỉ kiểm tra tồn kho, chưa trừ kho.
 *   - Xác nhận        : kiểm tra lại tồn kho, trừ kho, ghi giao dịch OUT.
 *   - Hoàn thành      : chỉ đổi trạng thái (hàng đã xuất khi xác nhận).
 *   - Hủy đơn chờ     : chỉ đổi trạng thái, không đụng tới kho.
 *   - Hủy đơn đã xác nhận: cộng lại tồn kho, ghi giao dịch IN (hoàn kho).
 *
 * Mọi thao tác chạy trong DB::transaction và khóa dòng (lockForUpdate)
 * để không trừ kho hai lần và không lệch dữ liệu khi có lỗi giữa chừng.
 */
class OrderService
{
    /**
     * Lập đơn hàng mới ở trạng thái "Chờ xác nhận".
     *
     * @param  array{customer_id:int, note:?string, items:array<int, array{product_id:int, quantity:int}>}  $data
     */
    public function create(array $data, int $userId): Order
    {
        return DB::transaction(function () use ($data, $userId) {
            $items    = collect($data['items']);
            $products = $this->lockProducts($items->pluck('product_id'));

            // Kiểm tra từng dòng: sản phẩm còn kinh doanh và đủ tồn kho
            $errors = [];
            foreach ($items as $i => $item) {
                $p = $products[$item['product_id']];
                if ($p->status !== 'active') {
                    $errors["items.$i.product_id"] = "{$p->product_name} đã ngừng kinh doanh.";
                } elseif ($item['quantity'] > $p->stock_quantity) {
                    $errors["items.$i.quantity"] = $this->shortageMessage($p, $item['quantity']);
                }
            }
            if ($errors) {
                throw ValidationException::withMessages($errors);
            }

            $order = Order::create([
                'order_code'   => 'TMP-' . Str::uuid(),   // đổi thành DHxxx ngay sau khi có id
                'customer_id'  => $data['customer_id'],
                'user_id'      => $userId,
                'order_date'   => now(),
                'status'       => Order::PENDING,
                'total_amount' => 0,
                'note'         => $data['note'] ?? null,
            ]);

            $total = 0;
            foreach ($items as $item) {
                $p        = $products[$item['product_id']];
                $subtotal = $item['quantity'] * (float) $p->selling_price;   // Thành tiền = SL × Đơn giá
                $total   += $subtotal;

                $order->details()->create([
                    'product_id' => $p->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $p->selling_price,                          // lưu giá tại thời điểm bán
                    'subtotal'   => $subtotal,
                ]);
            }

            $order->update([
                'order_code'   => Order::codeFromId($order->id),
                'total_amount' => $total,                                      // Tổng tiền = Σ thành tiền
            ]);

            return $order;
        });
    }

    /**
     * Xác nhận đơn: kiểm tra lại tồn kho, trừ kho và ghi phiếu xuất (OUT).
     */
    public function confirm(Order $order, int $userId): void
    {
        DB::transaction(function () use ($order, $userId) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== Order::PENDING) {
                throw new DomainException("Đơn {$order->order_code} đang ở trạng thái \"{$order->statusLabel()}\", không thể xác nhận lại.");
            }

            $details  = $order->details()->get();
            $products = $this->lockProducts($details->pluck('product_id'));

            // Tồn kho có thể đã thay đổi kể từ lúc lập đơn -> kiểm tra lại
            $shortages = [];
            foreach ($details as $d) {
                $p = $products[$d->product_id];
                if ($d->quantity > $p->stock_quantity) {
                    $shortages[] = $this->shortageMessage($p, $d->quantity);
                }
            }
            if ($shortages) {
                throw new DomainException('Không thể xác nhận đơn ' . $order->order_code . ': ' . implode(' ', $shortages));
            }

            foreach ($details as $d) {
                $products[$d->product_id]->decrement('stock_quantity', $d->quantity);

                InventoryTransaction::create([
                    'product_id'       => $d->product_id,
                    'user_id'          => $userId,
                    'order_id'         => $order->id,
                    'transaction_type' => InventoryTransaction::TYPE_OUT,
                    'quantity'         => $d->quantity,
                    'note'             => "Xuất kho theo đơn {$order->order_code}",
                ]);
            }

            $order->update(['status' => Order::CONFIRMED]);
        });
    }

    /** Hoàn thành đơn đã xác nhận (hàng đã xuất kho ở bước xác nhận). */
    public function complete(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== Order::CONFIRMED) {
                throw new DomainException("Chỉ hoàn thành được đơn đã xác nhận. Đơn {$order->order_code} đang \"{$order->statusLabel()}\".");
            }

            $order->update(['status' => Order::COMPLETED]);
        });
    }

    /**
     * Hủy đơn. Nếu đơn đã xác nhận (đã trừ kho) thì hoàn lại tồn kho và ghi phiếu nhập (IN).
     *
     * @return bool true nếu có hoàn kho
     */
    public function cancel(Order $order, int $userId, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($order, $userId, $reason) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($order->status, [Order::PENDING, Order::CONFIRMED], true)) {
                throw new DomainException("Không thể hủy đơn {$order->order_code} vì đơn đang \"{$order->statusLabel()}\".");
            }

            $restocked = false;
            if ($order->status === Order::CONFIRMED) {
                $details  = $order->details()->get();
                $products = $this->lockProducts($details->pluck('product_id'));

                foreach ($details as $d) {
                    $products[$d->product_id]->increment('stock_quantity', $d->quantity);

                    InventoryTransaction::create([
                        'product_id'       => $d->product_id,
                        'user_id'          => $userId,
                        'order_id'         => $order->id,
                        'transaction_type' => InventoryTransaction::TYPE_IN,
                        'quantity'         => $d->quantity,
                        'note'             => "Hoàn kho do hủy đơn {$order->order_code}",
                    ]);
                }
                $restocked = true;
            }

            $note = trim(($order->note ? $order->note . "\n" : '') . 'Lý do hủy: ' . ($reason ?: 'không ghi'));
            $order->update(['status' => Order::CANCELLED, 'note' => $note]);

            return $restocked;
        });
    }

    /* ---------------- Hàm phụ ---------------- */

    /** Khóa các dòng sản phẩm liên quan cho tới khi transaction kết thúc */
    private function lockProducts(Collection $ids): Collection
    {
        return Product::whereIn('id', $ids->unique())->lockForUpdate()->get()->keyBy('id');
    }

    private function shortageMessage(Product $p, int $qty): string
    {
        return "{$p->product_name} chỉ còn {$p->stock_quantity} " . mb_strtolower($p->unit) . ", không đủ {$qty}.";
    }
}
