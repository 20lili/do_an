@extends('layouts.app')

@section('title', $customer->customer_name)
@section('breadcrumb')
    <a href="{{ route('customers.index') }}">Khách hàng</a> / {{ $customer->code() }}
@endsection
@section('page-title', 'Hồ sơ khách hàng')

@section('page-actions')
    @if ($stats['orders'] === 0 && auth()->user()->can('delete-customers'))
        <form action="{{ route('customers.destroy', $customer) }}" method="POST"
              onsubmit="return confirm('Xóa khách hàng ' + {{ \Illuminate\Support\Js::from($customer->customer_name) }} + '?');">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Xóa</button>
        </form>
    @endif
    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i> Chỉnh sửa</a>
    @if (Route::has('orders.create'))
        <a href="{{ route('orders.create', ['customer_id' => $customer->id]) }}" class="btn btn-brand"><i class="bi bi-cart-plus"></i> Lập đơn</a>
    @endif
@endsection

@section('content')
    <div class="row g-4">
        {{-- ===== Thông tin khách hàng ===== --}}
        <div class="col-lg-4">
            <div class="card profile-card">
                <div class="avatar lg c{{ $customer->id % 5 }} mx-auto">{{ $customer->initials() }}</div>
                <div class="name">{{ $customer->customer_name }}</div>
                <div class="code">{{ $customer->code() }}</div>

                <ul class="info-list">
                    <li><i class="bi bi-telephone"></i><span class="money">{{ $customer->phoneFormatted() }}</span></li>
                    <li><i class="bi bi-envelope"></i><span class="{{ $customer->email ? '' : 'muted' }}">{{ $customer->email ?: 'Chưa có email' }}</span></li>
                    <li><i class="bi bi-geo-alt"></i><span class="{{ $customer->address ? '' : 'muted' }}">{{ $customer->address ?: 'Chưa có địa chỉ' }}</span></li>
                    <li><i class="bi bi-calendar3"></i><span>Khách hàng từ {{ $customer->created_at?->format('d/m/Y') }}</span></li>
                </ul>

                @if ($customer->note)
                    <div class="note-box"><i class="bi bi-sticky me-1"></i>{{ $customer->note }}</div>
                @endif
            </div>
        </div>

        {{-- ===== Thống kê + lịch sử đơn hàng ===== --}}
        <div class="col-lg-8">
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="fact"><div class="fact-label">Số đơn hàng</div><div class="fact-value">{{ $stats['orders'] }}</div></div>
                </div>
                <div class="col-sm-4">
                    <div class="fact"><div class="fact-label">Tổng chi tiêu</div><div class="fact-value money">{{ \App\Models\Product::vnd($stats['spent']) }}</div></div>
                </div>
                <div class="col-sm-4">
                    <div class="fact"><div class="fact-label">Đơn gần nhất</div>
                        <div class="fact-value">{{ $stats['last_order'] ? \Illuminate\Support\Carbon::parse($stats['last_order'])->format('d/m/Y') : '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="panel-title">Lịch sử đơn hàng</div>
            @if ($orders->isEmpty())
                <div class="empty-state">
                    <div class="big">🧾</div>
                    <p class="fw-bold mb-1">Khách hàng chưa có đơn hàng nào.</p>
                    <p class="text-muted mb-0">Đơn hàng lập cho khách này ở module Đơn hàng sẽ hiển thị tại đây.</p>
                </div>
            @else
                <div class="table-wrap">
                    <div class="table-responsive">
                        <table class="table table-hover table-products">
                            <thead>
                            <tr>
                                <th class="ps-3">Mã đơn</th>
                                <th>Ngày lập</th>
                                <th class="text-center">Số mặt hàng</th>
                                <th>Trạng thái</th>
                                <th class="text-end pe-3">Tổng tiền</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($orders as $o)
                                <tr>
                                    <td class="ps-3 fw-bold">
                                        @if (Route::has('orders.show'))
                                            <a href="{{ route('orders.show', $o) }}">{{ $o->order_code }}</a>
                                        @else
                                            {{ $o->order_code }}
                                        @endif
                                    </td>
                                    <td>{{ $o->order_date?->format('d/m/Y H:i') }}</td>
                                    <td class="text-center">{{ $o->details_count }}</td>
                                    <td><span class="badge rounded-pill text-bg-{{ $o->statusColor() }}">{{ $o->statusLabel() }}</span></td>
                                    <td class="text-end pe-3 money fw-bold {{ $o->status === 'cancelled' ? 'text-decoration-line-through text-muted' : '' }}">
                                        {{ \App\Models\Product::vnd($o->total_amount) }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="pager-row">
                    <div class="text-muted small">Tổng chi tiêu không tính các đơn đã hủy.</div>
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
