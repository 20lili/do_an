<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Danh sách khách hàng + tìm kiếm + sắp xếp.
     */
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'newest');

        $customers = Customer::query()
            ->search($request->input('q'))
            ->withCount('orders')
            // Tổng chi tiêu: cộng các đơn KHÔNG bị hủy
            ->withSum(['orders as total_spent' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total_amount')
            ->when($sort === 'name',  fn ($q) => $q->orderBy('customer_name'))
            ->when($sort === 'spent', fn ($q) => $q->orderByDesc('total_spent')->orderByDesc('id'))
            ->when($sort === 'newest', fn ($q) => $q->orderByDesc('created_at')->orderByDesc('id'))
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total'      => Customer::count(),
            'new_month'  => Customer::where('created_at', '>=', now()->startOfMonth())->count(),
            'has_orders' => Customer::has('orders')->count(),
        ];

        return view('customers.index', compact('customers', 'summary', 'sort'));
    }

    public function create()
    {
        return view('customers.create', ['customer' => new Customer()]);
    }

    public function store(CustomerRequest $request)
    {
        $customer = Customer::create($request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Đã thêm khách hàng {$customer->code()} – {$customer->customer_name}.");
    }

    /**
     * Chi tiết khách hàng + lịch sử đơn hàng.
     */
    public function show(Customer $customer)
    {
        $orders = $customer->orders()
            ->withCount('details')
            ->orderByDesc('order_date')
            ->paginate(10);

        $stats = [
            'orders'     => $customer->orders()->count(),
            'spent'      => (float) $customer->orders()->where('status', '!=', 'cancelled')->sum('total_amount'),
            'last_order' => $customer->orders()->max('order_date'),
        ];

        return view('customers.show', compact('customer', 'orders', 'stats'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Đã cập nhật thông tin khách hàng {$customer->code()}.");
    }

    /**
     * Chỉ xóa được khách hàng CHƯA phát sinh đơn hàng,
     * để không làm mất dữ liệu lịch sử bán hàng.
     */
    public function destroy(Customer $customer)
    {
        if ($customer->orders()->exists()) {
            return back()->with('error',
                "Không thể xóa {$customer->customer_name} vì khách hàng đã có đơn hàng. Bạn có thể cập nhật thông tin thay vì xóa.");
        }

        $name = $customer->customer_name;
        $customer->delete();

        return redirect()->route('customers.index')->with('success', "Đã xóa khách hàng {$name}.");
    }
}
