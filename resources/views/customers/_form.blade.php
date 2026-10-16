{{-- Biểu mẫu dùng chung cho Thêm và Sửa khách hàng --}}
@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle"></i> Vui lòng kiểm tra lại thông tin:</div>
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
            <div class="panel-title">Thông tin liên hệ</div>
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label" for="customer_name">Họ tên <span class="text-danger">*</span></label>
                    <input type="text" id="customer_name" name="customer_name" maxlength="100"
                           value="{{ old('customer_name', $customer->customer_name) }}"
                           class="form-control @error('customer_name') is-invalid @enderror" placeholder="VD: Nguyễn Văn An">
                    @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="phone">Số điện thoại <span class="text-danger">*</span></label>
                    <input type="tel" id="phone" name="phone" maxlength="20" inputmode="tel"
                           value="{{ old('phone', $customer->phone) }}"
                           class="form-control @error('phone') is-invalid @enderror" placeholder="VD: 0909 123 456">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-7">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" maxlength="100"
                           value="{{ old('email', $customer->email) }}"
                           class="form-control @error('email') is-invalid @enderror" placeholder="Không bắt buộc">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="address">Địa chỉ</label>
                    <input type="text" id="address" name="address" maxlength="255"
                           value="{{ old('address', $customer->address) }}"
                           class="form-control @error('address') is-invalid @enderror" placeholder="Số nhà, đường, phường, quận, tỉnh/thành">
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card p-3 p-md-4">
            <div class="panel-title">Ghi chú</div>
            <textarea name="note" rows="6" maxlength="1000"
                      class="form-control @error('note') is-invalid @enderror"
                      placeholder="Thú cưng đang nuôi, sở thích, lưu ý khi giao hàng…">{{ old('note', $customer->note) }}</textarea>
            @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Mỗi số điện thoại chỉ dùng cho một khách hàng.</div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 justify-content-end mt-4">
    <a href="{{ $customer->exists ? route('customers.show', $customer) : route('customers.index') }}" class="btn btn-outline-secondary">Hủy</a>
    <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> {{ $submitLabel }}</button>
</div>
