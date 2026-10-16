@extends('layouts.app')

@section('title', 'Thẻ kho ' . $product->product_code)
@section('breadcrumb')
    <a href="{{ route('inventory.index') }}">Kho hàng</a> / Thẻ kho
@endsection
@section('page-title', 'Thẻ kho: ' . $product->product_name)
@section('page-subtitle', $product->product_code . ' · ' . $product->category->category_name . ' · đơn vị: ' . mb_strtolower($product->unit))

@section('page-actions')
    <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary"><i class="bi bi-box-seam"></i> Xem sản phẩm</a>
    @if ($product->status === 'active' && auth()->user()->can('receive-stock'))
        <a href="{{ route('inventory.receive', ['product_id' => $product->id]) }}" class="btn btn-brand"><i class="bi bi-box-arrow-in-down"></i> Nhập kho</a>
    @endif
@endsection

@php [$label, $color] = $product->stockBadge(); @endphp

@section('content')
    @include('inventory._nav')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3"><div class="fact"><div class="fact-label">Tồn kho hiện tại</div><div class="fact-value">{{ $product->stock_quantity }}</div><span class="badge rounded-pill text-bg-{{ $color }}">{{ $label }}</span></div></div>
                <div class="col-6 col-md-3"><div class="fact"><div class="fact-label">Tổng nhập</div><div class="fact-value tx-in">+{{ $stats['in'] }}</div></div></div>
                <div class="col-6 col-md-3"><div class="fact"><div class="fact-label">Tổng xuất</div><div class="fact-value tx-out">−{{ $stats['out'] }}</div></div></div>
                <div class="col-6 col-md-3"><div class="fact"><div class="fact-label">Giá trị tồn</div><div class="fact-value money">{{ \App\Models\Product::vnd($product->stock_quantity * (float) $product->import_price) }}</div></div></div>
            </div>

            <div class="{{ $stats['balanced'] ? 'ledger-ok' : 'ledger-bad' }} mb-3">
                @if ($stats['balanced'])
                    <i class="bi bi-check-circle-fill me-1"></i> Sổ kho khớp: tổng nhập ({{ $stats['in'] }}) − tổng xuất ({{ $stats['out'] }}) = tồn kho hiện tại ({{ $product->stock_quantity }}).
                @else
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Sổ kho lệch: tổng nhập − tổng xuất = {{ $stats['in'] - $stats['out'] }}, tồn kho hệ thống = {{ $product->stock_quantity }}. Hãy kiểm kê để điều chỉnh.
                @endif
            </div>

            <div class="panel-title">Diễn biến nhập – xuất (mới nhất ở trên)</div>
            @if ($rows->isEmpty())
                <div class="empty-state"><p class="fw-bold mb-0">Sản phẩm chưa có giao dịch kho.</p></div>
            @else
                <div class="table-wrap">
                    <div class="table-responsive">
                        <table class="table table-products mb-0">
                            <thead>
                            <tr>
                                <th class="ps-3">Thời gian</th>
                                <th>Diễn giải</th>
                                <th class="text-end">Nhập</th>
                                <th class="text-end">Xuất</th>
                                <th class="text-end pe-3">Tồn sau GD</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($rows as $t)
                                <tr>
                                    <td class="ps-3 small text-nowrap">{{ $t->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="small">
                                        {{ $t->note }}
                                        @if ($t->order) <a href="{{ route('orders.show', $t->order) }}" class="ms-1 small" title="Xem đơn {{ $t->order->order_code }}">xem đơn <i class="bi bi-box-arrow-up-right"></i></a>@endif
                                    </td>
                                    <td class="text-end tx-in">{{ $t->isIn() ? '+' . $t->quantity : '' }}</td>
                                    <td class="text-end tx-out">{{ $t->isIn() ? '' : '−' . $t->quantity }}</td>
                                    <td class="text-end pe-3 balance-col">{{ $t->balance }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- ===== Kiểm kê ===== --}}
        <div class="col-lg-4">
            <div class="card p-3 p-md-4">
                <div class="panel-title">Kiểm kê &amp; điều chỉnh</div>
                <p class="small text-muted">Nhập số lượng đếm được thực tế. Hệ thống tự ghi giao dịch nhập hoặc xuất bằng phần chênh lệch.</p>
                @if ($errors->any())
                    <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
                @endif
                @can('adjust-stock')
                <form action="{{ route('inventory.adjust', $product) }}" method="POST" novalidate>
                    @csrf
                    <label class="form-label" for="actual_quantity">Số lượng thực tế</label>
                    <input type="number" min="0" id="actual_quantity" name="actual_quantity" class="form-control mb-1"
                           value="{{ old('actual_quantity', $product->stock_quantity) }}">
                    <div class="form-text mb-3">Hệ thống đang ghi nhận: <b>{{ $product->stock_quantity }}</b></div>
                    <label class="form-label" for="reason">Lý do</label>
                    <input type="text" id="reason" name="reason" maxlength="200" class="form-control mb-3"
                           value="{{ old('reason') }}" placeholder="VD: Hàng hư hỏng, đếm lại cuối tháng…">
                    <button class="btn btn-outline-secondary w-100"><i class="bi bi-clipboard-check"></i> Lưu điều chỉnh</button>
                </form>
                @else
                    <div class="perm-note w-100"><i class="bi bi-lock"></i> Chỉ quản trị viên được điều chỉnh tồn kho sau kiểm kê.</div>
                @endcan
            </div>
        </div>
    </div>
@endsection
