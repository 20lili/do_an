@extends('layouts.app')

@section('title', 'Đổi mật khẩu')
@section('page-title', 'Đổi mật khẩu')
@section('page-subtitle', 'Tài khoản: ' . auth()->user()->username . ' · ' . auth()->user()->roleLabel())

@section('content')
    <div class="row">
        <div class="col-md-8 col-lg-6">
            <form action="{{ route('password.update') }}" method="POST" class="card p-3 p-md-4" novalidate>
                @csrf @method('PUT')
                <label class="form-label" for="current_password">Mật khẩu hiện tại</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                       class="form-control mb-1 @error('current_password') is-invalid @enderror">
                @error('current_password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                <label class="form-label mt-3" for="password">Mật khẩu mới</label>
                <input type="password" id="password" name="password" autocomplete="new-password"
                       class="form-control mb-1 @error('password') is-invalid @enderror">
                @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                <div class="form-text">Ít nhất 8 ký tự.</div>

                <label class="form-label mt-3" for="password_confirmation">Nhập lại mật khẩu mới</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="form-control mb-4">

                <div class="d-flex gap-2">
                    <button class="btn btn-brand"><i class="bi bi-shield-lock"></i> Lưu mật khẩu</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
@endsection
