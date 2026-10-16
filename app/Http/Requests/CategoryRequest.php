<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Kiểm tra dữ liệu danh mục (mục 5.8 – FR03).
 */
class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-categories');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'category_name' => preg_replace('/\s+/u', ' ', trim((string) $this->category_name)),
            'description'   => trim((string) $this->description) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'category_name' => ['required', 'string', 'max:100',
                                Rule::unique('categories', 'category_name')->ignore($this->route('category'))],
            'description'   => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.required' => 'Vui lòng nhập tên danh mục.',
            'category_name.max'      => 'Tên danh mục tối đa 100 ký tự.',
            'category_name.unique'   => 'Tên danh mục đã tồn tại.',
            'description.max'        => 'Mô tả tối đa 500 ký tự.',
        ];
    }
}
