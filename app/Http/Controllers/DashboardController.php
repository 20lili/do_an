<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\ReportService;

/**
 * Trang Tổng quan (mục 5.7): tình hình hôm nay, tháng này và các việc cần xử lý.
 */
class DashboardController extends Controller
{
    public function __invoke(ReportService $reports)
    {
        $today      = now();
        $monthStart = now()->startOfMonth();
        $lastStart  = now()->subMonthNoOverflow()->startOfMonth();
        $lastSameDay = now()->subMonthNoOverflow()->endOfDay();   // cùng thời điểm tháng trước để so sánh công bằng

        $month = $reports->summary($monthStart, $today->copy()->endOfDay());
        $last  = $reports->summary($lastStart, $lastSameDay);

        $kpi = [
            'revenue_month' => $month['revenue'],
            'revenue_delta' => $last['revenue'] > 0 ? ($month['revenue'] - $last['revenue']) / $last['revenue'] * 100 : null,
            'orders_today'  => Order::whereDate('order_date', today())->count(),
            'pending'       => Order::where('status', Order::PENDING)->count(),
            'confirmed'     => Order::where('status', Order::CONFIRMED)->count(),
            'low'           => Product::stockState('low')->count(),
            'out'           => Product::stockState('out')->count(),
        ];

        $from30   = now()->subDays(29)->startOfDay();
        $daily    = $reports->revenueByDay($from30, $today->copy()->endOfDay());
        $statuses = $reports->statusCounts($from30, $today->copy()->endOfDay());
        $top      = $reports->topProducts($from30, $today->copy()->endOfDay(), 5);

        $lowStock = Product::with('category')->where('status', 'active')
            ->where('stock_quantity', '<', Product::LOW_STOCK_THRESHOLD)
            ->orderBy('stock_quantity')->orderBy('product_code')->limit(6)->get();

        $recent = Order::with('customer')->orderByDesc('order_date')->orderByDesc('id')->limit(6)->get();

        return view('dashboard', compact('kpi', 'month', 'daily', 'statuses', 'top', 'lowStock', 'recent'));
    }
}
