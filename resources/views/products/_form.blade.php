{{-- Biểu mẫu dùng chung cho Thêm và Sửa sản phẩm --}}
@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-circle"></i> Vui lòng kiểm tra lại thông tin:</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card p-3 p-md-4">
            <div class="panel-title">Thông tin sản phẩm</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Mã sản phẩm <span class="text-danger">*</span></label>
                    <input type="text" name="product_code" maxlength="50"
                           value="{{ old('product_code', $product->product_code) }}"
                           class="form-control code text-uppercase @error('product_code') is-invalid @enderror"
                           placeholder="VD: TAM009">
                    @error('product_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
                    <input type="text" name="product_name" maxlength="150"
                           value="{{ old('product_name', $product->product_name) }}"
                           class="form-control @error('product_name') is-invalid @enderror"
                           placeholder="VD: Hạt Whiskas vị cá ngừ 1.2kg">
                    @error('product_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Danh mục <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                        <option value="">— Chọn danh mục —</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->category_name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Đơn vị tính <span class="text-danger">*</span></label>
                    <input type="text" name="unit" maxlength="30" list="unit-list"
                           value="{{ old('unit', $product->unit) }}"
                           class="form-control @error('unit') is-invalid @enderror" placeholder="Túi, Gói, Cái…">
                    <datalist id="unit-list">
                        @foreach (['Túi', 'Gói', 'Hộp', 'Lon', 'Cái', 'Bộ', 'Chai', 'Tuýp'] as $u)
                            <option value="{{ $u }}">
                        @endforeach
                    </datalist>
                    @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Giá nhập (đ) <span class="text-danger">*</span></label>
                    <input type="number" name="import_price" min="0" step="1000" id="import_price"
                           value="{{ old('import_price', $product->import_price !== null ? (int) $product->import_price : '') }}"
                           class="form-control money @error('import_price') is-invalid @enderror">
                    @error('import_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Giá bán (đ) <span class="text-danger">*</span></label>
                    <input type="number" name="selling_price" min="0" step="1000" id="selling_price"
                           value="{{ old('selling_price', $product->selling_price !== null ? (int) $product->selling_price : '') }}"
                           class="form-control money @error('selling_price') is-invalid @enderror">
                    @error('selling_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text" id="margin-hint"></div>
                </div>

                <div class="col-12">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" rows="3" maxlength="2000"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card p-3 p-md-4 mb-4">
            <div class="panel-title">Hình ảnh</div>
            <div class="image-preview mb-2" id="image-preview">
                @if ($product->exists && $product->imageUrl())
                    <img src="{{ $product->imageUrl() }}" alt="">
                @else
                    <div class="text-center"><i class="bi bi-image fs-1 d-block"></i><small>Chưa có ảnh</small></div>
                @endif
            </div>
            <input type="file" name="image" accept="image/*" id="image-input"
                   class="form-control @error('image') is-invalid @enderror">
            @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">JPG, PNG, WEBP – tối đa 2MB. Ảnh vuông, nền trắng sẽ đẹp nhất.</div>
        </div>

        <div class="card p-3 p-md-4">
            <div class="panel-title">Trạng thái &amp; tồn kho</div>
            <label class="form-label">Trạng thái kinh doanh</label>
            <select name="status" class="form-select mb-3 @error('status') is-invalid @enderror">
                @foreach (\App\Models\Product::STATUSES as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $product->status) === $key)>{{ $label }}</option>
                @endforeach
            </select>

            <label class="form-label">Số lượng tồn kho</label>
            <input type="text" class="form-control bg-light" value="{{ (int) $product->stock_quantity }}" readonly>
            <div class="form-text">
                <i class="bi bi-info-circle"></i>
                Tồn kho không sửa trực tiếp tại đây; số lượng được cập nhật qua nghiệp vụ nhập kho và xuất kho theo đơn hàng.
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 justify-content-end mt-4">
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Hủy</a>
    <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> {{ $submitLabel }}</button>
</div>

@push('scripts')
<script>
    // Xem trước ảnh khi chọn tệp
    document.getElementById('image-input').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;
        const box = document.getElementById('image-preview');
        box.innerHTML = '';
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        box.appendChild(img);
    });

    // Hiển thị lợi nhuận gộp / cảnh báo giá bán thấp hơn giá nhập
    const imp = document.getElementById('import_price');
    const sell = document.getElementById('selling_price');
    const hint = document.getElementById('margin-hint');
    function updateMargin() {
        const a = parseFloat(imp.value), b = parseFloat(sell.value);
        if (isNaN(a) || isNaN(b)) { hint.textContent = ''; return; }
        const m = b - a;
        hint.className = 'form-text ' + (m < 0 ? 'text-danger' : 'text-success');
        hint.textContent = m < 0
            ? 'Giá bán đang thấp hơn giá nhập!'
            : 'Lãi gộp: ' + m.toLocaleString('vi-VN') + 'đ / đơn vị' + (a > 0 ? ' (' + (m / a * 100).toFixed(1) + '%)' : '');
    }
    imp.addEventListener('input', updateMargin);
    sell.addEventListener('input', updateMargin);
    updateMargin();
</script>
@endpush
