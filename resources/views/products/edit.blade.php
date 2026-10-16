@extends('layouts.app')

@section('title', 'Sửa sản phẩm')
@section('page-title', 'Chỉnh sửa sản phẩm')
@section('page-subtitle', 'Tồn kho được cập nhật qua nhập kho và đơn hàng.')
@section('breadcrumb')
    <a href="{{ route('products.index') }}" >Sản phẩm</a> / {{ $product->product_code }}
@endsection

@section('content')
    <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        @include('products._form', ['submitLabel' => 'Cập nhật'])
    </form>
@endsection
