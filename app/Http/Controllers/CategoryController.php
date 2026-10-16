<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Mục 5.8 – Quản lý danh mục (FR03).
 * Nhân viên: chỉ xem. Quản trị viên: thêm, sửa, ngừng sử dụng, xóa danh mục chưa có sản phẩm.
 * Trang danh sách và biểu mẫu nằm chung một màn hình (danh mục chỉ có 2 trường).
 */
class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', $this->listData() + ['category' => new Category()]);
    }

    public function edit(Category $category): View
    {
        return view('categories.index', $this->listData() + compact('category'));
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated() + ['status' => 'active']);

        return redirect()->route('categories.index')->with('success', 'Đã thêm danh mục "' . $category->category_name . '".');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('success', 'Đã cập nhật danh mục "' . $category->category_name . '".');
    }

    /** Ngừng sử dụng / sử dụng lại: danh mục ngừng sử dụng không hiện khi thêm sản phẩm mới */
    public function toggleStatus(Category $category): RedirectResponse
    {
        $category->update(['status' => $category->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', ($category->status === 'active' ? 'Đã sử dụng lại' : 'Đã ngừng sử dụng')
            . ' danh mục "' . $category->category_name . '".');
    }

    /** Chỉ xóa được danh mục chưa có sản phẩm nào */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Danh mục "' . $category->category_name . '" đang có sản phẩm, không thể xóa. Hãy chọn "Ngừng sử dụng".');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Đã xóa danh mục "' . $category->category_name . '".');
    }

    private function listData(): array
    {
        $categories = Category::withCount([
                'products',
                'products as active_products_count' => fn ($q) => $q->where('status', 'active'),
            ])
            ->withSum('products as stock_total', 'stock_quantity')
            ->orderBy('id')
            ->get();

        return compact('categories');
    }
}
