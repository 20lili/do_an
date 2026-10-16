@extends('layouts.app')

@section('title', 'Tổng quan')
@section('page-title', 'Tổng quan')
@section('page-subtitle', 'Hôm nay, ' . now()->translatedFormat('d/m/Y') . ' – tình hình bán hàng và kho của cửa hàng')

@section('page-actions')
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary"><i class="bi bi-bar-chart-line"></i> Báo cáo chi tiết</a>
    <a href="{{ route('orders.create') }}" class="btn btn-brand"><i class="bi bi-cart-plus"></i> Lập đơn hàng</a>
@endsection

@php
    use App\Models\Product;
    use App\Models\Order;
    $statusDot = ['pending' => '#f0ad00', 'confirmed' => '#3d6ef5', 'completed' => '#1f9d55', 'cancelled' => '#8a8f98'];
@endphp

@section('content')
    {{-- ===== Chỉ số chính ===== --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="kpi hero">
                <div class="kpi-label">Doanh thu tháng {{ now()->format('m/Y') }}</div>
                <div class="kpi-value money">{{ Product::vnd($kpi['revenue_month']) }}</div>
                <div class="kpi-sub">
                    @if (is_null($kpi['revenue_delta']))
                        {{ $month['completed'] }} đơn hoàn thành
                    @else
                        <span class="{{ $kpi['revenue_delta'] >= 0 ? 'delta-up' : 'delta-down' }}">
                            {{ $kpi['revenue_delta'] >= 0 ? '▲' : '▼' }} {{ number_format(abs($kpi['revenue_delta']), 1, ',', '.') }}%
                        </span> so với cùng kỳ tháng trước
                    @endif
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('orders.index') }}" class="kpi">
                <div class="kpi-label">Đơn hàng hôm nay</div>
                <div class="kpi-value">{{ $kpi['orders_today'] }}</div>
                <div class="kpi-sub">{{ $month['orders'] }} đơn trong tháng</div>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('orders.index', ['status' => 'pending']) }}" class="kpi">
                <div class="kpi-label">Đơn cần xử lý</div>
                <div class="kpi-value">{{ $kpi['pending'] + $kpi['confirmed'] }}</div>
                <div class="kpi-sub">{{ $kpi['pending'] }} chờ xác nhận · {{ $kpi['confirmed'] }} đang giao</div>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('inventory.index', ['stock' => 'low']) }}" class="kpi">
                <div class="kpi-label">Cảnh báo tồn kho</div>
                <div class="kpi-value" style="color: var(--pink)">{{ $kpi['low'] + $kpi['out'] }}</div>
                <div class="kpi-sub">{{ $kpi['low'] }} sắp hết · {{ $kpi['out'] }} hết hàng</div>
            </a>
        </div>
    </div>

    {{-- ===== Doanh thu 30 ngày + trạng thái đơn ===== --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="panel">
                <div class="panel-head">
                    <h2>Doanh thu 30 ngày gần nhất</h2>
                    <span class="text-muted">Tổng: <b class="text-dark">{{ Product::vnd($daily->sum('revenue')) }}</b> · đơn hoàn thành</span>
                </div>
                @include('reports._columns', ['points' => $daily])
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel">
                <div class="panel-head">
                    <h2>Đơn hàng theo trạng thái</h2>
                    <span class="text-muted">30 ngày</span>
                </div>
                @include('reports._hbars', ['rows' => collect($statuses)->map(fn ($n, $key) => [
                    'label' => Order::STATUSES[$key][0], 'value' => $n, 'display' => $n . ' đơn',
                    'dot' => $statusDot[$key], 'color' => $statusDot[$key],
                    'href' => route('orders.index', ['status' => $key]),
                ])->values()->all()])
                <div class="mt-3 small text-muted">Tổng {{ array_sum($statuses) }} đơn được lập trong 30 ngày.</div>
            </div>
        </div>
    </div>

    {{-- ===== Bán chạy / sắp hết / đơn gần đây ===== --}}
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="panel">
                <div class="panel-head"><h2>Bán chạy 30 ngày</h2><a href="{{ route('reports.index') }}">Xem thêm</a></div>
                @if ($top->isEmpty())
                    <p class="text-muted small mb-0">Chưa có sản phẩm được bán trong 30 ngày.</p>
                @else
                    <ul class="mini-list">
                        @foreach ($top as $i => $t)
                            <li>
                                <span class="rank {{ $i === 0 ? 'r1' : '' }}">{{ $i + 1 }}</span>
                                <div class="flex-fill" style="min-width:0">
                                    <a href="{{ route('products.show', $t->id) }}" class="fw-bold text-reset text-decoration-none d-block text-truncate">{{ $t->product_name }}</a>
                                    <div class="code">{{ $t->product_code }}</div>
                                </div>
                                <div class="text-end"><div class="fw-bold">{{ $t->qty }} {{ mb_strtolower($t->unit) }}</div><div class="small text-muted money">{{ Product::vnd($t->amount) }}</div></div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel">
                <div class="panel-head"><h2>Cần nhập thêm</h2><a href="{{ route('inventory.index', ['stock' => 'low']) }}">Xem kho</a></div>
                @if ($lowStock->isEmpty())
                    <p class="text-muted small mb-0">Không có sản phẩm nào dưới mức tồn tối thiểu.</p>
                @else
                    <ul class="mini-list">
                        @foreach ($lowStock as $p)
                            <li>
                                <span class="badge rounded-pill text-bg-{{ $p->stock_quantity <= 0 ? 'danger' : 'warning' }}" style="min-width: 2.2rem">{{ $p->stock_quantity }}</span>
                                <div class="flex-fill" style="min-width:0">
                                    <a href="{{ route('inventory.stock-card', $p) }}" class="fw-bold text-reset text-decoration-none d-block text-truncate">{{ $p->product_name }}</a>
                                    <div class="code">{{ $p->product_code }}</div>
                                </div>
                                <a href="{{ route('inventory.receive', ['product_id' => $p->id]) }}" class="btn btn-sm btn-outline-secondary">Nhập</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel">
                <div class="panel-head"><h2>Đơn hàng gần đây</h2><a href="{{ route('orders.index') }}">Tất cả đơn</a></div>
                <ul class="mini-list">
                    @foreach ($recent as $o)
                        <li>
                            <div class="flex-fill" style="min-width:0">
                                <a href="{{ route('orders.show', $o) }}" class="fw-bold">{{ $o->order_code }}</a>
                                <span class="small text-muted ms-1">{{ $o->order_date->format('d/m H:i') }}</span>
                                <div class="small text-truncate">{{ $o->customer->customer_name }}</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold money">{{ Product::vnd($o->total_amount) }}</div>
                                <span class="badge rounded-pill text-bg-{{ $o->statusColor() }}">{{ $o->statusLabel() }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection
