@extends('layouts.app')

@section('title', 'Lập đơn hàng')
@section('breadcrumb')
    <a href="{{ route('orders.index') }}">Đơn hàng</a> / Lập đơn mới
@endsection
@section('page-title', 'Lập đơn hàng')
@section('page-subtitle', 'Đơn mới ở trạng thái "Chờ xác nhận"; tồn kho chỉ bị trừ khi xác nhận đơn.')

@php
    // Dòng sản phẩm ban đầu: lấy lại dữ liệu cũ nếu lưu bị lỗi
    $initialItems = old('items', [['product_id' => '', 'quantity' => 1]]);
@endphp

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle"></i> Chưa lập được đơn hàng:</div>
            <ul class="mb-0 ps-3">
                @foreach (collect($errors->all())->unique() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('orders.store') }}" method="POST" id="order-form" novalidate>
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">
                {{-- ===== Khách hàng ===== --}}
                <div class="card p-3 p-md-4 mb-4">
                    <div class="panel-title">1. Khách hàng</div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-9">
                            <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                                <option value="">— Chọn khách hàng —</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}" @selected($selectedCustomer == $c->id)>
                                        {{ $c->customer_name }} – {{ $c->phoneFormatted() }} ({{ $c->code() }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('customers.create') }}" class="btn btn-outline-secondary w-100" target="_blank">
                                <i class="bi bi-person-plus"></i> Khách mới
                            </a>
                        </div>
                    </div>
                </div>

                {{-- ===== Sản phẩm ===== --}}
                <div class="card p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="panel-title mb-0">2. Sản phẩm</div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="add-line"><i class="bi bi-plus-lg"></i> Thêm dòng</button>
                    </div>
                    <div id="lines" class="mt-2"></div>
                    <p class="form-text mb-0">Chỉ hiển thị sản phẩm đang kinh doanh. Sản phẩm hết hàng không chọn được.</p>
                </div>
            </div>

            {{-- ===== Tổng tiền + ghi chú ===== --}}
            <div class="col-lg-4">
                <div class="total-box mb-3">
                    <div class="lbl">Tổng tiền đơn hàng</div>
                    <div class="val money" id="grand-total">0đ</div>
                    <div class="small mt-1" style="opacity:.75"><span id="item-count">0</span> mặt hàng</div>
                </div>
                <div class="card p-3 p-md-4 mb-3">
                    <label class="form-label" for="note">Ghi chú đơn hàng</label>
                    <textarea name="note" id="note" rows="4" maxlength="1000" class="form-control"
                              placeholder="Thời gian giao, yêu cầu đóng gói…">{{ old('note') }}</textarea>
                </div>
                <button type="submit" class="btn btn-brand w-100 py-2"><i class="bi bi-check2-circle"></i> Lưu đơn hàng</button>
                <a href="{{ route('orders.index') }}" class="btn btn-link w-100 text-muted">Hủy bỏ</a>
            </div>
        </div>
    </form>

    {{-- Mẫu một dòng sản phẩm --}}
    <template id="line-template">
        <div class="line-row">
            <div>
                <select class="form-select product-select" required>
                    <option value="">— Chọn sản phẩm —</option>
                    @foreach ($products->groupBy('category') as $category => $items)
                        <optgroup label="{{ $category }}">
                            @foreach ($items as $p)
                                <option value="{{ $p['id'] }}" @disabled($p['stock'] <= 0)>
                                    {{ $p['code'] }} – {{ $p['name'] }}{{ $p['stock'] <= 0 ? ' (hết hàng)' : '' }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <div class="line-meta"></div>
            </div>
            <input type="number" class="form-control qty-input" min="1" value="1" aria-label="Số lượng">
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
    const tpl   = document.getElementById('line-template');

    function renumber() {
        // Đặt tên input theo thứ tự để gửi lên server: items[0][product_id], items[0][quantity]…
        [...lines.children].forEach((row, i) => {
            row.querySelector('.product-select').name = `items[${i}][product_id]`;
            row.querySelector('.qty-input').name      = `items[${i}][quantity]`;
            row.querySelector('.remove-line').disabled = lines.children.length === 1;
        });
    }

    function refresh() {
        let total = 0, count = 0;
        const chosen = [...lines.querySelectorAll('.product-select')].map(s => s.value).filter(Boolean);

        [...lines.children].forEach(row => {
            const sel = row.querySelector('.product-select');
            const qty = parseInt(row.querySelector('.qty-input').value) || 0;
            const meta = row.querySelector('.line-meta');
            const p = PRODUCTS[sel.value];

            // Không cho chọn trùng sản phẩm ở hai dòng
            [...sel.options].forEach(o => {
                if (!o.value) return;
                const pr = PRODUCTS[o.value];
                o.disabled = pr.stock <= 0 || (o.value !== sel.value && chosen.includes(o.value));
            });

            if (!p) { meta.textContent = ''; row.querySelector('.line-sub').textContent = '0đ'; return; }

            const sub = qty * p.price;
            total += sub; count++;
            row.querySelector('.line-sub').textContent = vnd(sub);
            meta.innerHTML = `Đơn giá ${vnd(p.price)} / ${p.unit.toLowerCase()} · Tồn kho: <b>${p.stock}</b>` +
                (qty > p.stock ? ` <span class="warn">– không đủ hàng!</span>` : '');
        });

        document.getElementById('grand-total').textContent = vnd(total);
        document.getElementById('item-count').textContent = count;
    }

    function addLine(item = {}) {
        const row = tpl.content.firstElementChild.cloneNode(true);
        row.querySelector('.product-select').value = item.product_id ?? '';
        row.querySelector('.qty-input').value = item.quantity ?? 1;
        lines.appendChild(row);
        renumber(); refresh();
    }

    lines.addEventListener('input', refresh);
    lines.addEventListener('change', refresh);
    lines.addEventListener('click', e => {
        const btn = e.target.closest('.remove-line');
        if (btn && lines.children.length > 1) { btn.closest('.line-row').remove(); renumber(); refresh(); }
    });
    document.getElementById('add-line').addEventListener('click', () => addLine());

    INITIAL.forEach(addLine);

    // Tô đỏ dòng bị lỗi khi server trả về (items.0.quantity…)
    Object.keys(ERRORS).forEach(key => {
        const m = key.match(/^items\.(\d+)\.(product_id|quantity)$/);
        if (!m || !lines.children[m[1]]) return;
        lines.children[m[1]].querySelector(m[2] === 'quantity' ? '.qty-input' : '.product-select').classList.add('is-invalid');
    });
})();
</script>
@endpush
