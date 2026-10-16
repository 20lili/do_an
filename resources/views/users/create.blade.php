@extends('layouts.app')

@section('title', 'Thêm tài khoản')
@section('breadcrumb')
    <a href="{{ route('users.index') }}">Tài khoản</a> / Thêm mới
@endsection
@section('page-title', 'Thêm tài khoản')
@section('page-subtitle', 'Các trường có dấu * là bắt buộc.')

@section('content')
    <form action="{{ route('users.store') }}" method="POST" novalidate>
        @csrf
        @include('users._form', ['submitLabel' => 'Tạo tài khoản'])
    </form>
@endsection
