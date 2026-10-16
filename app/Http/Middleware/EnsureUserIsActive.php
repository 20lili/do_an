<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Nếu tài khoản bị Admin khóa trong lúc đang đăng nhập,
 * ở yêu cầu tiếp theo người dùng bị đăng xuất ngay.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['username' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.']);
        }

        return $next($request);
    }
}
