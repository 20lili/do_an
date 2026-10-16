<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use DomainException;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders)
    {
    }

    /** Danh sách đơn hàng + lọc theo trạng thái + tìm kiếm */
    public function index(Request $request)
    {
        $status = $request->input('status');

        $orders = Order::with('customer')
            ->withCount('details')
            ->search($request->input('q'))
            ->when(array_key_exists((string) $status, Order::STATUSES), fn ($q) => $q->where('status', $status))
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $counts = Order::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $summary = [
            'today'   => Order::whereDate('order_date', today())->count(),
            'pending' => $counts[Order::PENDING] ?? 0,
            'revenue' => (float) Order::where('status', Order::COMPLETED)
                            ->where('order_date', '>=', now()->startOfMonth())->sum('total_amount'),
        ];

        return view('orders.index', compact('orders', 'counts', 'summary', 'status'));
    }

    /** Biểu mẫu lập đơn */
    public function create(Request $request)
    {
        $customers = Customer::orderBy('customer_name')->get(['id', 'customer_name', 'phone']);

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
                'price'    => (float) $p->selling_price,
                'stock'    => $p->stock_quantity,
                'image'    => $p->imageUrl(),
            ]);

        $selectedCustomer = old('customer_id', $request->input('customer_id'));

        return view('orders.create', compact('customers', 'products', 'selectedCustomer'));
    }

    /** Lưu đơn mới (trạng thái Chờ xác nhận) */
    public function store(OrderRequest $request)
    {
        $order = $this->orders->create($request->validated(), $this->currentUserId());

        return redirect()->route('orders.show', $order)
            ->with('success', "Đã lập đơn {$order->order_code}. " . ($request->user()->can('confirm-orders')
                ? 'Bấm "Xác nhận đơn" để xuất kho.'
                : 'Đơn đang chờ người có quyền xác nhận và xuất kho.'));
    }

    /** Chi tiết đơn + lịch sử kho liên quan */
    public function show(Order $order)
    {
        $order->load(['customer', 'user', 'details.product.category', 'inventoryTransactions.product']);

        return view('orders.show', compact('order'));
    }

    public function confirm(Order $order)
    {
        return $this->run(fn () => $this->orders->confirm($order, $this->currentUserId()),
            "Đã xác nhận đơn {$order->order_code}. Tồn kho đã được trừ và ghi phiếu xuất kho.");
    }

    public function complete(Order $order)
    {
        return $this->run(fn () => $this->orders->complete($order),
            "Đơn {$order->order_code} đã hoàn thành.");
    }

    public function cancel(Request $request, Order $order)
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        try {
            $restocked = $this->orders->cancel($order, $this->currentUserId(), $request->input('reason'));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $restocked
            ? "Đã hủy đơn {$order->order_code} và hoàn lại tồn kho."
            : "Đã hủy đơn {$order->order_code}. Đơn chưa xuất kho nên tồn kho không thay đổi.");
    }

    /** Chạy một thao tác nghiệp vụ, chuyển lỗi nghiệp vụ thành thông báo */
    private function run(callable $action, string $successMessage)
    {
        try {
            $action();
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $successMessage);
    }
}
