<?php

namespace App\Services;

use App\Models\Order;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Các truy vấn thống kê dùng chung cho Dashboard và trang Báo cáo (mục 5.7).
 *
 * Quy ước:
 *  - Doanh thu   = tổng total_amount của các đơn HOÀN THÀNH, theo ngày lập đơn.
 *  - Số lượng bán = số lượng trong các đơn ĐÃ XÁC NHẬN hoặc HOÀN THÀNH (hàng đã xuất kho).
 *  - Lợi nhuận gộp ước tính = Σ (đơn giá bán − giá nhập hiện tại) × số lượng, đơn hoàn thành.
 */
class ReportService
{
    /** Trạng thái được tính là đã bán (đã xuất kho) */
    private const SOLD = [Order::CONFIRMED, Order::COMPLETED];

    /** Chỉ số tổng hợp trong khoảng thời gian */
    public function summary(Carbon $from, Carbon $to): array
    {
        $completed = Order::where('status', Order::COMPLETED)->whereBetween('order_date', [$from, $to]);

        $revenue = (float) (clone $completed)->sum('total_amount');
        $count   = (clone $completed)->count();

        $profit = (float) DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->join('products as p', 'p.id', '=', 'd.product_id')
            ->where('o.status', Order::COMPLETED)
            ->whereBetween('o.order_date', [$from, $to])
            ->sum(DB::raw('d.subtotal - d.quantity * p.import_price'));

        return [
            'revenue'   => $revenue,
            'completed' => $count,
            'avg'       => $count ? $revenue / $count : 0,                 // giá trị trung bình / đơn
            'profit'    => $profit,
            'orders'    => Order::whereBetween('order_date', [$from, $to])->count(),
            'customers' => Order::whereIn('status', self::SOLD)->whereBetween('order_date', [$from, $to])
                                ->distinct()->count('customer_id'),
            'units'     => (int) DB::table('order_details as d')->join('orders as o', 'o.id', '=', 'd.order_id')
                                ->whereIn('o.status', self::SOLD)->whereBetween('o.order_date', [$from, $to])
                                ->sum('d.quantity'),
        ];
    }

    /**
     * Doanh thu theo từng ngày (đủ mọi ngày, ngày không có đơn = 0).
     *
     * @return Collection<int, array{date: Carbon, revenue: float, orders: int}>
     */
    public function revenueByDay(Carbon $from, Carbon $to): Collection
    {
        $rows = Order::where('status', Order::COMPLETED)
            ->whereBetween('order_date', [$from, $to])
            ->selectRaw('DATE(order_date) as d, SUM(total_amount) as revenue, COUNT(*) as orders')
            ->groupBy('d')
            ->get()
            ->keyBy(fn ($r) => (string) $r->d);

        return collect(CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()))
            ->map(fn ($day) => [
                'date'    => $day->copy(),
                'revenue' => (float) ($rows[$day->toDateString()]->revenue ?? 0),
                'orders'  => (int) ($rows[$day->toDateString()]->orders ?? 0),
            ])
            ->values();
    }

    /** Số đơn theo trạng thái (đủ 4 trạng thái) */
    public function statusCounts(Carbon $from, Carbon $to): array
    {
        $counts = Order::whereBetween('order_date', [$from, $to])
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(Order::STATUSES)->map(fn ($s, $key) => (int) ($counts[$key] ?? 0))->all();
    }

    /** Sản phẩm bán chạy theo số lượng */
    public function topProducts(Carbon $from, Carbon $to, int $limit = 5): Collection
    {
        return DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->join('products as p', 'p.id', '=', 'd.product_id')
            ->whereIn('o.status', self::SOLD)
            ->whereBetween('o.order_date', [$from, $to])
            ->groupBy('p.id', 'p.product_code', 'p.product_name', 'p.unit')
            ->selectRaw('p.id, p.product_code, p.product_name, p.unit, SUM(d.quantity) as qty, SUM(d.subtotal) as amount')
            ->orderByDesc('qty')->orderByDesc('amount')
            ->limit($limit)
            ->get();
    }

    /** Doanh thu theo danh mục (đơn hoàn thành) */
    public function revenueByCategory(Carbon $from, Carbon $to): Collection
    {
        return DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->join('products as p', 'p.id', '=', 'd.product_id')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->where('o.status', Order::COMPLETED)
            ->whereBetween('o.order_date', [$from, $to])
            ->groupBy('c.id', 'c.category_name')
            ->selectRaw('c.id, c.category_name, SUM(d.subtotal) as amount, SUM(d.quantity) as qty')
            ->orderByDesc('amount')
            ->get();
    }

    /** Khách hàng mua nhiều nhất (đơn hoàn thành) */
    public function topCustomers(Carbon $from, Carbon $to, int $limit = 5): Collection
    {
        return DB::table('orders as o')
            ->join('customers as c', 'c.id', '=', 'o.customer_id')
            ->where('o.status', Order::COMPLETED)
            ->whereBetween('o.order_date', [$from, $to])
            ->groupBy('c.id', 'c.customer_name', 'c.phone')
            ->selectRaw('c.id, c.customer_name, c.phone, COUNT(*) as orders, SUM(o.total_amount) as amount')
            ->orderByDesc('amount')
            ->limit($limit)
            ->get();
    }

    /** Làm tròn lên "số đẹp" để vẽ trục biểu đồ: 1.234.000 -> 1.500.000 */
    public static function niceMax(float $value): float
    {
        if ($value <= 0) {
            return 1;
        }
        $exp  = 10 ** floor(log10($value));
        foreach ([1, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10] as $m) {
            if ($m * $exp >= $value) {
                return $m * $exp;
            }
        }
        return 10 * $exp;
    }

    /** Viết gọn số tiền cho nhãn trục: 1500000 -> 1,5tr; 250000 -> 250k */
    public static function shortMoney(float $v): string
    {
        return match (true) {
            $v >= 1_000_000 => rtrim(rtrim(number_format($v / 1_000_000, 1, ',', '.'), '0'), ',') . 'tr',
            $v >= 1_000     => number_format($v / 1_000, 0, ',', '.') . 'k',
            default         => number_format($v, 0, ',', '.'),
        };
    }
}
