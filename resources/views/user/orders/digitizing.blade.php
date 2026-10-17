@extends('layouts.app')

@section('title', 'New Digitizing Order')
@section('page_title', 'New Digitizing Order')
@section('page_heading', 'New Digitizing Order')

@section('breadcrumb')
    <li><a href="{{ route('user.orders.index') }}">Orders</a></li>
@endsection

@section('content')

    <div class="card">
        <div class="card-body p-3 p-md-4">

            <h2 class="oms-form-title">New Digitizing Order</h2>

            @include('orders.partials._form_digitizing', [
                'action'      => route('user.orders.digitizing.store'),
                'submitLabel' => 'Place Order',
            ])

        </div>
    </div>

@endsection
