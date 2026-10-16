<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Người dùng hệ thống (mục 4.5.1, 4.9).
 * Vai trò: admin (Quản trị viên) | staff (Nhân viên).
 */
class User extends Authenticatable
{
    use Notifiable;

    public const ADMIN = 'admin';
    public const STAFF = 'staff';

    public const ROLES = [
        self::ADMIN => 'Quản trị viên',
        self::STAFF => 'Nhân viên',
    ];

    /**
     * Các quyền bổ sung Admin có thể cấp cho nhân viên –
     * ứng với các dòng "Theo quyền được cấp" trong ma trận phân quyền (Bảng 4.1).
     */
    public const PERMISSIONS = [
        'products.manage'   => 'Thêm, sửa sản phẩm',
        'orders.confirm'    => 'Xác nhận và hủy đơn hàng',
        'inventory.receive' => 'Nhập kho',
    ];

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'permissions',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'    => 'hashed',   // tự băm mật khẩu (bcrypt) khi gán
            'permissions' => 'array',
            'is_active'   => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ADMIN;
    }

    /** Admin có mọi quyền; nhân viên chỉ có các quyền được cấp */
    public function hasPermission(string $permission): bool
    {
        return $this->isAdmin() || in_array($permission, $this->permissions ?? [], true);
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    /** Chữ cái đầu để hiển thị avatar: "Lê Thị Mai" -> "M" */
    public function initial(): string
    {
        return mb_strtoupper(mb_substr(trim(\Illuminate\Support\Str::afterLast(trim($this->name), ' ')), 0, 1));
    }
}
