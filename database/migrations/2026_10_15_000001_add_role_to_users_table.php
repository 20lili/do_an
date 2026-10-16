<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mục 5.8 – Bổ sung các cột phục vụ đăng nhập và phân quyền cho bảng users (mục 4.5.1).
 *
 *  - username    : tên đăng nhập (UNIQUE, NOT NULL)
 *  - role        : vai trò admin | staff
 *  - permissions : các quyền bổ sung Admin cấp cho nhân viên ("Theo quyền được cấp" – Bảng 4.1)
 *  - is_active   : tài khoản đang hoạt động hay đã bị khóa
 *  - email       : chuyển thành không bắt buộc vì hệ thống đăng nhập bằng username
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->unique()->after('name');
            $table->string('role', 20)->default('staff')->after('password');
            $table->json('permissions')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('permissions');
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'role', 'permissions', 'is_active']);
        });
    }
};
