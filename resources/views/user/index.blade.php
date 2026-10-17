@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_heading', 'Dashboard')

@section('content')

@php
    /* All dashboard data comes from DashboardService via HomeController. */

    /* Module counts – every module this customer can reach. */
    /* Orders still being worked on – percent is how far along production is. */
    $statusLabels = ['pending' => 'Pending', 'progress' => 'In Progress', 'done' => 'Completed'];
@endphp

    {{-- Module counts --}}
    <div class="row g-3 mb-3">
        @foreach ($modules as $module)
            <div class="col-12 col-sm-6 col-xl-3">
                <a href="{{ $module['route'] }}" class="oms-stat">
                    <div class="oms-stat__icon {{ $module['tone'] }}"><i class="fa-solid {{ $module['icon'] }}"></i></div>
                    <div>
                        <div class="oms-stat__label">{{ $module['label'] }}</div>
                        <div class="oms-stat__value">{{ $module['value'] }}</div>
                        <div class="oms-switch__hint">{{ $module['note'] }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    {{-- Quick actions --}}
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-2">
            <a href="{{ route('user.orders.digitizing') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Digitizing Order</a>
            <a href="{{ route('user.orders.vector') }}"     class="btn btn-brand"><i class="fa-solid fa-plus me-1"></i> Vector Order</a>
            <a href="{{ route('user.orders.patch') }}"      class="btn btn-brand"><i class="fa-solid fa-plus me-1"></i> Patch Order</a>
            <a href="{{ route('user.quotes.digitizing') }}" class="btn btn-brand"><i class="fa-solid fa-file-invoice me-1"></i> Request Quote</a>
            <a href="{{ route('user.billing') }}"           class="btn btn-brand"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Billing</a>
        </div>
    </div>

    <div class="row g-3">

        {{-- Where my orders are --}}
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header">Orders In Progress</div>
                <div class="card-body p-3 p-md-4">

                    @foreach ($active as $order)
                        <div class="{{ $loop->last ? '' : 'mb-4' }}">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div>
                                    <div class="fw-semibold">{{ $order['no'] }} — {{ $order['design'] }}</div>
                                    <div class="oms-switch__hint">{{ $order['type'] }} · {{ $order['stage'] }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="oms-meter__label">Expected</div>
                                    <div class="fw-semibold">{{ $order['due'] }}</div>
                                </div>
                            </div>

                            <div class="oms-progress mt-2">
                                <div class="oms-progress__bar" style="width: {{ $order['percent'] }}%"></div>
                            </div>
                            <div class="oms-switch__hint mt-1">{{ $order['percent'] }}% complete</div>
                        </div>
                    @endforeach

                    <a href="{{ route('user.orders.index') }}" class="btn btn-brand btn-sm mt-2">
                        <i class="fa-solid fa-list me-1"></i> View all orders
                    </a>

                </div>
            </div>
        </div>

        {{-- Recent orders --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">Recent Orders</div>
                <div class="card-body p-3 p-md-4">

                    <div class="oms-table-wrap">
                        <table class="oms-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Type</th>
                                    <th>Design Name</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recent as $order)
                                    <tr>
                                        <td>{{ $order['no'] }}</td>
                                        <td>{{ $order['type'] }}</td>
                                        <td>{{ $order['design'] }}</td>
                                        <td>{{ $order['date'] }}</td>
                                        <td><span class="oms-badge oms-badge--{{ $order['status'] }}">{{ $statusLabels[$order['status']] }}</span></td>
                                        <td><span class="oms-price {{ $order['price'] == 0 ? 'oms-price--zero' : '' }}">$ {{ number_format($order['price'], 2) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

    </div>

@endsection
