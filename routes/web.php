<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

/*
| Mục 5.8 – Đăng nhập: chỉ dành cho người CHƯA đăng nhập (middleware guest)
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.attempt');
});

/*
| Toàn bộ chức năng nội bộ: bắt buộc đăng nhập (auth) và tài khoản chưa bị khóa.
| Chức năng giới hạn theo vai trò dùng middleware can:<tên quyền> (định nghĩa trong AppServiceProvider).
*/
Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {

    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Mục 5.7 – Dashboard: mọi người dùng (nhân viên thấy thông tin cơ bản); báo cáo: Admin
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index')->middleware('can:view-reports');

    // Mục 5.8 – Danh mục: ai cũng xem; thêm, sửa, ngừng, xóa: Admin
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::middleware('can:manage-categories')->group(function () {
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });

    // Mục 5.3 – Sản phẩm: ai cũng xem; thêm, sửa, ngừng kinh doanh: Admin hoặc nhân viên được cấp quyền
    // (nhóm có quyền khai báo TRƯỚC để đường dẫn products/create không bị hiểu là products/{product})
    Route::middleware('can:manage-products')->group(function () {
        Route::resource('products', ProductController::class)->only(['create', 'store', 'edit', 'update']);
        Route::patch('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
    });
    Route::resource('products', ProductController::class)->only(['index', 'show']);

    // Mục 5.4 – Khách hàng: ai cũng thêm, sửa, xem; xóa: Admin
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])
        ->name('customers.destroy')->middleware('can:delete-customers');
    Route::resource('customers', CustomerController::class)->except(['destroy']);

    // Mục 5.5 – Đơn hàng: ai cũng lập, xem, hoàn thành; xác nhận (xuất kho) và hủy: Admin hoặc nhân viên được cấp quyền
    Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    Route::middleware('can:confirm-orders')->group(function () {
        Route::patch('orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
        Route::patch('orders/{order}/cancel',  [OrderController::class, 'cancel'])->name('orders.cancel');
    });

    // Mục 5.6 – Kho: ai cũng xem tồn kho, lịch sử, thẻ kho; nhập kho: theo quyền; kiểm kê điều chỉnh: Admin
    Route::prefix('inventory')->name('inventory.')->controller(InventoryController::class)->group(function () {
        Route::get('/', 'index')->name('index');                                   // Tồn kho
        Route::get('transactions', 'transactions')->name('transactions');          // Lịch sử nhập – xuất
        Route::get('products/{product}', 'stockCard')->name('stock-card');         // Thẻ kho
        Route::middleware('can:receive-stock')->group(function () {
            Route::get('receive', 'receiveForm')->name('receive');                 // Phiếu nhập kho
            Route::post('receive', 'storeReceipt')->name('receive.store');
        });
        Route::post('products/{product}/adjust', 'adjust')->name('adjust')->middleware('can:adjust-stock');
    });

    // Mục 5.8 – Quản lý tài khoản: chỉ Admin
    Route::middleware('can:manage-users')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    });
});
