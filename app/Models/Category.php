<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'category_name',
        'description',
        'status',
    ];

    /** Một danh mục có nhiều sản phẩm */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** Biểu tượng hiển thị thay ảnh khi sản phẩm chưa có hình */
    public function emoji(): string
    {
        $name = mb_strtolower($this->category_name);

        return match (true) {
            str_contains($name, 'chó')                                  => '🐶',
            str_contains($name, 'mèo')                                  => '🐱',
            str_contains($name, 'đồ chơi')                              => '🧸',
            str_contains($name, 'phụ kiện')                             => '🎀',
            str_contains($name, 'vệ sinh')                              => '🧼',
            str_contains($name, 'chăm sóc'), str_contains($name, 'sức khỏe') => '💊',
            default                                                     => '🐾',
        };
    }
}
