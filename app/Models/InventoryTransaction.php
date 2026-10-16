<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Giao dịch kho: IN (nhập kho / hoàn kho) hoặc OUT (xuất kho theo đơn hàng).
 */
class InventoryTransaction extends Model
{
    public const UPDATED_AT = null;   // bảng chỉ có created_at

    public const TYPE_IN  = 'IN';
    public const TYPE_OUT = 'OUT';

    protected $fillable = [
        'product_id',
        'user_id',
        'order_id',
        'transaction_type',
        'quantity',
        'note',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isIn(): bool
    {
        return $this->transaction_type === self::TYPE_IN;
    }

    /** Số lượng có dấu để hiển thị: +50 / -2 */
    public function signedQuantity(): string
    {
        return ($this->isIn() ? '+' : '−') . $this->quantity;
    }
}
