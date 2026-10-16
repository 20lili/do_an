@extends('layouts.app')

@section('title', 'Lịch sử nhập – xuất')
@section('page-title', 'Quản lý kho')
@section('page-subtitle', 'Lịch sử mọi biến động tồn kho: nhập kho, xuất theo đơn hàng, hoàn kho và điều chỉnh kiểm kê')

@php
    $type = request('type');
    $url = fn (array $p) => request()->fullUrlWithQuery($p + ['page' => null]);
@endphp

@section('content')
    @include('inventory._nav')

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    {{-- ===== Bộ lọc ===== --}}
    <form method="GET" class="card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Sản phẩm</label>
                <select name="product_id" class="form-select">
                    <option value="">Tất cả sản phẩm</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" @selected(request('product_id') == $p->id)>{{ $p->product_code }} – {{ $p->product_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Loại</label>
                <select name="type" class="form-select">
                    <option value="">Nhập và xuất</option>
                    <option value="IN"  @selected($type === 'IN')>Chỉ nhập (IN)</option>
                    <option value="OUT" @selected($type === 'OUT')>Chỉ xuất (OUT)</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Từ ngày</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Đến ngày</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-control">
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <button class="btn btn-brand flex-fill"><i class="bi bi-funnel"></i> Lọc</button>
                @if (request()->hasAny(['product_id', 'type', 'from', 'to']))
                    <a href="{{ route('inventory.transactions') }}" class="btn btn-outline-secondary" title="Bỏ lọc"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </div>
    </form>

    <div class="stat-strip">
        <div class="stat-pill"><b class="tx-in">+{{ number_format($totals['IN'] ?? 0, 0, ',', '.') }}</b><span>tổng nhập</span></div>
        <div class="stat-pill"><b class="tx-out">−{{ number_format($totals['OUT'] ?? 0, 0, ',', '.') }}</b><span>tổng xuất</span></div>
        <div class="stat-pill"><b>{{ $transactions->total() }}</b><span>giao dịch theo bộ lọc</span></div>
    </div>

    @if ($transactions->isEmpty())
        <div class="empty-state"><div class="big">🗂️</div><p class="fw-bold mb-0">Không có giao dịch kho trong khoảng đã chọn.</p></div>
    @else
        <div class="table-wrap">
            <div class="table-responsive">
                <table class="table table-hover table-products">
                    <thead>
                    <tr>
                        <th class="ps-3">Thời gian</th>
                        <th>Sản phẩm</th>
                        <th>Loại</th>
                        <th class="text-end">Số lượng</th>
                        <th>Đơn hàng</th>
                        <th>Ghi chú</th>
                        <th class="pe-3">Người thực hiện</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($transactions as $t)
                        <tr>
                            <td class="ps-3 small text-nowrap">{{ $t->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('inventory.stock-card', $t->product) }}" class="fw-bold text-reset text-decoration-none">{{ $t->product->product_name }}</a>
                                <div class="code">{{ $t->product->product_code }}</div>
                            </td>
                            <td><span class="tx-badge {{ $t->isIn() ? 'in' : 'out' }}">{{ $t->isIn() ? 'Nhập' : 'Xuất' }}</span></td>
                            <td class="text-end {{ $t->isIn() ? 'tx-in' : 'tx-out' }}">{{ $t->signedQuantity() }}</td>
                            <td>@if ($t->order)<a href="{{ route('orders.show', $t->order) }}" class="fw-bold">{{ $t->order->order_code }}</a>@else<span class="text-muted">—</span>@endif</td>
                            <td class="small text-muted" style="max-width: 320px">{{ $t->note }}</td>
                            <td class="pe-3 small">{{ $t->user->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="pager-row">
            <div class="text-muted small">Hiển thị {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }} / {{ $transactions->total() }} giao dịch</div>
            {{ $transactions->links() }}
        </div>
    @endif
@endsection
