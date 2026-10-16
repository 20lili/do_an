<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Kiểm tra dữ liệu khi lập đơn hàng (mục 5.5).
 * Kiểm tra tồn kho được thực hiện tiếp trong OrderService.
 */
class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Bỏ các dòng trống (chưa chọn sản phẩm)
        $items = collect($this->input('items', []))
            ->filter(fn ($row) => filled($row['product_id'] ?? null))
            ->map(fn ($row) => ['product_id' => (int) $row['product_id'], 'quantity' => (int) ($row['quantity'] ?? 0)])
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return [
            'customer_id'          => ['required', 'exists:customers,id'],
            'items'                => ['required', 'array', 'min:1', 'max:30'],
            'items.*.product_id'   => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity'     => ['required', 'integer', 'min:1', 'max:1000'],
            'note'                 => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required'        => 'Vui lòng chọn khách hàng.',
            'customer_id.exists'          => 'Khách hàng không tồn tại.',
            'items.required'              => 'Đơn hàng phải có ít nhất một sản phẩm.',
            'items.min'                   => 'Đơn hàng phải có ít nhất một sản phẩm.',
            'items.*.product_id.distinct' => 'Một sản phẩm đang được chọn ở nhiều dòng, hãy gộp lại thành một dòng.',
            'items.*.product_id.exists'   => 'Sản phẩm không tồn tại.',
            'items.*.quantity.min'        => 'Số lượng phải lớn hơn 0.',
            'items.*.quantity.integer'    => 'Số lượng phải là số nguyên.',
            'items.*.quantity.max'        => 'Số lượng tối đa mỗi dòng là 1000.',
            'note.max'                    => 'Ghi chú tối đa 1000 ký tự.',
        ];
    }
}
