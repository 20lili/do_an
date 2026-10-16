<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 40 sản phẩm thú cưng mẫu.
 * stock_quantity ở đây là TỒN ĐẦU KỲ khi đưa hệ thống vào sử dụng;
 * sau đó tồn kho chỉ thay đổi qua nhập kho / xuất kho theo đơn hàng.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // [category_id, mã, tên, đơn vị, giá nhập, giá bán, tồn, trạng thái, mô tả]
        $rows = [
            // 1. Thức ăn cho chó
            [1, 'TAC001', 'Hạt Royal Canin Mini Adult 2kg',            'Túi',  380000, 465000,  25, 'active', 'Thức ăn hạt cho chó trưởng thành giống nhỏ dưới 10kg.'],
            [1, 'TAC002', 'Hạt Pedigree vị bò và rau củ 1.5kg',        'Túi',   95000, 125000,  40, 'active', 'Hạt dinh dưỡng cho chó trưởng thành, vị bò và rau củ.'],
            [1, 'TAC003', 'Hạt SmartHeart cho chó con 1.5kg',          'Túi',   85000, 110000,  32, 'active', 'Công thức cho chó con dưới 12 tháng tuổi.'],
            [1, 'TAC004', 'Hạt Ganador Adult vị cừu và gạo 3kg',       'Túi',  165000, 210000,  18, 'active', 'Hạt cho chó trưởng thành, hỗ trợ lông mượt.'],
            [1, 'TAC005', 'Pate Pedigree vị gà nấu sốt 130g',          'Gói',   18000,  25000, 120, 'active', 'Pate mềm cho chó mọi lứa tuổi.'],
            [1, 'TAC006', 'Xúc xích Bow Wow cho chó (15 cây)',         'Gói',   38000,  52000,  60, 'active', 'Xúc xích dinh dưỡng dùng làm phần thưởng.'],
            [1, 'TAC007', 'Xương gặm sạch răng Jerhigh 70g',           'Gói',   32000,  45000,   8, 'active', 'Bánh thưởng dạng xương, hỗ trợ làm sạch răng.'],
            [1, 'TAC008', 'Sữa bột Bio Milk cho chó mèo 100g',         'Hộp',   25000,  38000,   0, 'active', 'Sữa bột bổ sung dinh dưỡng cho chó mèo con.'],

            // 2. Thức ăn cho mèo
            [2, 'TAM001', 'Hạt Royal Canin Indoor 27 cho mèo 2kg',     'Túi',  420000, 520000,  22, 'active', 'Hạt cho mèo trưởng thành nuôi trong nhà.'],
            [2, 'TAM002', 'Hạt Whiskas vị cá biển 1.2kg',              'Túi',   98000, 128000,  45, 'active', 'Hạt cho mèo trưởng thành, vị cá biển.'],
            [2, 'TAM003', 'Hạt Me-O vị cá ngừ 1.1kg',                  'Túi',   75000,  99000,  50, 'active', 'Hạt cho mèo trưởng thành, vị cá ngừ.'],
            [2, 'TAM004', 'Hạt Catsrang mọi lứa tuổi 2kg',             'Túi',  150000, 195000,  15, 'active', 'Hạt dùng cho mèo ở mọi giai đoạn phát triển.'],
            [2, 'TAM005', 'Pate Whiskas vị cá thu 80g',                'Gói',    9000,  14000, 150, 'active', 'Pate gói cho mèo trưởng thành.'],
            [2, 'TAM006', 'Pate Me-O vị cá ngừ 80g',                   'Gói',    8000,  12000,   6, 'active', 'Pate gói vị cá ngừ cho mèo.'],
            [2, 'TAM007', 'Súp thưởng Ciao Churu vị cá ngừ (4 thanh)', 'Gói',   28000,  39000,  70, 'active', 'Súp kem thưởng cho mèo, gói 4 thanh x 14g.'],
            [2, 'TAM008', 'Hạt Reflex Plus cho mèo con 1.5kg',         'Túi',  145000, 185000,  12, 'active', 'Hạt cho mèo con từ 2 đến 12 tháng tuổi.'],

            // 3. Đồ chơi
            [3, 'DC001',  'Bóng cao su gai cho chó',                   'Cái',   15000,  29000,  35, 'active', 'Bóng cao su có gai, giúp chó vận động và gặm.'],
            [3, 'DC002',  'Xương cao su đồ chơi cho chó',              'Cái',   22000,  39000,  28, 'active', 'Đồ chơi gặm bằng cao su bền.'],
            [3, 'DC003',  'Cần câu lông vũ cho mèo',                   'Cái',   12000,  25000,  40, 'active', 'Cần câu gắn lông vũ và chuông.'],
            [3, 'DC004',  'Chuột nhồi bông có catnip',                 'Cái',   10000,  20000,   4, 'active', 'Đồ chơi chuột nhồi bông tẩm cỏ mèo.'],
            [3, 'DC005',  'Bàn cào móng mèo bằng bìa carton',          'Cái',   45000,  75000,  14, 'active', 'Bàn cào móng giúp bảo vệ đồ nội thất.'],
            [3, 'DC006',  'Dây thừng kéo co cho chó',                  'Cái',   18000,  35000,  20, 'active', 'Dây thừng cotton dùng chơi kéo co.'],

            // 4. Phụ kiện
            [4, 'PK001',  'Vòng cổ có chuông cho mèo',                 'Cái',   12000,  25000,  50, 'active', 'Vòng cổ vải có khóa an toàn và chuông nhỏ.'],
            [4, 'PK002',  'Bộ dây dắt và yếm cho chó size M',          'Bộ',    65000, 110000,  16, 'active', 'Yếm đeo ngực kèm dây dắt dài 1,2m.'],
            [4, 'PK003',  'Bát ăn inox chống trượt',                   'Cái',   30000,  55000,  30, 'active', 'Bát inox có đế cao su chống trượt.'],
            [4, 'PK004',  'Bát ăn đôi kèm bình nước tự động',          'Cái',   85000, 140000,   9, 'active', 'Bát đôi, một bên gắn bình nước tự chảy.'],
            [4, 'PK005',  'Balo phi hành gia cho mèo',                 'Cái',  220000, 350000,   0, 'active', 'Balo có ô cửa trong suốt, thoáng khí.'],
            [4, 'PK006',  'Nệm ngủ tròn cho chó mèo size M',           'Cái',  120000, 195000,  11, 'active', 'Nệm lông mềm, đường kính 50cm.'],
            [4, 'PK007',  'Lồng vận chuyển thú cưng size S',           'Cái',  180000, 290000,   3, 'inactive', 'Lồng nhựa vận chuyển; cửa hàng đã ngừng nhập mẫu này.'],

            // 5. Vệ sinh
            [5, 'VS001',  'Cát vệ sinh mèo đậu nành 6L',               'Túi',   75000, 110000,   5, 'active', 'Cát đậu nành vón cục nhanh, có thể xả bồn cầu.'],
            [5, 'VS002',  'Cát đất sét Nhật hương táo 8L',             'Túi',   55000,  85000,  38, 'active', 'Cát đất sét khử mùi, hương táo.'],
            [5, 'VS003',  'Sữa tắm SOS cho chó lông trắng 530ml',      'Chai',  85000, 125000,  24, 'active', 'Sữa tắm dưỡng lông trắng sáng.'],
            [5, 'VS004',  'Khăn ướt vệ sinh thú cưng (80 tờ)',         'Gói',   25000,  42000,  46, 'active', 'Khăn ướt không cồn, lau chân và lông.'],
            [5, 'VS005',  'Tã lót vệ sinh cho chó (50 miếng)',         'Gói',   95000, 145000,  13, 'active', 'Tấm lót thấm hút, kích thước 45x60cm.'],
            [5, 'VS006',  'Xịt khử mùi chuồng trại 500ml',             'Chai',  60000,  95000,  19, 'active', 'Xịt khử mùi, diệt khuẩn khu vực nuôi.'],

            // 6. Chăm sóc & sức khỏe
            [6, 'CS001',  'Nhỏ gáy trị ve rận Frontline (chó 10-20kg)', 'Tuýp', 120000, 175000,  21, 'active', 'Thuốc nhỏ gáy phòng và trị ve, bọ chét.'],
            [6, 'CS002',  'Lược chải lông tự làm sạch',                'Cái',   45000,  79000,  26, 'active', 'Lược có nút bấm đẩy lông rụng ra ngoài.'],
            [6, 'CS003',  'Kềm cắt móng thú cưng',                     'Cái',   35000,  65000,  17, 'active', 'Kềm thép không gỉ có khóa an toàn.'],
            [6, 'CS004',  'Gel dinh dưỡng Nutri-Plus 120g',            'Tuýp', 140000, 195000,   7, 'active', 'Gel bổ sung năng lượng và vitamin.'],
            [6, 'CS005',  'Bộ bàn chải và kem đánh răng cho chó mèo',  'Bộ',    50000,  85000,  10, 'active', 'Kem đánh răng vị gà kèm bàn chải xỏ ngón.'],
        ];

        $now = now();
        $data = [];

        foreach ($rows as [$cat, $code, $name, $unit, $import, $sell, $stock, $status, $desc]) {
            $data[] = [
                'category_id'    => $cat,
                'product_code'   => $code,
                'product_name'   => $name,
                'unit'           => $unit,
                'import_price'   => $import,
                'selling_price'  => $sell,
                'stock_quantity' => $stock,
                'image'          => null,
                'description'    => $desc,
                'status'         => $status,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        DB::table('products')->insert($data);
    }
}
