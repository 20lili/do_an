{{-- Biểu mẫu dùng chung cho Thêm và Sửa tài khoản --}}
@php use App\Models\User; $isSelf = $user->exists && $user->is(auth()->user()); @endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle"></i> Vui lòng kiểm tra lại thông tin:</div>
        <ul class="mb-0 ps-3">
            @foreach (collect($errors->all())->unique() as $error) <li>{{ $error }}</li> @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card p-3 p-md-4">
            <div class="panel-title">Thông tin tài khoản</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">Họ tên <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" maxlength="100" value="{{ old('name', $user->name) }}"
                           class="form-control @error('name') is-invalid @enderror" placeholder="VD: Lê Thị Mai">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="username">Tên đăng nhập <span class="text-danger">*</span></label>
                    <input type="text" id="username" name="username" maxlength="50" value="{{ old('username', $user->username) }}"
                           class="form-control @error('username') is-invalid @enderror" placeholder="VD: nhanvien3" autocomplete="off">
                    <div class="form-text">Chữ thường không dấu, số, dấu chấm hoặc gạch dưới.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" maxlength="255" value="{{ old('email', $user->email) }}"
                           class="form-control @error('email') is-invalid @enderror" placeholder="Không bắt buộc">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password">
                        Mật khẩu @if (! $user->exists) <span class="text-danger">*</span> @endif
                    </label>
                    <input type="password" id="password" name="password" autocomplete="new-password"
                           class="form-control @error('password') is-invalid @enderror">
                    <div class="form-text">{{ $user->exists ? 'Để trống nếu không đổi mật khẩu.' : 'Ít nhất 8 ký tự.' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password_confirmation">Nhập lại mật khẩu</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card p-3 p-md-4">
            <div class="panel-title">Vai trò &amp; quyền</div>
            @php $role = old('role', $user->role); $perms = old('permissions', $user->permissions ?? []); @endphp
            <div class="role-options mb-3">
                @foreach (User::ROLES as $key => $label)
                    <label class="role-option">
                        <input type="radio" name="role" value="{{ $key }}" @checked($role === $key) @disabled($isSelf && $key !== $role)>
                        <span>
                            <b>{{ $label }}</b>
                            <small>{{ $key === User::ADMIN ? 'Toàn quyền, gồm quản lý tài khoản, danh mục, báo cáo doanh thu' : 'Bán hàng, khách hàng, xem sản phẩm, tồn kho và thông tin cơ bản' }}</small>
                        </span>
                    </label>
                @endforeach
            </div>
            @if ($isSelf)
                <p class="small text-muted">Bạn không thể tự đổi vai trò của tài khoản đang đăng nhập.</p>
            @endif

            <div id="perm-box" class="{{ $role === User::ADMIN ? 'opacity-50' : '' }}">
                <div class="fw-bold mb-1">Quyền bổ sung cho nhân viên</div>
                <p class="small text-muted mb-2">Ứng với các chức năng "theo quyền được cấp" trong ma trận phân quyền.</p>
                @foreach (User::PERMISSIONS as $key => $label)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $key }}" id="perm-{{ $loop->index }}"
                               @checked(in_array($key, $perms, true)) @disabled($role === User::ADMIN)>
                        <label class="form-check-label" for="perm-{{ $loop->index }}">{{ $label }}</label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-brand"><i class="bi bi-check2"></i> {{ $submitLabel }}</button>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Hủy</a>
</div>

@push('scripts')
<script>
    // Chọn "Quản trị viên" thì khóa ô quyền bổ sung (Admin đã có toàn quyền)
    document.querySelectorAll('input[name=role]').forEach(r => r.addEventListener('change', () => {
        const admin = document.querySelector('input[name=role]:checked').value === 'admin';
        document.getElementById('perm-box').classList.toggle('opacity-50', admin);
        document.querySelectorAll('input[name="permissions[]"]').forEach(c => c.disabled = admin);
    }));
</script>
@endpush
