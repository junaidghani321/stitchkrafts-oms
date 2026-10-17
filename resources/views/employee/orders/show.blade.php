@extends('layouts.app')

@section('title', 'Order ' . $order->order_number)
@section('page_title', 'Order Details')
@section('page_heading', $order->order_number)

@section('breadcrumb')
    <li><a href="{{ route('employee.orders.index') }}">Orders</a></li>
@endsection

@section('content')

    {{-- Read-only. An employee works through tasks, never the order itself. --}}
    @include('orders.partials._detail')


    <div class="oms-form-actions">
        <a href="{{ route('employee.orders.index') }}" class="btn btn-brand">Back to My Orders</a>
        <a href="{{ route('employee.tasks.index') }}" class="btn btn-primary">My Tasks</a>
    </div>

@endsection
