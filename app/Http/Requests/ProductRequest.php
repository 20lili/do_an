<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Kiểm tra dữ liệu khi thêm / sửa sản phẩm (mục 5.3.4).
 */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Phân quyền sẽ bổ sung ở mục 5.8
    }

    protected function prepareForValidation(): void
    {
        // Mã sản phẩm: bỏ khoảng trắng, viết hoa (tc001 -> TC001)
        if ($this->has('product_code')) {
            $this->merge(['product_code' => strtoupper(trim((string) $this->product_code))]);
        }

        // Cho phép nhập giá dạng 125.000 -> 125000
        foreach (['import_price', 'selling_price'] as $field) {
            $value = trim((string) $this->input($field));
            if (preg_match('/^\d{1,3}([.,\s]\d{3})+$/', $value)) {
                $this->merge([$field => preg_replace('/[.,\s]/', '', $value)]);
            }
        }
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'product_code'  => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9\-]+$/',
                                Rule::unique('products', 'product_code')->ignore($productId)],
            'product_name'  => ['required', 'string', 'max:150'],
            'category_id'   => ['required', 'exists:categories,id'],
            'unit'          => ['required', 'string', 'max:30'],
            'import_price'  => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:9999999999', 'gte:import_price'],
            'image'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'description'   => ['nullable', 'string', 'max:2000'],
            'status'        => ['required', Rule::in(array_keys(Product::STATUSES))],
        ];
    }

    public function messages(): array
    {
        return [
            'required'                => ':attribute không được để trống.',
            'max'                     => [
                'string'  => ':attribute tối đa :max ký tự.',
                'numeric' => ':attribute quá lớn.',
                'file'    => ':attribute không được vượt quá :max KB.',
            ],
            'min'                     => ['numeric' => ':attribute không được âm.'],
            'numeric'                 => ':attribute phải là số.',
            'product_code.unique'     => 'Mã sản phẩm đã tồn tại, vui lòng nhập mã khác.',
            'product_code.regex'      => 'Mã sản phẩm chỉ gồm chữ in hoa, số và dấu gạch ngang.',
            'category_id.exists'      => 'Danh mục không hợp lệ.',
            'selling_price.gte'       => 'Giá bán không được thấp hơn giá nhập.',
            'image.image'             => 'Tệp tải lên phải là hình ảnh.',
            'image.mimes'             => 'Hình ảnh phải có định dạng jpg, jpeg, png hoặc webp.',
            'image.max'               => 'Hình ảnh không được vượt quá 2MB.',
            'status.in'               => 'Trạng thái không hợp lệ.',
        ];
    }

    public function attributes(): array
    {
        return [
            'product_code'  => 'Mã sản phẩm',
            'product_name'  => 'Tên sản phẩm',
            'category_id'   => 'Danh mục',
            'unit'          => 'Đơn vị tính',
            'import_price'  => 'Giá nhập',
            'selling_price' => 'Giá bán',
            'image'         => 'Hình ảnh',
            'description'   => 'Mô tả',
            'status'        => 'Trạng thái',
        ];
    }
}
