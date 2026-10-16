<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Mục 5.8 – Tài khoản mẫu (mật khẩu được băm tự động nhờ cast 'hashed' của model User).
 *
 *  | Tên đăng nhập | Mật khẩu     | Vai trò        | Quyền bổ sung                 |
 *  |---------------|--------------|----------------|-------------------------------|
 *  | admin         | admin123     | Quản trị viên  | (toàn quyền)                  |
 *  | nhanvien      | nhanvien123  | Nhân viên      | Xác nhận và hủy đơn, Nhập kho |
 *  | nhanvien2     | nhanvien123  | Nhân viên      | (chỉ quyền cơ bản)            |
 *  | thuviec       | nhanvien123  | Nhân viên      | – tài khoản đã bị khóa –      |
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Trần Quốc Bảo', 'username' => 'admin',     'password' => 'admin123',    'role' => User::ADMIN, 'permissions' => [],                                     'is_active' => true],
            ['name' => 'Lê Thị Mai',    'username' => 'nhanvien',  'password' => 'nhanvien123', 'role' => User::STAFF, 'permissions' => ['orders.confirm', 'inventory.receive'], 'is_active' => true],
            ['name' => 'Phạm Văn Nam',  'username' => 'nhanvien2', 'password' => 'nhanvien123', 'role' => User::STAFF, 'permissions' => [],                                     'is_active' => true],
            ['name' => 'Đỗ Minh Khoa',  'username' => 'thuviec',   'password' => 'nhanvien123', 'role' => User::STAFF, 'permissions' => [],                                     'is_active' => false],
        ];

        foreach ($users as $u) {
            User::create($u);
        }
    }
}
