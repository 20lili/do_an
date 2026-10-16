<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Kiểm tra dữ liệu khi thêm / sửa khách hàng (mục 5.4).
 */
class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Phân quyền bổ sung ở mục 5.8
    }

    protected function prepareForValidation(): void
    {
        // Chuẩn hóa: bỏ khoảng trắng/dấu chấm trong SĐT, +84 -> 0, email chữ thường
        $phone = preg_replace('/[\s.\-]/', '', (string) $this->phone);
        if (str_starts_with($phone, '+84')) {
            $phone = '0' . substr($phone, 3);
        }

        $this->merge([
            'customer_name' => preg_replace('/\s+/u', ' ', trim((string) $this->customer_name)),
            'phone'         => $phone,
            'email'         => $this->filled('email') ? mb_strtolower(trim($this->email)) : null,
        ]);
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->id;

        return [
            'customer_name' => ['required', 'string', 'max:100'],
            // Số di động Việt Nam 10 số: 03x, 05x, 07x, 08x, 09x
            'phone'         => ['required', 'regex:/^0(3|5|7|8|9)\d{8}$/',
                                Rule::unique('customers', 'phone')->ignore($customerId)],
            'email'         => ['nullable', 'email', 'max:100'],
            'address'       => ['nullable', 'string', 'max:255'],
            'note'          => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'required'      => ':attribute không được để trống.',
            'max'           => [
                'string' => ':attribute tối đa :max ký tự.',
            ],
            'phone.regex'   => 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 03, 05, 07, 08 hoặc 09.',
            'phone.unique'  => 'Số điện thoại này đã được dùng cho khách hàng khác.',
            'email.email'   => 'Email không đúng định dạng.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_name' => 'Họ tên',
            'phone'         => 'Số điện thoại',
            'email'         => 'Email',
            'address'       => 'Địa chỉ',
            'note'          => 'Ghi chú',
        ];
    }
}
