@extends('layouts.app')

@section('title', 'Nhập kho')
@section('breadcrumb')
    <a href="{{ route('inventory.index') }}">Kho hàng</a> / Phiếu nhập kho
@endsection
@section('page-title', 'Phiếu nhập kho')
@section('page-subtitle', 'Ghi nhận hàng nhập về cửa hàng; tồn kho được cộng ngay khi lưu phiếu.')

@php
    $initialItems = old('items', [['product_id' => $preselect, 'quantity' => 10]]);
@endphp

@section('content')
    @include('inventory._nav')

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle"></i> Chưa lưu được phiếu nhập:</div>
            <ul class="mb-0 ps-3">
                @foreach (collect($errors->all())->unique() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('inventory.receive.store') }}" method="POST" id="receive-form" novalidate>
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="panel-title mb-0">Sản phẩm nhập kho</div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="add-line"><i class="bi bi-plus-lg"></i> Thêm dòng</button>
                    </div>
                    <div id="lines" class="mt-2"></div>
                    <p class="form-text mb-0">Chỉ nhập kho cho sản phẩm đang kinh doanh. Giá trị phiếu tính theo giá nhập tham khảo của sản phẩm.</p>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="total-box mb-3">
                    <div class="lbl">Tổng số lượng nhập</div>
                    <div class="val" id="total-units">0</div>
                    <div class="small mt-1" style="opacity:.75">Giá trị ước tính: <span id="total-value">0đ</span></div>
                </div>
                <div class="card p-3 p-md-4 mb-3">
                    <label class="form-label" for="note">Nhà cung cấp / ghi chú phiếu</label>
                    <input type="text" name="note" id="note" maxlength="255" class="form-control"
                           value="{{ old('note') }}" placeholder="VD: NCC Pet Mart – HĐ 0123">
                    <div class="form-text">Ghi chú được lưu cùng từng dòng lịch sử nhập kho.</div>
                </div>
                <button type="submit" class="btn btn-brand w-100 py-2"><i class="bi bi-box-arrow-in-down"></i> Lưu phiếu nhập</button>
                <a href="{{ route('inventory.index') }}" class="btn btn-link w-100 text-muted">Hủy bỏ</a>
            </div>
        </div>
    </form>

    <template id="line-template">
        <div class="line-row">
            <div>
                <select class="form-select product-select" required>
                    <option value="">— Chọn sản phẩm —</option>
                    @foreach ($products->groupBy('category') as $category => $items)
                        <optgroup label="{{ $category }}">
                            @foreach ($items as $p)
                                <option value="{{ $p['id'] }}">{{ $p['code'] }} – {{ $p['name'] }} (tồn {{ $p['stock'] }})</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <div class="line-meta"></div>
            </div>
            <input type="number" class="form-control qty-input" min="1" value="10" aria-label="Số lượng nhập">
            <div class="line-sub money">0đ</div>
            <button type="button" class="icon-btn danger remove-line" title="Xóa dòng"><i class="bi bi-x-lg"></i></button>
        </div>
    </template>
@endsection

@push('scripts')
<script>
(function () {
    const PRODUCTS = @json($products->keyBy('id'));
    const INITIAL  = @json(array_values($initialItems));
    const ERRORS   = @json($errors->getMessages());
    const vnd = n => Math.round(n).toLocaleString('vi-VN') + 'đ';
    const lines = document.getElementById('lines');
    const tpl = document.getElementById('line-template');

    function renumber() {
        [...lines.children].forEach((row, i) => {
            row.querySelector('.product-select').name = `items[${i}][product_id]`;
            row.querySelector('.qty-input').name = `items[${i}][quantity]`;
            row.querySelector('.remove-line').disabled = lines.children.length === 1;
        });
    }

    function refresh() {
        let units = 0, value = 0;
        const chosen = [...lines.querySelectorAll('.product-select')].map(s => s.value).filter(Boolean);
        [...lines.children].forEach(row => {
            const sel = row.querySelector('.product-select');
            const qty = parseInt(row.querySelector('.qty-input').value) || 0;
            const meta = row.querySelector('.line-meta');
            const p = PRODUCTS[sel.value];
            [...sel.options].forEach(o => { if (o.value) o.disabled = o.value !== sel.value && chosen.includes(o.value); });
            if (!p) { meta.textContent = ''; row.querySelector('.line-sub').textContent = '0đ'; return; }
            units += qty; value += qty * p.cost;
            row.querySelector('.line-sub').textContent = vnd(qty * p.cost);
            meta.innerHTML = `Giá nhập ${vnd(p.cost)} / ${p.unit.toLowerCase()} · Tồn: <b>${p.stock}</b> → sau nhập: <b class="tx-in">${p.stock + qty}</b>`;
        });
        document.getElementById('total-units').textContent = units.toLocaleString('vi-VN');
        document.getElementById('total-value').textContent = vnd(value);
    }

    function addLine(item = {}) {
        const row = tpl.content.firstElementChild.cloneNode(true);
        row.querySelector('.product-select').value = item.product_id ?? '';
        row.querySelector('.qty-input').value = item.quantity ?? 10;
        lines.appendChild(row); renumber(); refresh();
    }

    lines.addEventListener('input', refresh);
    lines.addEventListener('change', refresh);
    lines.addEventListener('click', e => {
        const btn = e.target.closest('.remove-line');
        if (btn && lines.children.length > 1) { btn.closest('.line-row').remove(); renumber(); refresh(); }
    });
    document.getElementById('add-line').addEventListener('click', () => addLine());
    INITIAL.forEach(addLine);

    Object.keys(ERRORS).forEach(key => {
        const m = key.match(/^items\.(\d+)\.(product_id|quantity)$/);
        if (m && lines.children[m[1]]) lines.children[m[1]].querySelector(m[2] === 'quantity' ? '.qty-input' : '.product-select').classList.add('is-invalid');
    });
})();
</script>
@endpush
