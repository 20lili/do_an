<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $categories = [
            ['id' => 1, 'category_name' => 'Thức ăn cho chó',     'description' => 'Hạt khô, pate, xúc xích, bánh thưởng dành cho chó'],
            ['id' => 2, 'category_name' => 'Thức ăn cho mèo',     'description' => 'Hạt khô, pate, súp thưởng dành cho mèo'],
            ['id' => 3, 'category_name' => 'Đồ chơi',             'description' => 'Đồ chơi vận động, gặm, cào móng cho chó mèo'],
            ['id' => 4, 'category_name' => 'Phụ kiện',            'description' => 'Vòng cổ, dây dắt, bát ăn, nệm, balo, lồng vận chuyển'],
            ['id' => 5, 'category_name' => 'Vệ sinh',             'description' => 'Cát vệ sinh, sữa tắm, tã lót, khử mùi'],
            ['id' => 6, 'category_name' => 'Chăm sóc & sức khỏe', 'description' => 'Thuốc trị ve rận, dinh dưỡng bổ sung, dụng cụ chải chuốt'],
        ];

        foreach ($categories as &$c) {
            $c += ['status' => 'active', 'created_at' => $now, 'updated_at' => $now];
        }

        DB::table('categories')->insert($categories);
    }
}
