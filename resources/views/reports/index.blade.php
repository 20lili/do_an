@extends('layouts.app')

@section('title', 'Báo cáo')
@section('page-title', 'Báo cáo thống kê')
@section('page-subtitle', 'Từ ' . $from->format('d/m/Y') . ' đến ' . $to->format('d/m/Y'))

@section('page-actions')
    <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> In báo cáo</button>
@endsection

@php
    use App\Models\Product;
    use App\Models\Order;
    $statusDot = ['pending' => '#f0ad00', 'confirmed' => '#3d6ef5', 'completed' => '#1f9d55', 'cancelled' => '#8a8f98'];
    $catTotal = max(1, $categories->sum('amount'));
@endphp

@section('content')
    {{-- ===== Chọn khoảng thời gian ===== --}}
    <div class="range-bar">
        @foreach ($presets as $key => [$label])
            <a href="{{ route('reports.index', ['range' => $key]) }}" class="chip {{ $range === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
        <form method="GET" action="{{ route('reports.index') }}" class="ms-lg-2">
            <input type="date" name="from" class="form-control form-control-sm" value="{{ $from->toDateString() }}" aria-label="Từ ngày">
            <span class="text-muted">→</span>
            <input type="date" name="to" class="form-control form-control-sm" value="{{ $to->toDateString() }}" aria-label="Đến ngày">
            <button class="btn btn-sm {{ $range === 'custom' ? 'btn-brand' : 'btn-outline-secondary' }}">Xem</button>
        </form>
    </div>
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    {{-- ===== Chỉ số tổng hợp ===== --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><div class="kpi hero">
            <div class="kpi-label">Doanh thu (đơn hoàn thành)</div>
            <div class="kpi-value money">{{ Product::vnd($summary['revenue']) }}</div>
            <div class="kpi-sub">{{ $summary['completed'] }} đơn hoàn thành</div>
        </div></div>
        <div class="col-sm-6 col-xl-3"><div class="kpi">
            <div class="kpi-label">Lợi nhuận gộp ước tính</div>
            <div class="kpi-value money">{{ Product::vnd($summary['profit']) }}</div>
            <div class="kpi-sub">Biên lợi nhuận {{ $summary['revenue'] > 0 ? number_format($summary['profit'] / $summary['revenue'] * 100, 1, ',', '.') : 0 }}%</div>
        </div></div>
        <div class="col-sm-6 col-xl-3"><div class="kpi">
            <div class="kpi-label">Giá trị trung bình / đơn</div>
            <div class="kpi-value money">{{ Product::vnd($summary['avg']) }}</div>
            <div class="kpi-sub">{{ $summary['orders'] }} đơn được lập trong kỳ</div>
        </div></div>
        <div class="col-sm-6 col-xl-3"><div class="kpi">
            <div class="kpi-label">Sản phẩm đã bán</div>
            <div class="kpi-value">{{ number_format($summary['units'], 0, ',', '.') }}</div>
            <div class="kpi-sub">cho {{ $summary['customers'] }} khách hàng</div>
        </div></div>
    </div>

    {{-- ===== Doanh thu theo ngày ===== --}}
    <div class="panel mb-4">
        <div class="panel-head">
            <h2>Doanh thu theo ngày</h2>
            <span class="text-muted">Cao nhất: <b class="text-dark">{{ Product::vnd($daily->max('revenue')) }}</b></span>
        </div>
        @include('reports._columns', ['points' => $daily])
        <details class="mt-3 no-print">
            <summary class="small fw-bold">Xem dạng bảng</summary>
            <div class="table-wrap mt-2" style="max-height: 320px; overflow:auto">
                <table class="table table-sm table-products mb-0">
                    <thead><tr><th class="ps-3">Ngày</th><th class="text-center">Số đơn hoàn thành</th><th class="text-end pe-3">Doanh thu</th></tr></thead>
                    <tbody>
                    @foreach ($daily->where('orders', '>', 0) as $d)
                        <tr><td class="ps-3">{{ $d['date']->format('d/m/Y') }}</td><td class="text-center">{{ $d['orders'] }}</td><td class="text-end pe-3 money">{{ Product::vnd($d['revenue']) }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="panel">
                <div class="panel-head"><h2>Đơn hàng theo trạng thái</h2><span class="text-muted">{{ array_sum($statuses) }} đơn</span></div>
                @include('reports._hbars', ['rows' => collect($statuses)->map(fn ($n, $key) => [
                    'label' => Order::STATUSES[$key][0], 'value' => $n,
                    'display' => $n . ' (' . (array_sum($statuses) ? round($n / array_sum($statuses) * 100) : 0) . '%)',
                    'dot' => $statusDot[$key], 'color' => $statusDot[$key],
                ])->values()->all()])
                <p class="small text-muted mt-3 mb-0">Tỷ lệ hủy đơn: <b>{{ array_sum($statuses) ? number_format($statuses['cancelled'] / array_sum($statuses) * 100, 1, ',', '.') : 0 }}%</b></p>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="panel">
                <div class="panel-head"><h2>Doanh thu theo danh mục</h2><span class="text-muted">đơn hoàn thành</span></div>
                @include('reports._hbars', ['rows' => $categories->map(fn ($c) => [
                    'label' => $c->category_name, 'value' => (float) $c->amount,
                    'display' => Product::vnd($c->amount) . ' · ' . round($c->amount / $catTotal * 100) . '%',
                ])->all()])
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="panel">
                <div class="panel-head"><h2>Top 10 sản phẩm bán chạy</h2><span class="text-muted">đơn đã xác nhận + hoàn thành</span></div>
                @if ($top->isEmpty())
                    <p class="text-muted small mb-0">Chưa có sản phẩm được bán trong kỳ.</p>
                @else
                    <table class="table table-sm table-products mb-0">
                        <thead><tr><th>#</th><th>Sản phẩm</th><th class="text-end">Số lượng</th><th class="text-end">Doanh số</th></tr></thead>
                        <tbody>
                        @foreach ($top as $i => $t)
                            <tr>
                                <td><span class="rank {{ $i === 0 ? 'r1' : '' }}">{{ $i + 1 }}</span></td>
                                <td><a href="{{ route('products.show', $t->id) }}" class="fw-bold text-reset text-decoration-none">{{ $t->product_name }}</a> <span class="code">{{ $t->product_code }}</span></td>
                                <td class="text-end fw-bold">{{ $t->qty }}</td>
                                <td class="text-end money">{{ Product::vnd($t->amount) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
        <div class="col-lg-5">
            <div class="panel">
                <div class="panel-head"><h2>Khách hàng mua nhiều nhất</h2><span class="text-muted">đơn hoàn thành</span></div>
                @if ($customers->isEmpty())
                    <p class="text-muted small mb-0">Chưa có đơn hoàn thành trong kỳ.</p>
                @else
                    <ul class="mini-list">
                        @foreach ($customers as $i => $c)
                            <li>
                                <span class="rank {{ $i === 0 ? 'r1' : '' }}">{{ $i + 1 }}</span>
                                <div class="flex-fill" style="min-width:0">
                                    <a href="{{ route('customers.show', $c->id) }}" class="fw-bold text-reset text-decoration-none">{{ $c->customer_name }}</a>
                                    <div class="small text-muted">{{ $c->orders }} đơn</div>
                                </div>
                                <div class="fw-bold money">{{ Product::vnd($c->amount) }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    <p class="small text-muted mt-4 mb-0">
        Ghi chú: doanh thu tính trên các đơn hoàn thành theo ngày lập đơn; số lượng bán tính trên đơn đã xác nhận và hoàn thành (hàng đã xuất kho);
        lợi nhuận gộp ước tính = Σ (đơn giá bán − giá nhập hiện tại) × số lượng.
    </p>
@endsection
