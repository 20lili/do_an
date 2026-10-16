@extends('layouts.app')

@section('title', 'Thêm sản phẩm')
@section('page-title', 'Thêm sản phẩm mới')
@section('page-subtitle', 'Nhập thông tin mặt hàng mới; tồn kho ban đầu bằng 0.')
@section('breadcrumb')
    <a href="{{ route('products.index') }}" >Sản phẩm</a> / Thêm mới
@endsection

@section('content')
    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        @include('products._form', ['submitLabel' => 'Lưu sản phẩm'])
    </form>
@endsection
