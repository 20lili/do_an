<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Kiểm tra dữ liệu khi Admin thêm / sửa tài khoản (mục 5.8).
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-users');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'        => trim((string) $this->name),
            'username'    => mb_strtolower(trim((string) $this->username)),
            'email'       => trim((string) $this->email) ?: null,
            'permissions' => array_values((array) $this->input('permissions', [])),
        ]);
    }

    public function rules(): array
    {
        $user     = $this->route('user');           // null khi thêm mới
        $creating = $user === null;

        return [
            'name'          => ['required', 'string', 'max:100'],
            'username'      => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9._]+$/',
                                Rule::unique('users', 'username')->ignore($user)],
            'email'         => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role'          => ['required', Rule::in(array_keys(User::ROLES))],
            'permissions'   => ['array'],
            'permissions.*' => [Rule::in(array_keys(User::PERMISSIONS))],
            'password'      => [$creating ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Vui lòng nhập họ tên.',
            'username.required'  => 'Vui lòng nhập tên đăng nhập.',
            'username.min'       => 'Tên đăng nhập phải có ít nhất 3 ký tự.',
            'username.regex'     => 'Tên đăng nhập chỉ gồm chữ thường không dấu, số, dấu chấm hoặc gạch dưới.',
            'username.unique'    => 'Tên đăng nhập đã được sử dụng.',
            'email.email'        => 'Email không đúng định dạng.',
            'email.unique'       => 'Email đã được sử dụng cho tài khoản khác.',
            'role.in'            => 'Vai trò không hợp lệ.',
            'permissions.*.in'   => 'Quyền được chọn không hợp lệ.',
            'password.required'  => 'Vui lòng nhập mật khẩu.',
            'password.min'       => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Nhập lại mật khẩu không khớp.',
        ];
    }
}
