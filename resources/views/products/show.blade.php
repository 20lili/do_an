@extends('layouts.app')

@section('title', $product->product_name)
@section('breadcrumb')
    <a href="{{ route('products.index') }}">Sản phẩm</a> / {{ $product->category->category_name }}
@endsection
@section('page-title', $product->product_name)

@section('page-actions')
    @can('manage-products')
    <form action="{{ route('products.toggle-status', $product) }}" method="POST">
        @csrf @method('PATCH')
        <button class="btn btn-outline-secondary">
            @if ($product->status === 'active')
                <i class="bi bi-pause-fill"></i> Ngừng kinh doanh
            @else
                <i class="bi bi-play-fill"></i> Kinh doanh lại
            @endif
        </button>
    </form>
    @endcan
    @if (Route::has('inventory.stock-card'))
        <a href="{{ route('inventory.stock-card', $product) }}" class="btn btn-outline-secondary"><i class="bi bi-journal-text"></i> Thẻ kho</a>
    @endif
    @can('manage-products')
        <a href="{{ route('products.edit', $product) }}" class="btn btn-brand"><i class="bi bi-pencil"></i> Chỉnh sửa</a>
    @endcan
@endsection

@section('content')
    @php
        [$stockLabel, $stockColor] = $product->stockBadge();
        $img = $product->imageUrl();
    @endphp

    <div class="row g-4">
        <div class="col-md-5 col-lg-4">
            <div class="image-preview">
                @if ($img)
                    <img src="{{ $img }}" alt="{{ $product->product_name }}">
                @else
                    <div>
                        <div class="p-emoji">{{ $product->category->emoji() }}</div>
                        <small>Chưa có ảnh – lưu ảnh tên <b>{{ $product->product_code }}.jpg</b><br>vào public/images/products</small>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-md-7 col-lg-8">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge rounded-pill text-bg-light border code">{{ $product->product_code }}</span>
                <span class="badge rounded-pill {{ $product->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $product->statusLabel() }}</span>
                <span class="badge rounded-pill text-bg-{{ $stockColor }}">{{ $stockLabel }}</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="fact"><div class="fact-label">Giá bán</div><div class="fact-value money">{{ \App\Models\Product::vnd($product->selling_price) }}</div></div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="fact"><div class="fact-label">Giá nhập</div><div class="fact-value money">{{ \App\Models\Product::vnd($product->import_price) }}</div></div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="fact"><div class="fact-label">Lãi gộp / {{ mb_strtolower($product->unit) }}</div><div class="fact-value money">{{ \App\Models\Product::vnd($product->margin()) }}</div></div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="fact"><div class="fact-label">Tồn kho</div><div class="fact-value">{{ $product->stock_quantity }} {{ mb_strtolower($product->unit) }}</div></div>
                </div>
            </div>

            <div class="card p-3 p-md-4">
                <div class="panel-title">Thông tin chi tiết</div>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Danh mục</dt>
                    <dd class="col-sm-8">{{ $product->category->category_name }}</dd>
                    <dt class="col-sm-4">Đơn vị tính</dt>
                    <dd class="col-sm-8">{{ $product->unit }}</dd>
                    <dt class="col-sm-4">Mô tả</dt>
                    <dd class="col-sm-8">{{ $product->description ?: '—' }}</dd>
                    <dt class="col-sm-4">Ngày tạo</dt>
                    <dd class="col-sm-8">{{ $product->created_at?->format('d/m/Y H:i') }}</dd>
                    <dt class="col-sm-4">Cập nhật lần cuối</dt>
                    <dd class="col-sm-8 mb-0">{{ $product->updated_at?->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>
@endsection
