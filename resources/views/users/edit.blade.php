@extends('layouts.app')

@section('title', 'Sửa tài khoản')
@section('breadcrumb')
    <a href="{{ route('users.index') }}">Tài khoản</a> / {{ $user->username }}
@endsection
@section('page-title', 'Sửa tài khoản: ' . $user->name)
@section('page-subtitle', 'Tạo ngày ' . $user->created_at?->format('d/m/Y') . ($user->is_active ? '' : ' · Tài khoản đang bị khóa'))

@section('content')
    <form action="{{ route('users.update', $user) }}" method="POST" novalidate>
        @csrf @method('PUT')
        @include('users._form', ['submitLabel' => 'Lưu thay đổi'])
    </form>
@endsection
