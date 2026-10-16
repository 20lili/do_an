@extends('layouts.app')

@section('title', 'Khách hàng')
@section('page-title', 'Khách hàng')
@section('page-subtitle', 'Thông tin khách hàng dùng khi lập đơn và tra cứu lịch sử mua hàng')

@section('page-actions')
    <a href="{{ route('customers.create') }}" class="btn btn-brand">
        <i class="bi bi-person-plus"></i> Thêm khách hàng
    </a>
@endsection

@php
    $sortUrl = fn ($s) => request()->fullUrlWithQuery(['sort' => $s === 'newest' ? null : $s, 'page' => null]);
@endphp

@section('content')
    {{-- ===== Số liệu nhanh ===== --}}
    <div class="stat-strip">
        <div class="stat-pill"><b>{{ $summary['total'] }}</b><span>khách hàng</span></div>
        <div class="stat-pill"><b>{{ $summary['new_month'] }}</b><span>khách mới trong tháng</span></div>
        <div class="stat-pill"><b>{{ $summary['has_orders'] }}</b><span>khách đã mua hàng</span></div>
    </div>

    {{-- ===== Sắp xếp + kết quả tìm kiếm ===== --}}
    <div class="toolbar">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ $sortUrl('newest') }}" class="chip {{ $sort === 'newest' ? 'active' : '' }}"><i class="bi bi-clock-history"></i> Mới nhất</a>
            <a href="{{ $sortUrl('name') }}"   class="chip {{ $sort === 'name' ? 'active' : '' }}"><i class="bi bi-sort-alpha-down"></i> Họ tên A–Z</a>
            <a href="{{ $sortUrl('spent') }}"  class="chip {{ $sort === 'spent' ? 'active' : '' }}"><i class="bi bi-cash-stack"></i> Chi tiêu nhiều nhất</a>

            @if (filled(request('q')))
                <span class="search-note ms-1">
                    Kết quả cho “{{ request('q') }}”
                    <a href="{{ request()->fullUrlWithQuery(['q' => null, 'page' => null]) }}" class="ms-1" title="Bỏ tìm kiếm"><i class="bi bi-x-lg"></i></a>
                </span>
            @endif
        </div>
    </div>

    @if ($customers->isEmpty())
        <div class="empty-state">
            <div class="big">🙋</div>
            <p class="fw-bold mb-1">Không tìm thấy khách hàng phù hợp.</p>
            <p class="text-muted mb-3">Kiểm tra lại tên hoặc số điện thoại, hoặc thêm khách hàng mới.</p>
            <a href="{{ route('customers.create') }}" class="btn btn-brand">Thêm khách hàng</a>
        </div>
    @else
        <div class="table-wrap">
            <div class="table-responsive">
                <table class="table table-hover table-products table-customers">
                    <thead>
                    <tr>
                        <th class="ps-3">Khách hàng</th>
                        <th>Số điện thoại</th>
                        <th>Địa chỉ</th>
                        <th class="text-center">Số đơn</th>
                        <th class="text-end">Tổng chi tiêu</th>
                        <th>Ngày tạo</th>
                        <th class="text-end pe-3">Thao tác</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($customers as $c)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar c{{ $c->id % 5 }}" aria-hidden="true">{{ $c->initials() }}</div>
                                    <div>
                                        <a href="{{ route('customers.show', $c) }}" class="fw-bold text-reset text-decoration-none">{{ $c->customer_name }}</a>
                                        <div class="code">{{ $c->code() }}@if ($c->email) <span class="ms-2">{{ $c->email }}</span>@endif</div>
                                    </div>
                                </div>
                            </td>
                            <td class="money">{{ $c->phoneFormatted() }}</td>
                            <td class="addr">{{ $c->address ?: '—' }}</td>
                            <td class="text-center fw-bold">{{ $c->orders_count }}</td>
                            <td class="text-end money fw-bold">{{ \App\Models\Product::vnd($c->total_spent ?? 0) }}</td>
                            <td class="text-muted small">{{ $c->created_at?->format('d/m/Y') }}</td>
                            <td class="text-end pe-3 text-nowrap">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('customers.show', $c) }}" class="icon-btn" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('customers.edit', $c) }}" class="icon-btn" title="Chỉnh sửa"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('customers.destroy', $c) }}" method="POST"
                                          onsubmit="return confirm('Xóa khách hàng ' + {{ \Illuminate\Support\Js::from($c->customer_name) }} + '?');">
                                        @csrf @method('DELETE')
                                        <button class="icon-btn danger"
                                                title="{{ $c->orders_count ? 'Khách đã có đơn hàng, không thể xóa' : 'Xóa khách hàng' }}"
                                                @disabled($c->orders_count)>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pager-row">
            <div class="text-muted small">Hiển thị {{ $customers->firstItem() }}–{{ $customers->lastItem() }} / {{ $customers->total() }} khách hàng</div>
            {{ $customers->links() }}
        </div>
    @endif
@endsection
