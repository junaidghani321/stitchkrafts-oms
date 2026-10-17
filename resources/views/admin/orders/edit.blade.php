@extends('layouts.app')

@section('title', 'Edit Order ' . $order->order_number)
@section('page_title', 'Edit Order')
@section('page_heading', 'Edit ' . $order->order_number)

@section('breadcrumb')
    <li><a href="{{ route('admin.orders.index') }}">Orders</a></li>
    <li><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></li>
@endsection

@section('content')

    <div class="card">
        <div class="card-body p-3 p-md-4">

            <h2 class="oms-form-title">{{ $order->type->label() }} Order — {{ $order->order_number }}</h2>

            {{-- Same partial as the create page, in edit mode. The customer
                 select is deliberately omitted: re-homing an order would
                 invalidate every ownership check already made against it. --}}
            @include('orders.partials._form_' . $order->type->value, [
                'action'      => route('admin.orders.update', $order),
                'method'      => 'PUT',
                'submitLabel' => 'Save Changes',
                'order'       => $order,
            ])

        </div>
    </div>

@endsection
