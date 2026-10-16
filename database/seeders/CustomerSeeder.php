<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 20 khách hàng mẫu (dữ liệu giả lập, số điện thoại và email chỉ dùng để thử nghiệm).
 */
class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        // [họ tên, số điện thoại, email, địa chỉ, ghi chú, số ngày trước khi tạo]
        $rows = [
            ['Nguyễn Văn An',     '0909000001', 'an.nguyen@example.com',   '12 Lê Lợi, P. Bến Nghé, Q.1, TP.HCM',            'Nuôi 2 mèo Anh lông ngắn, hay mua pate Whiskas.', 120],
            ['Trần Thị Bích',     '0909000002', 'bich.tran@example.com',   '45 Nguyễn Trãi, P.7, Q.5, TP.HCM',                'Chó Poodle 3 tuổi, dị ứng thức ăn vị bò.',         110],
            ['Lê Hoàng Cường',    '0909000003', null,                       '88 Cách Mạng Tháng 8, P.6, Q.3, TP.HCM',          null,                                                  105],
            ['Phạm Ngọc Diệp',    '0909000004', 'diep.pham@example.com',   '21 Phan Xích Long, P.2, Q. Phú Nhuận, TP.HCM',    'Khách quen, thích nhận hàng buổi tối.',             98],
            ['Hoàng Minh Đức',    '0909000005', 'duc.hoang@example.com',   '150 Điện Biên Phủ, P.15, Q. Bình Thạnh, TP.HCM',  'Nuôi chó Golden, mua hạt Royal Canin định kỳ.',     90],
            ['Võ Thị Giang',      '0909000006', null,                       '9 Trần Hưng Đạo, P. Cầu Kho, Q.1, TP.HCM',        null,                                                  84],
            ['Đặng Quốc Huy',     '0909000007', 'huy.dang@example.com',    '33 Quang Trung, P.10, Q. Gò Vấp, TP.HCM',         null,                                                  77],
            ['Bùi Thanh Hương',   '0909000008', 'huong.bui@example.com',   '70 Võ Văn Ngân, P. Bình Thọ, TP. Thủ Đức',        'Mèo con 4 tháng tuổi.',                              70],
            ['Đỗ Gia Khang',      '0909000009', null,                       '5 Nguyễn Văn Linh, P. Tân Phong, Q.7, TP.HCM',    null,                                                  63],
            ['Ngô Phương Linh',   '0909000010', 'linh.ngo@example.com',    '102 Hoàng Văn Thụ, P.4, Q. Tân Bình, TP.HCM',     'Nuôi thỏ và mèo, quan tâm cát vệ sinh đậu nành.',   56],
            ['Dương Tấn Lộc',     '0909000011', 'loc.duong@example.com',   '18 Lý Thường Kiệt, P.14, Q.10, TP.HCM',           null,                                                  49],
            ['Lý Thị Mai',        '0909000012', null,                       '27 Âu Cơ, P.9, Q. Tân Bình, TP.HCM',              'Thanh toán chuyển khoản.',                           42],
            ['Huỳnh Đức Nam',     '0909000013', 'nam.huynh@example.com',   '64 Nguyễn Thị Thập, P. Bình Thuận, Q.7, TP.HCM',  null,                                                  35],
            ['Phan Thảo Nguyên',  '0909000014', 'nguyen.phan@example.com', '11 Pasteur, P. Võ Thị Sáu, Q.3, TP.HCM',          'Chó Corgi, hay mua đồ chơi gặm.',                    28],
            ['Trương Văn Phúc',   '0909000015', null,                       '230 Lạc Long Quân, P.10, Q.11, TP.HCM',           null,                                                  21],
            ['Mai Anh Quân',      '0909000016', 'quan.mai@example.com',    '7 Lê Văn Sỹ, P.13, Q.3, TP.HCM',                  null,                                                  14],
            ['Châu Ngọc Sương',   '0909000017', 'suong.chau@example.com',  '41 Bạch Đằng, P.2, Q. Tân Bình, TP.HCM',          'Mèo Ba Tư, cần sữa tắm dưỡng lông.',                 10],
            ['Tạ Minh Tâm',       '0909000018', null,                       '19 Kha Vạn Cân, P. Linh Đông, TP. Thủ Đức',       null,                                                  6],
            ['Kiều Thu Trang',    '0909000019', 'trang.kieu@example.com',  '56 Tô Hiến Thành, P.13, Q.10, TP.HCM',            null,                                                  3],
            ['Lâm Gia Vy',        '0909000020', 'vy.lam@example.com',      '3 Hai Bà Trưng, P. Bến Nghé, Q.1, TP.HCM',        'Khách mới, giới thiệu từ khách Nguyễn Văn An.',      1],
        ];

        $data = [];
        foreach ($rows as [$name, $phone, $email, $address, $note, $daysAgo]) {
            $created = now()->subDays($daysAgo);
            $data[] = [
                'customer_name' => $name,
                'phone'         => $phone,
                'email'         => $email,
                'address'       => $address,
                'note'          => $note,
                'created_at'    => $created,
                'updated_at'    => $created,
            ];
        }

        DB::table('customers')->insert($data);
    }
}
