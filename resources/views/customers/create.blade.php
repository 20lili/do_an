@extends('layouts.app')

@section('title', 'Thêm khách hàng')
@section('breadcrumb')
    <a href="{{ route('customers.index') }}">Khách hàng</a> / Thêm mới
@endsection
@section('page-title', 'Thêm khách hàng mới')
@section('page-subtitle', 'Các trường có dấu * là bắt buộc.')

@section('content')
    <form action="{{ route('customers.store') }}" method="POST" novalidate>
        @csrf
        @include('customers._form', ['submitLabel' => 'Lưu khách hàng'])
    </form>
@endsection
