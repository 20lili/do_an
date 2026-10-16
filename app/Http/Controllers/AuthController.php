<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Mục 5.8 – Đăng nhập, đăng xuất (FR01, UC01).
 */
class AuthController extends Controller
{
    /** Số lần nhập sai tối đa trước khi bị tạm khóa 60 giây */
    private const MAX_ATTEMPTS = 5;

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        // Tên đăng nhập lưu dạng chữ thường: bỏ khoảng trắng thừa và không phân biệt hoa thường khi nhập
        $request->merge(['username' => mb_strtolower(trim((string) $request->username))]);

        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        // Chống dò mật khẩu: tối đa 5 lần sai / 1 phút cho mỗi tên đăng nhập + địa chỉ IP
        $key = Str::lower($credentials['username']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'username' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau ' . RateLimiter::availableIn($key) . ' giây.',
            ]);
        }

        // Auth::attempt tự so khớp mật khẩu với mã băm trong CSDL
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'username' => 'Tên đăng nhập hoặc mật khẩu không đúng.',
            ]);
        }

        // Tài khoản đã bị khóa: không cho vào hệ thống
        if (! Auth::user()->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'username' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();   // cấp lại mã phiên, chống chiếm phiên (session fixation)

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Xin chào ' . Auth::user()->name . '!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Bạn đã đăng xuất.');
    }
}
