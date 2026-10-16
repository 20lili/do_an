@extends('layouts.app')

@section('title', 'Tồn kho')
@section('page-title', 'Quản lý kho')
@section('page-subtitle', 'Theo dõi tồn kho hiện tại, nhập kho và lịch sử biến động hàng hóa')

@section('page-actions')
    @can('receive-stock')
        <a href="{{ route('inventory.receive') }}" class="btn btn-brand"><i class="bi bi-box-arrow-in-down"></i> Lập phiếu nhập</a>
    @endcan
@endsection

@php
    use App\Models\Product;
    $url = fn (array $p) => request()->fullUrlWithQuery($p + ['page' => null]);
    $stock = request('stock');
@endphp

@section('content')
    @include('inventory._nav')

    <div class="stat-strip">
        <div class="stat-pill"><b>{{ number_format($summary['units'], 0, ',', '.') }}</b><span>đơn vị hàng đang tồn</span></div>
        <div class="stat-pill"><b>{{ Product::vnd($summary['value']) }}</b><span>giá trị tồn kho (theo giá nhập)</span></div>
        <a href="{{ $url(['stock' => $stock === 'low' ? null : 'low']) }}" class="stat-pill text-decoration-none text-reset"><b style="color:var(--pink)">{{ $summary['low'] }}</b><span>sắp hết (&lt; {{ Product::LOW_STOCK_THRESHOLD }})</span></a>
        <a href="{{ $url(['stock' => $stock === 'out' ? null : 'out']) }}" class="stat-pill text-decoration-none text-reset"><b style="color:var(--red)">{{ $summary['out'] }}</b><span>hết hàng</span></a>
    </div>

    <div class="toolbar">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ $url(['stock' => null]) }}" class="chip {{ blank($stock) ? 'active' : '' }}">Tất cả</a>
            <a href="{{ $url(['stock' => 'low']) }}" class="chip chip-pink {{ $stock === 'low' ? 'active' : '' }}"><i class="bi bi-exclamation-circle"></i> Sắp hết</a>
            <a href="{{ $url(['stock' => 'out']) }}" class="chip chip-red {{ $stock === 'out' ? 'active' : '' }}"><i class="bi bi-x-circle"></i> Hết hàng</a>
            <form method="GET" class="d-flex gap-2 ms-1">
                @foreach (request()->except(['category_id', 'page']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Danh mục">
                    <option value="">Tất cả danh mục</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->category_name }}</option>
                    @endforeach
                </select>
            </form>
            @if (filled(request('q')))
                <span class="search-note ms-1">Kết quả cho “{{ request('q') }}”
                    <a href="{{ $url(['q' => null]) }}" class="ms-1" title="Bỏ tìm kiếm"><i class="bi bi-x-lg"></i></a></span>
            @endif
        </div>
        <div class="view-toggle" role="group" aria-label="Sắp xếp">
            <a href="{{ $url(['sort' => null]) }}" class="{{ $sort === 'stock' ? 'active' : '' }}">Tồn ít nhất</a>
            <a href="{{ $url(['sort' => 'value']) }}" class="{{ $sort === 'value' ? 'active' : '' }}">Giá trị cao</a>
            <a href="{{ $url(['sort' => 'code']) }}" class="{{ $sort === 'code' ? 'active' : '' }}">Mã SP</a>
        </div>
    </div>

    @if ($products->isEmpty())
        <div class="empty-state"><div class="big">📦</div><p class="fw-bold mb-0">Không có sản phẩm phù hợp bộ lọc.</p></div>
    @else
        <div class="table-wrap">
            <div class="table-responsive">
                <table class="table table-hover table-products">
                    <thead>
                    <tr>
                        <th class="ps-3">Sản phẩm</th>
                        <th>Tồn kho</th>
                        <th class="text-end">Giá trị tồn</th>
                        <th class="text-center">Nhập 30 ngày</th>
                        <th class="text-center">Xuất 30 ngày</th>
                        <th>Biến động gần nhất</th>
                        <th class="text-end pe-3">Thao tác</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($products as $p)
                        @php
                            [$label, $color] = $p->stockBadge();
                            $cls = $p->stock_quantity <= 0 ? 'out' : ($p->stock_quantity < Product::LOW_STOCK_THRESHOLD ? 'low' : '');
                            $pct = min(100, $p->stock_quantity / max(1, Product::LOW_STOCK_THRESHOLD * 3) * 100);
                            $img = $p->imageUrl();
                        @endphp
                        <tr class="{{ $p->status === 'inactive' ? 'row-inactive' : '' }}">
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="thumb">@if ($img)<img src="{{ $img }}" alt="">@else{{ $p->category->emoji() }}@endif</div>
                                    <div>
                                        <a href="{{ route('inventory.stock-card', $p) }}" class="fw-bold text-reset text-decoration-none">{{ $p->product_name }}</a>
                                        <div class="code">{{ $p->product_code }} · {{ $p->category->category_name }}@if ($p->status === 'inactive') · ngừng KD @endif</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-baseline gap-2">
                                    <span class="stock-big">{{ $p->stock_quantity }}</span>
                                    <span class="small text-muted">{{ mb_strtolower($p->unit) }}</span>
                                    <span class="badge rounded-pill text-bg-{{ $color }}">{{ $label }}</span>
                                </div>
                                <div class="mini-bar"><span class="{{ $cls }}" style="width: {{ $pct }}%"></span></div>
                            </td>
                            <td class="text-end money">{{ Product::vnd($p->stock_quantity * (float) $p->import_price) }}</td>
                            <td class="text-center tx-in">{{ $p->in_30 ? '+' . $p->in_30 : '—' }}</td>
                            <td class="text-center tx-out">{{ $p->out_30 ? '−' . $p->out_30 : '—' }}</td>
                            <td class="small text-muted">{{ $p->last_moved_at ? \Illuminate\Support\Carbon::parse($p->last_moved_at)->format('d/m/Y H:i') : '—' }}</td>
                            <td class="text-end pe-3 text-nowrap">
                                <a href="{{ route('inventory.stock-card', $p) }}" class="btn btn-sm btn-outline-secondary">Thẻ kho</a>
                                @if ($p->status === 'active' && auth()->user()->can('receive-stock'))
                                    <a href="{{ route('inventory.receive', ['product_id' => $p->id]) }}" class="btn btn-sm btn-brand">Nhập</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="pager-row">
            <div class="text-muted small">Hiển thị {{ $products->firstItem() }}–{{ $products->lastItem() }} / {{ $products->total() }} sản phẩm</div>
            {{ $products->links() }}
        </div>
    @endif
@endsection
