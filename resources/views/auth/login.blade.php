<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập – PetShop Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}?v=7" rel="stylesheet">
</head>
<body class="login-page">
<main class="login-shell">
    {{-- ===== Bên trái: giới thiệu ===== --}}
    <section class="login-brand" aria-hidden="true">
        <div class="logo">
            <span class="logo-mark">🐾</span>
            <span class="logo-text"><strong>PetShop</strong><small>Quản lý đơn hàng &amp; kho</small></span>
        </div>
        <h1>Quản lý cửa hàng thú cưng<br>gọn gàng mỗi ngày</h1>
        <ul class="login-points">
            <li><i class="bi bi-receipt"></i> Lập đơn, xác nhận và theo dõi trạng thái đơn hàng</li>
            <li><i class="bi bi-buildings"></i> Tồn kho tự cập nhật khi xuất – nhập hàng</li>
            <li><i class="bi bi-bar-chart-line"></i> Doanh thu, hàng bán chạy, cảnh báo sắp hết</li>
        </ul>
        <div class="login-pets">🐶 🐱 🐹 🐰</div>
    </section>

    {{-- ===== Bên phải: biểu mẫu đăng nhập ===== --}}
    <section class="login-card">
        <h2>Đăng nhập</h2>
        <p class="text-muted mb-4">Dành cho quản trị viên và nhân viên cửa hàng.</p>

        @if (session('status'))
            <div class="alert alert-success py-2"><i class="bi bi-check-circle-fill me-1"></i> {{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger py-2" role="alert"><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first() }}</div>
        @endif

        <form action="{{ route('login.attempt') }}" method="POST" novalidate>
            @csrf
            <label class="form-label" for="username">Tên đăng nhập</label>
            <div class="input-icon mb-3">
                <i class="bi bi-person"></i>
                <input type="text" id="username" name="username" value="{{ old('username') }}" maxlength="50"
                       class="form-control form-control-lg @error('username') is-invalid @enderror"
                       autocomplete="username" autofocus placeholder="VD: nhanvien">
            </div>

            <label class="form-label" for="password">Mật khẩu</label>
            <div class="input-icon mb-3">
                <i class="bi bi-lock"></i>
                <input type="password" id="password" name="password"
                       class="form-control form-control-lg @error('password') is-invalid @enderror"
                       autocomplete="current-password" placeholder="••••••••">
                <button type="button" class="pw-toggle" aria-label="Hiện mật khẩu"
                        onclick="const i=document.getElementById('password'); const s=i.type==='password'; i.type=s?'text':'password'; this.firstElementChild.className=s?'bi bi-eye-slash':'bi bi-eye';">
                    <i class="bi bi-eye"></i>
                </button>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" @checked(old('remember'))>
                <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
            </div>

            <button type="submit" class="btn btn-brand btn-lg w-100"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập</button>
        </form>

        @if (app()->environment('local'))
            {{-- Chỉ hiện khi APP_ENV=local, giúp chạy thử nhanh; khi triển khai thật sẽ tự ẩn --}}
            <details class="demo-accounts">
                <summary>Tài khoản dùng thử</summary>
                <table class="table table-sm mb-0 mt-2">
                    <tr><td><code>admin</code></td><td><code>admin123</code></td><td>Quản trị viên</td></tr>
                    <tr><td><code>nhanvien</code></td><td><code>nhanvien123</code></td><td>Nhân viên (được xác nhận đơn, nhập kho)</td></tr>
                    <tr><td><code>nhanvien2</code></td><td><code>nhanvien123</code></td><td>Nhân viên (quyền cơ bản)</td></tr>
                </table>
            </details>
        @endif
    </section>
</main>
</body>
</html>
