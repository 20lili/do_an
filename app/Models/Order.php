<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const PENDING   = 'pending';
    public const CONFIRMED = 'confirmed';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    /** Trạng thái đơn hàng: [nhãn, màu Bootstrap] */
    public const STATUSES = [
        self::PENDING   => ['Chờ xác nhận', 'warning'],
        self::CONFIRMED => ['Đã xác nhận', 'primary'],
        self::COMPLETED => ['Hoàn thành', 'success'],
        self::CANCELLED => ['Đã hủy', 'secondary'],
    ];

    protected $fillable = [
        'order_code',
        'customer_id',
        'user_id',
        'order_date',
        'status',
        'total_amount',
        'note',
    ];

    protected $casts = [
        'order_date'   => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    /* ---------------- Quan hệ ---------------- */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** Nhân viên lập đơn */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    /** Các giao dịch kho phát sinh từ đơn này (xuất kho, hoàn kho) */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /* ---------------- Phạm vi truy vấn ---------------- */

    /** Tìm theo mã đơn, tên hoặc số điện thoại khách hàng */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (blank($keyword)) {
            return $query;
        }
        $keyword = trim($keyword);

        // Gõ dạng mã đơn (DH005) -> chỉ tìm theo mã đơn
        if (preg_match('/^DH\d*$/i', $keyword)) {
            return $query->where('order_code', 'like', "%{$keyword}%");
        }

        $digits = preg_replace('/\D/', '', $keyword);

        return $query->where(function (Builder $q) use ($keyword, $digits) {
            $q->where('order_code', 'like', "%{$keyword}%")
              ->orWhereHas('customer', function (Builder $c) use ($keyword, $digits) {
                  $c->where('customer_name', 'like', "%{$keyword}%");
                  if (strlen($digits) >= 3) {
                      $c->orWhere('phone', 'like', "%{$digits}%");
                  }
              });
        });
    }

    /* ---------------- Hiển thị ---------------- */

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUSES[$this->status][1] ?? 'secondary';
    }

    /** Mã đơn tạo từ id: 31 -> DH031 */
    public static function codeFromId(int $id): string
    {
        return 'DH' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    }
}
