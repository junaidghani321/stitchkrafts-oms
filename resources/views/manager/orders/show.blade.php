@extends('layouts.app')

@section('title', 'Order ' . $order->order_number)
@section('page_title', 'Order Details')
@section('page_heading', $order->order_number)

@section('breadcrumb')
    <li><a href="{{ route('manager.orders.index') }}">Orders</a></li>
@endsection

@section('content')

    @include('orders.partials._detail')

    {{-- Renders only while the order is waiting on a sign-off. --}}
    @include('orders.partials._approval', ['role' => 'manager'])


    @include('orders.partials._staff_actions', ['role' => 'manager'])

@endsection
