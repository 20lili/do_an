@extends('layouts.app')

@section('title', 'Đơn ' . $order->order_code)
@section('breadcrumb')
    <a href="{{ route('orders.index') }}">Đơn hàng</a> / {{ $order->order_code }}
@endsection
@section('page-title', 'Đơn hàng ' . $order->order_code)
@section('page-subtitle', 'Lập lúc ' . $order->order_date->format('H:i d/m/Y') . ' bởi ' . ($order->user->name ?? '—'))

@php
    use App\Models\Order;
    $st = $order->status;
    $isCancelled = $st === Order::CANCELLED;
    $reached = fn ($step) => match ($step) {
        'pending'   => true,
        'confirmed' => in_array($st, [Order::CONFIRMED, Order::COMPLETED], true),
        'completed' => $st === Order::COMPLETED,
    };
@endphp

@section('page-actions')
    {{-- Mục 5.8: Xác nhận (xuất kho) và Hủy đơn chỉ dành cho Admin hoặc nhân viên được cấp quyền --}}
    @if ($st === Order::PENDING)
        @can('confirm-orders')
            <form action="{{ route('orders.cancel', $order) }}" method="POST"
                  onsubmit="const r = prompt('Lý do hủy đơn {{ $order->order_code }}:', 'Khách đổi ý'); if (r === null) return false; this.reason.value = r;">
                @csrf @method('PATCH') <input type="hidden" name="reason">
                <button class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Hủy đơn</button>
            </form>
            <form action="{{ route('orders.confirm', $order) }}" method="POST">
                @csrf @method('PATCH')
                <button class="btn btn-brand"><i class="bi bi-box-arrow-right"></i> Xác nhận đơn &amp; xuất kho</button>
            </form>
        @else
            <span class="perm-note"><i class="bi bi-lock"></i> Đơn chờ người có quyền xác nhận</span>
        @endcan
    @elseif ($st === Order::CONFIRMED)
        @can('confirm-orders')
            <form action="{{ route('orders.cancel', $order) }}" method="POST"
                  onsubmit="const r = prompt('Đơn đã xuất kho. Hủy đơn sẽ hoàn lại tồn kho.\nLý do hủy:', 'Khách không nhận hàng'); if (r === null) return false; this.reason.value = r;">
                @csrf @method('PATCH') <input type="hidden" name="reason">
                <button class="btn btn-outline-danger"><i class="bi bi-arrow-counterclockwise"></i> Hủy &amp; hoàn kho</button>
            </form>
        @endcan
        <form action="{{ route('orders.complete', $order) }}" method="POST">
            @csrf @method('PATCH')
            <button class="btn btn-brand"><i class="bi bi-check2-all"></i> Hoàn thành đơn</button>
        </form>
    @endif
@endsection

@section('content')
    {{-- ===== Thanh trạng thái ===== --}}
    <div class="steps {{ $isCancelled ? 'cancelled' : '' }}">
        @foreach (['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận · đã xuất kho', 'completed' => 'Hoàn thành'] as $key => $label)
            @php
                $done = $reached($key);
                $current = ! $isCancelled && $st === $key;
            @endphp
            @if (! $loop->first) <div class="step-line {{ $done ? 'done' : '' }}"></div> @endif
            <div class="step {{ $done ? 'done' : '' }} {{ $current ? 'current' : '' }}">
                <span class="ball">{!! $done && ! $current ? '<i class="bi bi-check-lg"></i>' : $loop->iteration !!}</span>
                <span class="lbl">{{ $label }}</span>
            </div>
        @endforeach
    </div>
    @if ($isCancelled)
        <div class="alert alert-secondary"><i class="bi bi-x-octagon me-1"></i> Đơn hàng đã bị hủy.</div>
    @endif

    <div class="row g-4">
        {{-- ===== Chi tiết sản phẩm ===== --}}
        <div class="col-lg-8">
            <div class="table-wrap mb-4">
                <div class="table-responsive">
                    <table class="table table-products mb-0">
                        <thead>
                        <tr>
                            <th class="ps-3">Sản phẩm</th>
                            <th class="text-center">SL</th>
                            <th class="text-end">Đơn giá</th>
                            <th class="text-end pe-3">Thành tiền</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($order->details as $d)
                            @php $img = $d->product->imageUrl(); @endphp
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="thumb">@if ($img)<img src="{{ $img }}" alt="">@else{{ $d->product->category->emoji() }}@endif</div>
                                        <div>
                                            <a href="{{ route('products.show', $d->product) }}" class="fw-bold text-reset text-decoration-none">{{ $d->product->product_name }}</a>
                                            <div class="code">{{ $d->product->product_code }} · tồn hiện tại: {{ $d->product->stock_quantity }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center fw-bold">{{ $d->quantity }}</td>
                                <td class="text-end money">{{ \App\Models\Product::vnd($d->unit_price) }}</td>
                                <td class="text-end pe-3 money fw-bold">{{ \App\Models\Product::vnd($d->subtotal) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold ps-3">Tổng tiền</td>
                            <td class="text-end pe-3 money fw-bold fs-5">{{ \App\Models\Product::vnd($order->total_amount) }}</td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- ===== Lịch sử kho của đơn ===== --}}
            <div class="panel-title">Giao dịch kho của đơn hàng</div>
            @if ($order->inventoryTransactions->isEmpty())
                <p class="text-muted">
                    @if ($st === Order::PENDING)
                        Chưa phát sinh giao dịch kho. Tồn kho sẽ được trừ khi xác nhận đơn.
                    @else
                        Đơn không phát sinh giao dịch kho.
                    @endif
                </p>
            @else
                <div class="table-wrap">
                    <table class="table table-products mb-0">
                        <thead><tr><th class="ps-3">Thời gian</th><th>Sản phẩm</th><th>Loại</th><th class="text-end">Số lượng</th><th class="pe-3">Ghi chú</th></tr></thead>
                        <tbody>
                        @foreach ($order->inventoryTransactions->sortBy('id') as $t)
                            <tr>
                                <td class="ps-3 small">{{ $t->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $t->product->product_code }}</td>
                                <td>{{ $t->isIn() ? 'Nhập (hoàn kho)' : 'Xuất kho' }}</td>
                                <td class="text-end {{ $t->isIn() ? 'tx-in' : 'tx-out' }}">{{ $t->signedQuantity() }}</td>
                                <td class="pe-3 small text-muted">{{ $t->note }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- ===== Khách hàng + ghi chú ===== --}}
        <div class="col-lg-4">
            <div class="card p-3 p-md-4 mb-3">
                <div class="panel-title">Khách hàng</div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar c{{ $order->customer->id % 5 }}">{{ $order->customer->initials() }}</div>
                    <div>
                        <a href="{{ route('customers.show', $order->customer) }}" class="fw-bold text-reset">{{ $order->customer->customer_name }}</a>
                        <div class="code">{{ $order->customer->code() }}</div>
                    </div>
                </div>
                <ul class="info-list mt-2">
                    <li><i class="bi bi-telephone"></i><span>{{ $order->customer->phoneFormatted() }}</span></li>
                    <li><i class="bi bi-geo-alt"></i><span>{{ $order->customer->address ?: 'Chưa có địa chỉ' }}</span></li>
                </ul>
            </div>
            <div class="card p-3 p-md-4">
                <div class="panel-title">Ghi chú</div>
                <div style="white-space: pre-line">{{ $order->note ?: '—' }}</div>
            </div>
        </div>
    </div>
@endsection
