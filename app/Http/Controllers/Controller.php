<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Id người dùng đang đăng nhập (ghi vào orders.user_id, inventory_transactions.user_id).
     * Mục 5.8: mọi route nghiệp vụ đều qua middleware auth nên luôn có người dùng.
     */
    protected function currentUserId(): int
    {
        return (int) auth()->id();
    }
}
