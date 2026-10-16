<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản lý cửa hàng') – PetShop Manager</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    {{-- Tệp giao diện riêng: public/css/admin.css (?v= để trình duyệt không dùng bản cũ) --}}
    <link href="{{ asset('css/admin.css') }}?v=7" rel="stylesheet">
    @stack('styles')
</head>
<body>
@php
    // Menu theo các module của đồ án; 'can' = quyền cần có để thấy mục đó (mục 5.8)
    $menu = [
        ['route' => 'dashboard',        'icon' => 'bi-grid',            'label' => 'Tổng quan'],
        ['route' => 'categories.index', 'icon' => 'bi-tags',            'label' => 'Danh mục'],
        ['route' => 'products.index',   'icon' => 'bi-box-seam',        'label' => 'Sản phẩm'],
        ['route' => 'customers.index',  'icon' => 'bi-people',          'label' => 'Khách hàng'],
        ['route' => 'orders.index',     'icon' => 'bi-receipt',         'label' => 'Đơn hàng'],
        ['route' => 'inventory.index',  'icon' => 'bi-buildings',       'label' => 'Kho hàng'],
        ['route' => 'reports.index',    'icon' => 'bi-bar-chart-line',  'label' => 'Báo cáo',  'can' => 'view-reports'],
        ['route' => 'users.index',      'icon' => 'bi-person-gear',     'label' => 'Tài khoản', 'can' => 'manage-users'],
    ];
    $me = auth()->user();
    $menu = array_filter($menu, fn ($item) => ! isset($item['can']) || $me?->can($item['can']));
@endphp

{{-- ===== Header: logo – ô tìm kiếm – người dùng ===== --}}
<header class="site-header">
    <div class="container-xl header-row">
        <a href="{{ url('/') }}" class="logo" aria-label="Trang chủ">
            <span class="logo-mark">🐾</span>
            <span class="logo-text">
                <strong>PetShop</strong>
                <small>Quản lý đơn hàng &amp; kho</small>
            </span>
        </a>

        @php
            // Ô tìm kiếm tìm trong module đang mở: khách hàng hoặc sản phẩm
            [$searchAction, $searchHint] = match (true) {
                request()->routeIs('customers.*') => [route('customers.index'), 'Tìm khách hàng theo tên, SĐT, email hoặc mã KH…'],
                request()->routeIs('orders.*')    => [route('orders.index'), 'Tìm đơn theo mã đơn, tên hoặc SĐT khách hàng…'],
                request()->routeIs('inventory.*') => [route('inventory.index'), 'Tìm tồn kho theo tên hoặc mã sản phẩm…'],
                default                           => [route('products.index'), 'Tìm sản phẩm theo tên hoặc mã…'],
            };
        @endphp
        <form action="{{ $searchAction }}" method="GET" class="header-search" role="search">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $searchHint }}" aria-label="Tìm kiếm">
            <button type="submit" aria-label="Tìm"><i class="bi bi-search"></i></button>
        </form>

        @if ($me)
            {{-- Người dùng đang đăng nhập: vai trò, đổi mật khẩu, đăng xuất --}}
            <div class="dropdown header-user">
                <button class="user-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="avatar sm c{{ $me->id % 5 }}">{{ $me->initial() }}</span>
                    <span class="d-none d-md-flex flex-column text-start lh-sm">
                        <b>{{ $me->name }}</b>
                        <small class="role-text {{ $me->role }}">{{ $me->roleLabel() }}</small>
                    </span>
                    <i class="bi bi-chevron-down small"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="px-3 py-2 small text-muted">Đăng nhập: <b class="text-dark">{{ $me->username }}</b></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('password.edit') }}"><i class="bi bi-key me-2"></i>Đổi mật khẩu</a></li>
                    @can('manage-users')
                        <li><a class="dropdown-item" href="{{ route('users.index') }}"><i class="bi bi-person-gear me-2"></i>Quản lý tài khoản</a></li>
                    @endcan
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</button>
                        </form>
                    </li>
                </ul>
            </div>
        @endif
    </div>

    {{-- ===== Thanh menu tím ===== --}}
    <nav class="main-nav" aria-label="Chức năng">
        <div class="container-xl nav-scroll">
            @foreach ($menu as $item)
                @php $active = request()->routeIs(Str::before($item['route'], '.') . '*'); @endphp
                @if (Route::has($item['route']))
                    <a href="{{ route($item['route']) }}" class="nav-link-item {{ $active ? 'active' : '' }}">
                        <i class="bi {{ $item['icon'] }}"></i>{{ $item['label'] }}
                    </a>
                @else
                    <span class="nav-link-item is-soon" title="Sẽ xây dựng ở các mục tiếp theo">
                        <i class="bi {{ $item['icon'] }}"></i>{{ $item['label'] }}
                    </span>
                @endif
            @endforeach
        </div>
    </nav>
</header>

<main class="container-xl page">
    {{-- Tiêu đề trang + nút thao tác --}}
    <div class="page-head">
        <div>
            @hasSection('breadcrumb')
                <nav class="crumbs">@yield('breadcrumb')</nav>
            @endif
            <h1 class="page-title">@yield('page-title')</h1>
            @hasSection('page-subtitle')
                <p class="page-subtitle">@yield('page-subtitle')</p>
            @endif
        </div>
        <div class="page-actions">@yield('page-actions')</div>
    </div>

    {{-- Khu vực thông báo --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    @endif

    @yield('content')
</main>

<footer class="site-footer">
    <div class="container-xl">PetShop Manager – Hệ thống quản lý đơn hàng tích hợp quản lý kho</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
