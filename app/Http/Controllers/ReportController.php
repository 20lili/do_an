<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Trang Báo cáo (mục 5.7): thống kê đơn hàng, doanh thu, sản phẩm, khách hàng theo khoảng thời gian.
 */
class ReportController extends Controller
{
    /** Các khoảng thời gian chọn nhanh */
    private function presets(): array
    {
        return [
            '7d'         => ['7 ngày qua',   now()->subDays(6)->startOfDay(), now()->endOfDay()],
            '30d'        => ['30 ngày qua',  now()->subDays(29)->startOfDay(), now()->endOfDay()],
            'this_month' => ['Tháng này',    now()->startOfMonth(), now()->endOfDay()],
            'last_month' => ['Tháng trước',  now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            '90d'        => ['90 ngày qua',  now()->subDays(89)->startOfDay(), now()->endOfDay()],
        ];
    }

    public function index(Request $request, ReportService $reports)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ], ['to.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.']);

        $presets = $this->presets();

        if ($request->filled('from') && $request->filled('to')) {
            $range = 'custom';
            $from  = Carbon::parse($request->from)->startOfDay();
            $to    = Carbon::parse($request->to)->endOfDay();
            if ($from->diffInDays($to) > 366) {
                return back()->withErrors(['to' => 'Khoảng thời gian tối đa là 1 năm.']);
            }
        } else {
            $range = array_key_exists($request->range, $presets) ? $request->range : '30d';
            [, $from, $to] = $presets[$range];
        }

        return view('reports.index', [
            'presets'    => $presets,
            'range'      => $range,
            'from'       => $from,
            'to'         => $to,
            'summary'    => $reports->summary($from, $to),
            'daily'      => $reports->revenueByDay($from, $to),
            'statuses'   => $reports->statusCounts($from, $to),
            'top'        => $reports->topProducts($from, $to, 10),
            'categories' => $reports->revenueByCategory($from, $to),
            'customers'  => $reports->topCustomers($from, $to, 5),
        ]);
    }
}
