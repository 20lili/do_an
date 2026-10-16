<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Danh sách sản phẩm + tìm kiếm + lọc danh mục / trạng thái / tồn kho.
     */
    public function index(Request $request)
    {
        // Kiểu hiển thị: lưới thẻ (mặc định) hoặc bảng
        $view = $request->input('view') === 'table' ? 'table' : 'grid';

        $products = Product::with('category')
            ->search($request->input('q'))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->stockState($request->input('stock'))
            ->orderBy('category_id')
            ->orderBy('product_code')
            ->paginate($view === 'grid' ? 20 : 15)
            ->withQueryString();

        // Danh mục kèm số sản phẩm để làm thanh lọc nhanh
        $categories = Category::withCount('products')->orderBy('id')->get();

        $summary = [
            'total'    => Product::count(),
            'low'      => Product::stockState('low')->count(),
            'out'      => Product::stockState('out')->count(),
            'inactive' => Product::where('status', 'inactive')->count(),
        ];

        return view('products.index', compact('products', 'categories', 'summary', 'view'));
    }

    public function create()
    {
        $categories = Category::where('status', 'active')->orderBy('id')->get();
        $product    = new Product(['status' => 'active']);

        return view('products.create', compact('categories', 'product'));
    }

    public function store(ProductRequest $request)
    {
        $data = $request->validated();
        $data['image'] = $this->saveImage($request->file('image'));

        // Sản phẩm mới có tồn kho = 0; số lượng được tăng qua nghiệp vụ nhập kho.
        $product = Product::create($data);

        return redirect()
            ->route('products.index')
            ->with('success', "Đã thêm sản phẩm {$product->product_code} – {$product->product_name}.");
    }

    public function show(Product $product)
    {
        $product->load('category');

        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('id')->get();

        return view('products.edit', compact('categories', 'product'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteImage($product->image);
            $data['image'] = $this->saveImage($request->file('image'));
        } else {
            unset($data['image']); // giữ ảnh cũ
        }

        // stock_quantity không có trong $fillable nên không thể bị sửa tại đây.
        $product->update($data);

        return redirect()
            ->route('products.index')
            ->with('success', "Đã cập nhật sản phẩm {$product->product_code}.");
    }

    /**
     * Chuyển trạng thái Đang kinh doanh <-> Ngừng kinh doanh
     * (thay cho xóa cứng, để không mất lịch sử đơn hàng / kho).
     */
    public function toggleStatus(Product $product)
    {
        $product->status = $product->status === 'active' ? 'inactive' : 'active';
        $product->save();

        return back()->with('success', "Sản phẩm {$product->product_code} chuyển sang: {$product->statusLabel()}.");
    }

    /* ---------------- Xử lý ảnh ---------------- */

    private function saveImage(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $dir = public_path('images/products');
        File::ensureDirectoryExists($dir);

        $name = now()->format('YmdHis') . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $name);

        return 'images/products/' . $name;
    }

    private function deleteImage(?string $path): void
    {
        if ($path && File::exists(public_path($path))) {
            File::delete(public_path($path));
        }
    }
}
