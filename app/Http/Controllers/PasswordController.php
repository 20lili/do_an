<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mục 5.8 – Người dùng tự đổi mật khẩu của mình.
 */
class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.required'         => 'Vui lòng nhập mật khẩu hiện tại.',
            'current_password.current_password' => 'Mật khẩu hiện tại không đúng.',
            'password.required'                 => 'Vui lòng nhập mật khẩu mới.',
            'password.min'                      => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'password.confirmed'                => 'Nhập lại mật khẩu mới không khớp.',
            'password.different'                => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
        ]);

        $request->user()->update(['password' => $request->password]);   // cast 'hashed' tự băm

        return redirect()->route('dashboard')->with('success', 'Đã đổi mật khẩu.');
    }
}
