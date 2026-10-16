<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,       // 4 tài khoản mẫu (mục 5.8)
            CategorySeeder::class,   // 6 danh mục
            ProductSeeder::class,    // 40 sản phẩm
            CustomerSeeder::class,   // 20 khách hàng
            OrderSeeder::class,      // 30 đơn hàng mẫu (lịch sử mua hàng) + phiếu xuất kho
            InventorySeeder::class,  // phiếu tồn đầu kỳ cho 40 sản phẩm
        ]);
    }
}
