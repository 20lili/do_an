```blade
@extends('layouts.app')

@section('title', 'Danh mục')
@section('page-title', 'Danh mục sản phẩm')
@section('page-subtitle', $categories->count() . ' danh mục · Phân loại sản phẩm khi tra cứu và thống kê')

@php
    $editing = isset($category) && $category->exists;
@endphp

@section('content')
    <div class="row g-4">

        {{-- Danh sách danh mục --}}
        <div class="{{ auth()->user()?->can('manage-categories') ? 'col-lg-8' : 'col-12' }}">
            <div class="table-wrap">
                <div class="table-responsive">
                    <table class="table table-products align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Danh mục</th>
                                <th class="text-center">Sản phẩm</th>
                                <th class="text-center">Tổng tồn</th>
                                <th>Trạng thái</th>
                                @can('manage-categories')
                                    <th class="text-end pe-3">Thao tác</th>
                                @endcan
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($categories as $c)
                                <tr class="{{ $c->status === 'active' ? '' : 'row-muted' }} {{ $editing && $category->is($c) ? 'row-editing' : '' }}">
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="cat-emoji">
                                                {{ method_exists($c, 'emoji') ? $c->emoji() : '📁' }}
                                            </span>

                                            <div>
                                                <a href="{{ route('products.index', ['category_id' => $c->id]) }}"
                                                   class="fw-bold text-reset text-decoration-none">
                                                    {{ $c->category_name }}
                                                </a>

                                                <div class="small text-muted">
                                                    {{ $c->description ?: 'Chưa có mô tả' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <div class="fw-bold">
                                            {{ $c->products_count ?? 0 }}
                                        </div>

                                        @if (($c->products_count ?? 0) > ($c->active_products_count ?? 0))
                                            <div class="small text-muted">
                                                {{ ($c->products_count ?? 0) - ($c->active_products_count ?? 0) }}
                                                sản phẩm ngừng kinh doanh
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        {{ number_format((int) ($c->stock_total ?? 0)) }}
                                    </td>

                                    <td>
                                        @if ($c->status === 'active')
                                            <span class="badge rounded-pill text-bg-success">
                                                Đang sử dụng
                                            </span>
                                        @else
                                            <span class="badge rounded-pill text-bg-secondary">
                                                Ngừng sử dụng
                                            </span>
                                        @endif
                                    </td>

                                    @can('manage-categories')
                                        <td class="text-end pe-3 text-nowrap">
                                            <div class="d-inline-flex gap-1">

                                                {{-- Sửa danh mục --}}
                                                <a href="{{ route('categories.edit', $c) }}"
                                                   class="icon-btn"
                                                   title="Chỉnh sửa"
                                                   aria-label="Chỉnh sửa danh mục {{ $c->category_name }}">
                                                    <i class="bi bi-pencil"></i>
                                                </a>

                                                {{-- Bật/tắt trạng thái --}}
                                                <form action="{{ route('categories.toggle-status', $c) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('Bạn có muốn {{ $c->status === 'active' ? 'ngừng sử dụng' : 'sử dụng lại' }} danh mục này?')">
                                                    @csrf
                                                    @method('PATCH')

                                                    <button type="submit"
                                                            class="icon-btn"
                                                            title="{{ $c->status === 'active' ? 'Ngừng sử dụng' : 'Sử dụng lại' }}"
                                                            aria-label="{{ $c->status === 'active' ? 'Ngừng sử dụng' : 'Sử dụng lại' }} danh mục">
                                                        <i class="bi {{ $c->status === 'active' ? 'bi-pause-fill' : 'bi-play-fill' }}"></i>
                                                    </button>
                                                </form>

                                                {{-- Xóa danh mục --}}
                                                <form action="{{ route('categories.destroy', $c) }}"
                                                      method="POST"
                                                      onsubmit="return confirm({{ \Illuminate\Support\Js::from('Bạn có chắc muốn xóa danh mục ' . $c->category_name . '?') }})">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit"
                                                            class="icon-btn danger"
                                                            @disabled(($c->products_count ?? 0) > 0)
                                                            title="{{ ($c->products_count ?? 0) > 0 ? 'Danh mục đang có sản phẩm, không thể xóa' : 'Xóa danh mục' }}"
                                                            aria-label="Xóa danh mục {{ $c->category_name }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()?->can('manage-categories') ? 5 : 4 }}"
                                        class="text-center py-5">
                                        <div class="mb-2">
                                            <i class="bi bi-folder2-open fs-2 text-muted"></i>
                                        </div>
                                        <div class="fw-semibold">Chưa có danh mục sản phẩm</div>
                                        <div class="small text-muted mt-1">
                                            @can('manage-categories')
                                                Hãy sử dụng biểu mẫu bên cạnh để thêm danh mục đầu tiên.
                                            @else
                                                Hiện chưa có dữ liệu danh mục để hiển thị.
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @cannot('manage-categories')
                <p class="small text-muted mt-3 mb-0">
                    <i class="bi bi-eye"></i>
                    Bạn đang ở chế độ <strong>xem</strong>. Chỉ quản trị viên mới được thêm, sửa hoặc xóa danh mục.
                </p>
            @endcannot
        </div>

        {{-- Biểu mẫu thêm/sửa danh mục: chỉ Admin --}}
        @can('manage-categories')
            <div class="col-lg-4">
                <form
                    action="{{ $editing ? route('categories.update', $category) : route('categories.store') }}"
                    method="POST"
                    class="card p-3 p-md-4 {{ $editing ? 'border-brand' : '' }}"
                >
                    @csrf

                    @if ($editing)
                        @method('PUT')
                    @endif

                    <div class="panel-title mb-3">
                        <i class="bi {{ $editing ? 'bi-pencil-square' : 'bi-folder-plus' }}"></i>
                        {{ $editing ? 'Sửa danh mục' : 'Thêm danh mục mới' }}
                    </div>

                    <label class="form-label" for="category_name">
                        Tên danh mục <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        id="category_name"
                        name="category_name"
                        maxlength="100"
                        required
                        autofocus
                        value="{{ old('category_name', $editing ? $category->category_name : '') }}"
                        class="form-control @error('category_name') is-invalid @enderror"
                        placeholder="Ví dụ: Thức ăn cho chó"
                    >

                    @error('category_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    <label class="form-label mt-3" for="description">
                        Mô tả
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        maxlength="500"
                        class="form-control @error('description') is-invalid @enderror"
                        placeholder="Mô tả nhóm sản phẩm thuộc danh mục này"
                    >{{ old('description', $editing ? $category->description : '') }}</textarea>

                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="submit" class="btn btn-brand">
                            <i class="bi bi-check2"></i>
                            {{ $editing ? 'Lưu thay đổi' : 'Thêm danh mục' }}
                        </button>

                        @if ($editing)
                            <a href="{{ route('categories.index') }}"
                               class="btn btn-outline-secondary">
                                Hủy
                            </a>
                        @endif
                    </div>

                    <p class="small text-muted mt-3 mb-0">
                        Danh mục ngừng sử dụng sẽ không xuất hiện khi thêm sản phẩm mới.
                        Sản phẩm cũ vẫn được giữ nguyên. Chỉ có thể xóa danh mục chưa có sản phẩm.
                    </p>
                </form>
            </div>
        @endcan

    </div>
@endsection
```