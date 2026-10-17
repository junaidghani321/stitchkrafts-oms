@extends('layouts.app')

@section('title', 'New Patch Order')
@section('page_title', 'New Patch Order')
@section('page_heading', 'New Patch Order')

@section('breadcrumb')
    <li><a href="{{ route('admin.orders.index') }}">Orders</a></li>
@endsection

@section('content')

    <div class="card">
        <div class="card-body p-3 p-md-4">

            <h2 class="oms-form-title">New Patch Order</h2>

            @include('orders.partials._form_patch', [
                'action'      => route('admin.orders.patch.store'),
                'submitLabel' => 'Place Order',
                'customers'   => $customers,
            ])

        </div>
    </div>

@endsection
