<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Phân trang dùng giao diện Bootstrap 5 thay vì Tailwind mặc định
        Schema::defaultStringLength(191);
        Paginator::useBootstrapFive();

        /*
        | Mục 5.8 – Định nghĩa quyền (Gate) theo ma trận phân quyền Bảng 4.1.
        | Dùng ở route: ->middleware('can:manage-users')
        | Dùng ở view:  @can('manage-users') ... @endcan
        */

        // Chỉ Quản trị viên
        Gate::define('manage-users',      fn (User $u) => $u->isAdmin());   // quản lý tài khoản
        Gate::define('manage-categories', fn (User $u) => $u->isAdmin());   // thêm, sửa danh mục
        Gate::define('delete-customers',  fn (User $u) => $u->isAdmin());   // xóa dữ liệu khách hàng
        Gate::define('adjust-stock',      fn (User $u) => $u->isAdmin());   // kiểm kê điều chỉnh tồn kho
        Gate::define('view-reports',      fn (User $u) => $u->isAdmin());   // doanh thu, lợi nhuận, báo cáo

        // Admin hoặc nhân viên được cấp quyền
        Gate::define('manage-products', fn (User $u) => $u->hasPermission('products.manage'));
        Gate::define('confirm-orders',  fn (User $u) => $u->hasPermission('orders.confirm'));
        Gate::define('receive-stock',   fn (User $u) => $u->hasPermission('inventory.receive'));
    }
}
