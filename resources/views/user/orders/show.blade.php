@extends('layouts.app')

@section('title', 'Order ' . $order->order_number)
@section('page_title', 'Order Details')
@section('page_heading', $order->order_number)

@section('breadcrumb')
    <li><a href="{{ route('user.orders.index') }}">Orders</a></li>
@endsection

@section('content')

    @include('orders.partials._detail')


    <div class="oms-form-actions">
        <a href="{{ route('user.orders.index') }}" class="btn btn-brand">Back to Orders</a>

        {{-- The policy only allows this while the order is still Pending. --}}
        @can('update', $order)
            <a href="{{ route('user.orders.edit', $order) }}" class="btn btn-primary">Edit Order</a>
        @endcan

        @can('requestRevision', $order)
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#revisionModal"
                    data-order-id="{{ $order->id }}"
                    data-order="{{ $order->order_number }}"
                    data-design="{{ $order->design_name }}">
                Request Revision
            </button>
        @endcan

        {{-- Only while the order is still Pending — see OrderPolicy@cancel. --}}
        @can('cancel', $order)
            <button type="button" class="btn btn-danger ms-auto" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                Cancel Order
            </button>
        @endcan
    </div>

    @can('cancel', $order)
        @include('orders.partials._cancel_order_modal', ['action' => route('user.orders.cancel', $order)])
    @endcan

    @can('requestRevision', $order)
        @include('orders.partials._revision_modal', ['action' => route('user.revisions.store')])
    @endcan

@endsection
