<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'customer_name',
        'phone',
        'address',
        'email',
        'note',
    ];

    /* ---------------- Quan hệ ---------------- */

    /** Một khách hàng có nhiều đơn hàng */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /* ---------------- Phạm vi truy vấn ---------------- */

    /** Tìm theo tên, số điện thoại, email hoặc mã KH (KH005) */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (blank($keyword)) {
            return $query;
        }

        $keyword = trim($keyword);
        $digits  = preg_replace('/\D/', '', $keyword);   // "0909 000 001" -> "0909000001"

        return $query->where(function (Builder $q) use ($keyword, $digits) {
            $q->where('customer_name', 'like', "%{$keyword}%")
              ->orWhere('email', 'like', "%{$keyword}%");

            if ($digits !== '') {
                $q->orWhere('phone', 'like', "%{$digits}%");
            }
            if (preg_match('/^KH0*(\d+)$/i', $keyword, $m)) {
                $q->orWhere('id', (int) $m[1]);
            }
        });
    }

    /* ---------------- Thuộc tính hiển thị ---------------- */

    /** Mã khách hàng hiển thị: KH001, KH002… (tạo từ id, không lưu thêm cột) */
    public function code(): string
    {
        return 'KH' . str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }

    /** Chữ cái viết tắt cho ảnh đại diện: "Nguyễn Văn An" -> "VA" */
    public function initials(): string
    {
        $words = preg_split('/\s+/u', trim($this->customer_name));
        $picked = count($words) >= 2 ? array_slice($words, -2) : $words;

        return mb_strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), $picked)));
    }

    /** Định dạng số điện thoại: 0909000001 -> 0909 000 001 */
    public function phoneFormatted(): string
    {
        $p = $this->phone;

        return strlen($p) === 10 ? substr($p, 0, 4) . ' ' . substr($p, 4, 3) . ' ' . substr($p, 7) : $p;
    }
}
