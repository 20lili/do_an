@extends('layouts.app')

@section('title', 'Đơn hàng')
@section('page-title', 'Đơn hàng')
@section('page-subtitle', 'Lập đơn, xác nhận xuất kho và theo dõi trạng thái đơn hàng')

@section('page-actions')
    <a href="{{ route('orders.create') }}" class="btn btn-brand"><i class="bi bi-cart-plus"></i> Lập đơn hàng</a>
@endsection

@php
    use App\Models\Order;
    $statusUrl = fn ($s) => request()->fullUrlWithQuery(['status' => $s, 'page' => null]);
    $totalAll  = $counts->sum();
    $dotColor  = ['pending' => '#f0ad00', 'confirmed' => '#3d6ef5', 'completed' => '#1f9d55', 'cancelled' => '#8a8f98'];
@endphp

@section('content')
    <div class="stat-strip">
        <div class="stat-pill"><b>{{ $summary['today'] }}</b><span>đơn hôm nay</span></div>
        <div class="stat-pill"><b>{{ $summary['pending'] }}</b><span>đơn chờ xác nhận</span></div>
        @can('view-reports')
            <div class="stat-pill"><b>{{ \App\Models\Product::vnd($summary['revenue']) }}</b><span>doanh thu tháng này (đơn hoàn thành)</span></div>
        @endcan
    </div>

    <div class="toolbar">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ $statusUrl(null) }}" class="chip {{ blank($status) ? 'active' : '' }}">Tất cả <span class="count">{{ $totalAll }}</span></a>
            @foreach (Order::STATUSES as $key => [$label, $color])
                <a href="{{ $statusUrl($key) }}" class="chip {{ $status === $key ? 'active' : '' }}">
                    <span class="dot" style="background: {{ $dotColor[$key] }}"></span> {{ $label }}
                    <span class="count">{{ $counts[$key] ?? 0 }}</span>
                </a>
            @endforeach

            @if (filled(request('q')))
                <span class="search-note ms-1">
                    Kết quả cho “{{ request('q') }}”
                    <a href="{{ request()->fullUrlWithQuery(['q' => null, 'page' => null]) }}" class="ms-1" title="Bỏ tìm kiếm"><i class="bi bi-x-lg"></i></a>
                </span>
            @endif
        </div>
    </div>

    @if ($orders->isEmpty())
        <div class="empty-state">
            <div class="big">🧾</div>
            <p class="fw-bold mb-1">Không có đơn hàng phù hợp.</p>
            <p class="text-muted mb-3">Chọn trạng thái khác hoặc lập đơn hàng mới.</p>
            <a href="{{ route('orders.create') }}" class="btn btn-brand">Lập đơn hàng</a>
        </div>
    @else
        <div class="table-wrap">
            <div class="table-responsive">
                <table class="table table-hover table-products">
                    <thead>
                    <tr>
                        <th class="ps-3">Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Ngày lập</th>
                        <th class="text-center">Số mặt hàng</th>
                        <th class="text-end">Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th class="text-end pe-3"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($orders as $o)
                        <tr>
                            <td class="ps-3"><a href="{{ route('orders.show', $o) }}" class="fw-bold">{{ $o->order_code }}</a></td>
                            <td>
                                <div class="fw-bold">{{ $o->customer->customer_name }}</div>
                                <div class="code">{{ $o->customer->phoneFormatted() }}</div>
                            </td>
                            <td>{{ $o->order_date->format('d/m/Y H:i') }}</td>
                            <td class="text-center">{{ $o->details_count }}</td>
                            <td class="text-end money fw-bold {{ $o->status === 'cancelled' ? 'text-decoration-line-through text-muted' : '' }}">
                                {{ \App\Models\Product::vnd($o->total_amount) }}
                            </td>
                            <td><span class="badge rounded-pill text-bg-{{ $o->statusColor() }}">{{ $o->statusLabel() }}</span></td>
                            <td class="text-end pe-3">
                                <a href="{{ route('orders.show', $o) }}" class="btn btn-sm btn-outline-secondary">Chi tiết</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="pager-row">
            <div class="text-muted small">Hiển thị {{ $orders->firstItem() }}–{{ $orders->lastItem() }} / {{ $orders->total() }} đơn hàng</div>
            {{ $orders->links() }}
        </div>
    @endif
@endsection
