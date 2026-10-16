@extends('layouts.app')

@section('title', 'Sửa khách hàng')
@section('breadcrumb')
    <a href="{{ route('customers.index') }}">Khách hàng</a> / {{ $customer->code() }}
@endsection
@section('page-title', 'Chỉnh sửa khách hàng')
@section('page-subtitle', $customer->customer_name)

@section('content')
    <form action="{{ route('customers.update', $customer) }}" method="POST" novalidate>
        @csrf
        @method('PUT')
        @include('customers._form', ['submitLabel' => 'Cập nhật'])
    </form>
@endsection
