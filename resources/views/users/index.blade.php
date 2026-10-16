@extends('layouts.app')

@section('title', 'Tài khoản')
@section('page-title', 'Tài khoản người dùng')
@section('page-subtitle', 'Cấp tài khoản, vai trò và quyền sử dụng cho quản trị viên, nhân viên cửa hàng')

@section('page-actions')
    <a href="{{ route('users.create') }}" class="btn btn-brand"><i class="bi bi-person-plus"></i> Thêm tài khoản</a>
@endsection

@php
    use App\Models\User;
    $roleUrl = fn ($r) => request()->fullUrlWithQuery(['role' => $r]);
@endphp

@section('content')
    <div class="toolbar">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ $roleUrl(null) }}" class="chip {{ blank(request('role')) ? 'active' : '' }}">Tất cả <span class="count">{{ $counts->sum() }}</span></a>
            @foreach (User::ROLES as $key => $label)
                <a href="{{ $roleUrl($key) }}" class="chip {{ request('role') === $key ? 'active' : '' }}">{{ $label }} <span class="count">{{ $counts[$key] ?? 0 }}</span></a>
            @endforeach
        </div>
        <form method="GET" class="d-flex gap-2">
            @if (request('role')) <input type="hidden" name="role" value="{{ request('role') }}"> @endif
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm theo họ tên, tên đăng nhập…" style="min-width: 240px">
            <button class="btn btn-sm btn-outline-secondary">Tìm</button>
        </form>
    </div>

    @if ($users->isEmpty())
        <div class="empty-state"><div class="big">🔎</div><p class="fw-bold mb-0">Không có tài khoản phù hợp.</p></div>
    @else
        <div class="table-wrap">
            <div class="table-responsive">
                <table class="table table-products align-middle">
                    <thead>
                    <tr>
                        <th class="ps-3">Người dùng</th>
                        <th>Vai trò</th>
                        <th>Quyền bổ sung</th>
                        <th class="text-center">Đơn đã lập</th>
                        <th class="text-center">Phiếu kho</th>
                        <th>Trạng thái</th>
                        <th class="text-end pe-3">Thao tác</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($users as $u)
                        <tr class="{{ $u->is_active ? '' : 'row-muted' }}">
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar c{{ $u->id % 5 }}">{{ $u->initial() }}</span>
                                    <div>
                                        <div class="fw-bold">{{ $u->name }}
                                            @if ($u->is(auth()->user())) <span class="badge rounded-pill text-bg-light border ms-1">Bạn</span> @endif
                                        </div>
                                        <div class="code">{{ $u->username }}{{ $u->email ? ' · ' . $u->email : '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="role-badge {{ $u->role }}">{{ $u->roleLabel() }}</span></td>
                            <td>
                                @if ($u->isAdmin())
                                    <span class="text-muted small">Toàn quyền</span>
                                @else
                                    @forelse ($u->permissions ?? [] as $perm)
                                        <span class="perm-chip">{{ User::PERMISSIONS[$perm] ?? $perm }}</span>
                                    @empty
                                        <span class="text-muted small">Quyền cơ bản</span>
                                    @endforelse
                                @endif
                            </td>
                            <td class="text-center">{{ $u->orders_count }}</td>
                            <td class="text-center">{{ $u->inventory_transactions_count }}</td>
                            <td>
                                @if ($u->is_active)
                                    <span class="badge rounded-pill text-bg-success">Đang hoạt động</span>
                                @else
                                    <span class="badge rounded-pill text-bg-secondary">Đã khóa</span>
                                @endif
                            </td>
                            <td class="text-end pe-3 text-nowrap">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('users.edit', $u) }}" class="icon-btn" title="Chỉnh sửa"><i class="bi bi-pencil"></i></a>
                                    @unless ($u->is(auth()->user()))
                                        <form action="{{ route('users.toggle-status', $u) }}" method="POST"
                                              onsubmit="return confirm({{ \Illuminate\Support\Js::from(($u->is_active ? 'Khóa' : 'Mở khóa') . ' tài khoản ' . $u->username . '?') }});">
                                            @csrf @method('PATCH')
                                            <button class="icon-btn {{ $u->is_active ? 'danger' : '' }}" title="{{ $u->is_active ? 'Khóa tài khoản' : 'Mở khóa' }}">
                                                <i class="bi {{ $u->is_active ? 'bi-lock' : 'bi-unlock' }}"></i>
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <p class="small text-muted mt-3 mb-0">
            <i class="bi bi-info-circle"></i> Tài khoản không xóa được vì gắn với đơn hàng và phiếu kho đã lập; khi nhân viên nghỉ việc, hãy <b>khóa</b> tài khoản.
        </p>
    @endif
@endsection
