@extends('layouts.app')

@section('title', 'New Vector Order')
@section('page_title', 'New Vector Order')
@section('page_heading', 'New Vector Order')

@section('breadcrumb')
    <li><a href="{{ route('user.orders.index') }}">Orders</a></li>
@endsection

@section('content')

    <div class="card">
        <div class="card-body p-3 p-md-4">

            <h2 class="oms-form-title">New Vector Order</h2>

            @include('orders.partials._form_vector', [
                'action'      => route('user.orders.vector.store'),
                'submitLabel' => 'Place Order',
            ])

        </div>
    </div>

@endsection
