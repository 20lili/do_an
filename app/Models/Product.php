<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    /** Mức tồn kho tối thiểu dùng để cảnh báo "Sắp hết" */
    public const LOW_STOCK_THRESHOLD = 10;

    public const STATUSES = [
        'active'   => 'Đang kinh doanh',
        'inactive' => 'Ngừng kinh doanh',
    ];

    /**
     * stock_quantity KHÔNG nằm trong $fillable: tồn kho chỉ được thay đổi
     * qua nghiệp vụ nhập kho / xuất kho (mục 5.3.1), không sửa trực tiếp trên form.
     */
    protected $fillable = [
        'category_id',
        'product_code',
        'product_name',
        'unit',
        'import_price',
        'selling_price',
        'image',
        'description',
        'status',
    ];

    protected $casts = [
        'import_price'   => 'decimal:2',
        'selling_price'  => 'decimal:2',
        'stock_quantity' => 'integer',
    ];

    /* ---------------- Quan hệ ---------------- */

    /** Mỗi sản phẩm thuộc một danh mục */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Lịch sử nhập / xuất kho của sản phẩm */
    public function inventoryTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /* ---------------- Phạm vi truy vấn (scope) ---------------- */

    /** Tìm theo mã hoặc tên sản phẩm */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (blank($keyword)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($keyword) {
            $q->where('product_code', 'like', "%{$keyword}%")
              ->orWhere('product_name', 'like', "%{$keyword}%");
        });
    }

    /** Lọc theo tình trạng tồn kho: in | low | out */
    public function scopeStockState(Builder $query, ?string $state): Builder
    {
        return match ($state) {
            'out'   => $query->where('stock_quantity', 0),
            'low'   => $query->where('stock_quantity', '>', 0)
                             ->where('stock_quantity', '<', self::LOW_STOCK_THRESHOLD),
            'in'    => $query->where('stock_quantity', '>=', self::LOW_STOCK_THRESHOLD),
            default => $query,
        };
    }

    /* ---------------- Thuộc tính hiển thị ---------------- */

    /** Tình trạng tồn kho: [nhãn, màu Bootstrap] */
    public function stockBadge(): array
    {
        if ($this->stock_quantity <= 0) {
            return ['Hết hàng', 'danger'];
        }
        if ($this->stock_quantity < self::LOW_STOCK_THRESHOLD) {
            return ['Sắp hết', 'warning'];
        }
        return ['Còn hàng', 'success'];
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Lợi nhuận gộp trên một đơn vị */
    public function margin(): float
    {
        return (float) $this->selling_price - (float) $this->import_price;
    }

    /**
     * Đường dẫn ảnh hiển thị:
     *  1. Ảnh đã tải lên qua form (cột image), hoặc
     *  2. Ảnh bạn tự chép vào public/images/products/ và đặt tên theo mã sản phẩm,
     *     ví dụ TAM003.jpg, TAM003.png, TAM003.webp (nhận cả .jfif, .avif, .gif).
     *  Không có ảnh -> null (giao diện hiện biểu tượng của danh mục).
     */
    public function imageUrl(): ?string
    {
        if ($this->image && is_file(public_path($this->image))) {
            return asset($this->image);
        }

        $file = self::imageFiles()[strtoupper($this->product_code)] ?? null;

        return $file ? asset('images/products/' . $file) : null;
    }

    /**
     * Đọc thư mục public/images/products một lần mỗi lượt tải trang.
     * Trả về mảng [MÃ IN HOA => tên tệp], ví dụ ['TAM003' => 'TAM003.jpg'].
     */
    private static function imageFiles(): array
    {
        static $map = null;
        if ($map !== null) {
            return $map;
        }

        $map = [];
        $dir = public_path('images/products');
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'jfif', 'avif', 'gif'];

        foreach (is_dir($dir) ? scandir($dir) : [] as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed, true)) {
                $map[strtoupper(pathinfo($file, PATHINFO_FILENAME))] ??= $file;
            }
        }

        return $map;
    }

    /** Định dạng tiền Việt Nam: 125.000đ */
    public static function vnd($amount): string
    {
        return number_format((float) $amount, 0, ',', '.') . 'đ';
    }
}
