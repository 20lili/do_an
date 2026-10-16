@extends('layouts.app')

@section('title', 'Sản phẩm')
@section('page-title', 'Sản phẩm')
@section('page-subtitle', $summary['total'] . ' mặt hàng trong ' . $categories->count() . ' danh mục')

@section('page-actions')
    @can('manage-products')
        <a href="{{ route('products.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> Thêm sản phẩm
        </a>
    @endcan
@endsection

@php
    // Tạo đường dẫn lọc giữ nguyên các tham số khác, quay về trang 1
    $filterUrl = fn (array $params) => request()->fullUrlWithQuery($params + ['page' => null]);
    $catId  = request('category_id');
    $stock  = request('stock');
    $status = request('status');
    $isFiltering = request()->hasAny(['q', 'category_id', 'stock', 'status']);
@endphp

@section('content')
    {{-- ===== Hàng 1: lọc theo danh mục ===== --}}
    <div class="chip-row" aria-label="Lọc theo danh mục">
        <a href="{{ $filterUrl(['category_id' => null, 'stock' => null, 'status' => null]) }}" class="chip {{ blank($catId) && blank($stock) && blank($status) ? 'active' : '' }}">
            Tất cả <span class="count">{{ $summary['total'] }}</span>
        </a>
        @foreach ($categories as $c)
            <a href="{{ $filterUrl(['category_id' => $c->id, 'stock' => null, 'status' => null]) }}" class="chip {{ $catId == $c->id ? 'active' : '' }}">
                <span aria-hidden="true">{{ $c->emoji() }}</span> {{ $c->category_name }}
                <span class="count">{{ $c->products_count }}</span>
            </a>
        @endforeach
    </div>

    {{-- ===== Hàng 2: lọc nhanh tồn kho / trạng thái + đổi kiểu xem ===== --}}
    <div class="toolbar">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ $filterUrl(['stock' => $stock === 'low' ? null : 'low', 'status' => null, 'category_id' => null]) }}" class="chip chip-pink {{ $stock === 'low' ? 'active' : '' }}">
                <i class="bi bi-exclamation-circle"></i> Sắp hết <span class="count">{{ $summary['low'] }}</span>
            </a>
            <a href="{{ $filterUrl(['stock' => $stock === 'out' ? null : 'out', 'status' => null, 'category_id' => null]) }}" class="chip chip-red {{ $stock === 'out' ? 'active' : '' }}">
                <i class="bi bi-x-circle"></i> Hết hàng <span class="count">{{ $summary['out'] }}</span>
            </a>
            <a href="{{ $filterUrl(['status' => $status === 'inactive' ? null : 'inactive', 'stock' => null, 'category_id' => null]) }}" class="chip chip-gray {{ $status === 'inactive' ? 'active' : '' }}">
                <i class="bi bi-pause-circle"></i> Ngừng kinh doanh <span class="count">{{ $summary['inactive'] }}</span>
            </a>

            @if ($isFiltering)
                <a href="{{ route('products.index', ['view' => $view === 'table' ? 'table' : null]) }}" class="chip">
                    <i class="bi bi-x-lg"></i> Bỏ lọc
                </a>
            @endif

            @if (filled(request('q')))
                <span class="search-note ms-1">
                    Kết quả cho “{{ request('q') }}”
                    <a href="{{ $filterUrl(['q' => null]) }}" class="ms-1" title="Bỏ tìm kiếm"><i class="bi bi-x-lg"></i></a>
                </span>
            @endif
        </div>

        <div class="view-toggle" role="group" aria-label="Kiểu hiển thị">
            <a href="{{ $filterUrl(['view' => null]) }}" class="{{ $view === 'grid' ? 'active' : '' }}"><i class="bi bi-grid-3x3-gap"></i> Lưới</a>
            <a href="{{ $filterUrl(['view' => 'table']) }}" class="{{ $view === 'table' ? 'active' : '' }}"><i class="bi bi-list-ul"></i> Bảng</a>
        </div>
    </div>

    @if ($products->isEmpty())
        <div class="empty-state">
            <div class="big">🐾</div>
            <p class="fw-bold mb-1">Không có sản phẩm nào khớp bộ lọc đang chọn.</p>
            <p class="text-muted mb-3">Thử bỏ bớt điều kiện lọc hoặc chọn danh mục khác.</p>
            <a href="{{ route('products.index') }}" class="btn btn-brand">Xem tất cả sản phẩm</a>
        </div>

    @elseif ($view === 'grid')
        {{-- ===== Dạng lưới thẻ ===== --}}
        <div class="product-grid">
            @foreach ($products as $p)
                @php $img = $p->imageUrl(); @endphp
                <article class="p-card {{ $p->status === 'inactive' ? 'is-inactive' : '' }}">
                    <div class="p-media {{ $img ? '' : 'no-image' }}">
                        @if ($img)
                            <img src="{{ $img }}" alt="{{ $p->product_name }}" loading="lazy">
                        @else
                            <span class="p-emoji" aria-hidden="true">{{ $p->category->emoji() }}</span>
                        @endif

                        <span class="p-unit">{{ $p->unit }}</span>

                        @if ($p->status === 'inactive')
                            <span class="p-tag off">Ngừng bán</span>
                        @elseif ($p->stock_quantity <= 0)
                            <span class="p-tag out">Hết hàng</span>
                        @elseif ($p->stock_quantity < \App\Models\Product::LOW_STOCK_THRESHOLD)
                            <span class="p-tag low">Sắp hết</span>
                        @endif
                    </div>

                    <div class="p-body">
                        <div class="p-cat"><span>{{ $p->category->category_name }}</span><span class="code ms-2">{{ $p->product_code }}</span></div>
                        <a href="{{ route('products.show', $p) }}" class="p-name">{{ $p->product_name }}</a>
                        <div class="p-price money">{{ \App\Models\Product::vnd($p->selling_price) }}</div>
                        <div class="p-cost money">Giá nhập {{ \App\Models\Product::vnd($p->import_price) }}</div>

                        <div class="p-foot">
                            @php
                                $cls = $p->stock_quantity <= 0 ? 'out' : ($p->stock_quantity < \App\Models\Product::LOW_STOCK_THRESHOLD ? 'low' : 'ok');
                            @endphp
                            <span class="p-stock {{ $cls }}">Tồn: {{ $p->stock_quantity }}</span>
                            @can('manage-products')
                            <div class="p-actions">
                                <a href="{{ route('products.edit', $p) }}" class="icon-btn" title="Chỉnh sửa" aria-label="Chỉnh sửa {{ $p->product_name }}"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('products.toggle-status', $p) }}" method="POST">
                                    @csrf @method('PATCH')
                                    @if ($p->status === 'active')
                                        <button class="icon-btn" title="Ngừng kinh doanh" aria-label="Ngừng kinh doanh"><i class="bi bi-pause-fill"></i></button>
                                    @else
                                        <button class="icon-btn" title="Kinh doanh lại" aria-label="Kinh doanh lại"><i class="bi bi-play-fill"></i></button>
                                    @endif
                                </form>
                            </div>
                            @endcan
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

    @else
        {{-- ===== Dạng bảng ===== --}}
        <div class="table-wrap">
            <div class="table-responsive">
                <table class="table table-hover table-products">
                    <thead>
                    <tr>
                        <th class="ps-3">Sản phẩm</th>
                        <th>Danh mục</th>
                        <th>ĐVT</th>
                        <th class="text-end">Giá nhập</th>
                        <th class="text-end">Giá bán</th>
                        <th class="text-center">Tồn kho</th>
                        <th>Trạng thái</th>
                        <th class="text-end pe-3">Thao tác</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($products as $p)
                        @php [$stockLabel, $stockColor] = $p->stockBadge(); $img = $p->imageUrl(); @endphp
                        <tr class="{{ $p->status === 'inactive' ? 'row-inactive' : '' }}">
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="thumb">
                                        @if ($img) <img src="{{ $img }}" alt="" loading="lazy"> @else {{ $p->category->emoji() }} @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('products.show', $p) }}" class="fw-bold text-reset text-decoration-none">{{ $p->product_name }}</a>
                                        <div class="code">{{ $p->product_code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $p->category->category_name }}</td>
                            <td>{{ $p->unit }}</td>
                            <td class="text-end money text-muted">{{ \App\Models\Product::vnd($p->import_price) }}</td>
                            <td class="text-end money fw-bold">{{ \App\Models\Product::vnd($p->selling_price) }}</td>
                            <td class="text-center">
                                <div class="fw-bold">{{ $p->stock_quantity }}</div>
                                <span class="badge rounded-pill text-bg-{{ $stockColor }}">{{ $stockLabel }}</span>
                            </td>
                            <td>{{ $p->statusLabel() }}</td>
                            <td class="text-end pe-3 text-nowrap">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('products.show', $p) }}" class="icon-btn" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                    @can('manage-products')
                                    <a href="{{ route('products.edit', $p) }}" class="icon-btn" title="Chỉnh sửa"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('products.toggle-status', $p) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button class="icon-btn" title="{{ $p->status === 'active' ? 'Ngừng kinh doanh' : 'Kinh doanh lại' }}">
                                            <i class="bi {{ $p->status === 'active' ? 'bi-pause-fill' : 'bi-play-fill' }}"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($products->total())
        <div class="pager-row">
            <div class="text-muted small">Hiển thị {{ $products->firstItem() }}–{{ $products->lastItem() }} / {{ $products->total() }} sản phẩm</div>
            {{ $products->links() }}
        </div>
    @endif
@endsection
