<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockReceiptRequest;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Services\InventoryService;
use DomainException;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    /**
     * Tồn kho hiện tại của các sản phẩm + cảnh báo sắp hết / hết hàng.
     */
    public function index(Request $request)
    {
        $sort  = $request->input('sort', 'stock');
        $since = now()->subDays(30);

        $products = Product::with('category')
            ->search($request->input('q'))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->stockState($request->input('stock'))
            // Nhập / xuất trong 30 ngày gần nhất và thời điểm giao dịch gần nhất
            ->withSum(['inventoryTransactions as in_30'  => fn ($q) => $q->where('transaction_type', 'IN')->where('created_at', '>=', $since)], 'quantity')
            ->withSum(['inventoryTransactions as out_30' => fn ($q) => $q->where('transaction_type', 'OUT')->where('created_at', '>=', $since)], 'quantity')
            ->withMax('inventoryTransactions as last_moved_at', 'created_at')
            ->when($sort === 'stock', fn ($q) => $q->orderBy('stock_quantity')->orderBy('product_code'))
            ->when($sort === 'code',  fn ($q) => $q->orderBy('product_code'))
            ->when($sort === 'value', fn ($q) => $q->orderByRaw('stock_quantity * import_price DESC'))
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'units' => (int) Product::sum('stock_quantity'),
            'value' => (float) Product::selectRaw('SUM(stock_quantity * import_price) as v')->value('v'),
            'low'   => Product::stockState('low')->count(),
            'out'   => Product::stockState('out')->count(),
        ];

        $categories = Category::orderBy('id')->get();

        return view('inventory.index', compact('products', 'summary', 'categories', 'sort'));
    }

    /** Biểu mẫu phiếu nhập kho */
    public function receiveForm(Request $request)
    {
        $products = Product::with('category:id,category_name')
            ->where('status', 'active')
            ->orderBy('category_id')->orderBy('product_code')
            ->get()
            ->map(fn ($p) => [
                'id'       => $p->id,
                'code'     => $p->product_code,
                'name'     => $p->product_name,
                'category' => $p->category->category_name,
                'unit'     => $p->unit,
                'cost'     => (float) $p->import_price,
                'stock'    => $p->stock_quantity,
            ]);

        // Mở từ nút "Nhập kho" của một sản phẩm -> chọn sẵn sản phẩm đó
        $preselect = $request->filled('product_id') ? (int) $request->product_id : '';

        return view('inventory.receive', compact('products', 'preselect'));
    }

    /** Lưu phiếu nhập kho */
    public function storeReceipt(StockReceiptRequest $request)
    {
        $data  = $request->validated();
        $total = $this->inventory->receive($data['items'], $this->currentUserId(), $data['note'] ?? null);

        return redirect()->route('inventory.transactions', ['type' => 'IN'])
            ->with('success', 'Đã nhập kho ' . count($data['items']) . " sản phẩm, tổng {$total} đơn vị. Tồn kho đã được cập nhật.");
    }

    /** Lịch sử giao dịch kho + bộ lọc */
    public function transactions(Request $request)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ], ['to.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.']);

        $query = InventoryTransaction::query()
            ->when(in_array($request->type, ['IN', 'OUT'], true), fn ($q) => $q->where('transaction_type', $request->type))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->date('from')->startOfDay()))
            ->when($request->filled('to'),   fn ($q) => $q->where('created_at', '<=', $request->date('to')->endOfDay()));

        // Tổng nhập / xuất theo bộ lọc hiện tại
        $totals = (clone $query)->selectRaw('transaction_type, SUM(quantity) as qty')
            ->groupBy('transaction_type')->pluck('qty', 'transaction_type');

        $transactions = $query->with(['product', 'order', 'user'])
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $products = Product::orderBy('product_code')->get(['id', 'product_code', 'product_name']);

        return view('inventory.transactions', compact('transactions', 'totals', 'products'));
    }

    /**
     * Thẻ kho của một sản phẩm: các giao dịch theo thời gian kèm số dư sau mỗi giao dịch.
     */
    public function stockCard(Product $product)
    {
        $balance = 0;
        $rows = $product->inventoryTransactions()
            ->with(['order', 'user'])
            ->orderBy('created_at')->orderBy('id')
            ->get()
            ->map(function ($t) use (&$balance) {
                $balance += $t->isIn() ? $t->quantity : -$t->quantity;
                $t->balance = $balance;                              // số dư sau giao dịch
                return $t;
            });

        $stats = [
            'in'       => $rows->where('transaction_type', 'IN')->sum('quantity'),
            'out'      => $rows->where('transaction_type', 'OUT')->sum('quantity'),
            'balanced' => $balance === $product->stock_quantity,     // sổ kho khớp tồn kho?
        ];

        return view('inventory.stock-card', [
            'product' => $product->load('category'),
            'rows'    => $rows->reverse()->values(),                   // mới nhất lên đầu
            'stats'   => $stats,
        ]);
    }

    /** Điều chỉnh tồn kho theo kiểm kê thực tế */
    public function adjust(Request $request, Product $product)
    {
        $data = $request->validate([
            'actual_quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'reason'          => ['required', 'string', 'max:200'],
        ], [
            'actual_quantity.required' => 'Vui lòng nhập số lượng thực tế.',
            'actual_quantity.integer'  => 'Số lượng thực tế phải là số nguyên.',
            'actual_quantity.min'      => 'Số lượng thực tế không được âm.',
            'reason.required'          => 'Vui lòng nhập lý do điều chỉnh.',
        ]);

        try {
            $diff = $this->inventory->adjust($product, (int) $data['actual_quantity'], $data['reason'], $this->currentUserId());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $sign = $diff > 0 ? "+{$diff}" : (string) $diff;

        return back()->with('success', "Đã điều chỉnh tồn kho {$product->product_code} ({$sign}). Tồn kho mới: {$data['actual_quantity']}.");
    }
}
