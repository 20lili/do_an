<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Kiểm tra dữ liệu phiếu nhập kho (mục 5.6).
 */
class StockReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($row) => filled($row['product_id'] ?? null))
            ->map(fn ($row) => ['product_id' => (int) $row['product_id'], 'quantity' => (int) ($row['quantity'] ?? 0)])
            ->values()->all();

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return [
            'items'              => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1', 'max:10000'],
            'note'               => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'              => 'Phiếu nhập phải có ít nhất một sản phẩm.',
            'items.min'                   => 'Phiếu nhập phải có ít nhất một sản phẩm.',
            'items.*.product_id.distinct' => 'Một sản phẩm đang được chọn ở nhiều dòng, hãy gộp lại thành một dòng.',
            'items.*.product_id.exists'   => 'Sản phẩm không tồn tại.',
            'items.*.quantity.integer'    => 'Số lượng nhập phải là số nguyên.',
            'items.*.quantity.min'        => 'Số lượng nhập phải lớn hơn 0.',
            'items.*.quantity.max'        => 'Số lượng nhập mỗi dòng tối đa 10.000.',
            'note.max'                    => 'Ghi chú tối đa 255 ký tự.',
        ];
    }
}
