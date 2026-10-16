{{-- Trang báo không có quyền (Laravel tự dùng khi middleware can:... từ chối) --}}
@extends('layouts.app')

@section('title', 'Không có quyền truy cập')

@section('content')
    <div class="denied">
        <div class="denied-icon">🔒</div>
        <h1>Bạn không có quyền thực hiện chức năng này</h1>
        <p class="text-muted">
            Tài khoản <b>{{ auth()->user()?->username }}</b> ({{ auth()->user()?->roleLabel() }}) chưa được cấp quyền cho thao tác vừa chọn.
            Nếu cần sử dụng, vui lòng liên hệ quản trị viên để được cấp quyền.
        </p>
        <div class="d-flex gap-2 justify-content-center">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
            <a href="{{ route('dashboard') }}" class="btn btn-brand"><i class="bi bi-grid"></i> Về trang Tổng quan</a>
        </div>
        <div class="small text-muted mt-4">Mã lỗi 403</div>
    </div>
@endsection
