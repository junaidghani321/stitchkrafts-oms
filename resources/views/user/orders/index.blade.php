@extends('layouts.app')

@section('title', 'My Orders')
@section('page_title', 'My Orders')
@section('page_heading', 'My Orders')

@section('breadcrumb')
    <li><a href="{{ route('user.orders.index') }}">Orders</a></li>
@endsection

@section('content')

@php
    /* $orders comes from User\OrderController@index via OrderRowPresenter. */
    /* Straight from the enum so a new status cannot be missed here. */
    $statusLabels = App\Enums\OrderStatus::badgeOptions();
    $typeLabels   = ['digitizing' => 'Digitizing', 'vector' => 'Vector', 'patch' => 'Patch'];
@endphp

    <div class="card">
        <div class="card-body p-3 p-md-4" data-oms-table>

            <div class="oms-table-toolbar">
                <div class="oms-table-filters">
                    <label>
                        Show
                        <select class="form-select" data-oms-entries>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        entries
                    </label>

                    <label>
                        Type
                        <select class="form-select" data-oms-filter="type">
                            <option value="">All</option>
                            @foreach ($typeLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Status
                        <select class="form-select" data-oms-filter="status">
                            <option value="">All</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <label>
                    Search:
                    <input type="search" class="form-control" data-oms-search placeholder="Type to filter…">
                </label>
            </div>

            <div class="oms-table-wrap">
                <table class="oms-table">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Order #</th>
                            <th>Type</th>
                            <th>Design Name</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr data-row data-type="{{ $order['type'] }}" data-status="{{ $order['status'] }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $order['no'] }}</td>
                                <td>{{ $typeLabels[$order['type']] }}</td>
                                <td>{{ $order['design'] }}</td>
                                <td>{{ $order['date'] }}</td>
                                <td><span class="oms-badge oms-badge--{{ $order['status'] }}">{{ $statusLabels[$order['status']] }}</span></td>
                                <td><span class="oms-price {{ $order['price'] == 0 ? 'oms-price--zero' : '' }}">$ {{ number_format($order['price'], 2) }}</span></td>
                                <td>
                                    <div class="oms-actions">
                                        @if ($order['status'] === 'pending')
                                            <a href="{{ route('user.orders.edit', $order['id']) }}" class="oms-action" title="Edit order" aria-label="Edit order"><i class="fa-solid fa-pen"></i></a>
                                        @endif
                                        <a href="{{ route('user.orders.show', $order['id']) }}" class="oms-action" title="View order" aria-label="View order"><i class="fa-solid fa-eye"></i></a>

                                        {{-- Revisions only make sense once the order is delivered. --}}
                                        @if ($order['status'] === 'done')
                                            <button type="button" class="oms-action" title="Request revision" aria-label="Request revision"
                                                    data-bs-toggle="modal" data-bs-target="#revisionModal"
                                                    data-order-id="{{ $order['id'] }}"
                                                    data-order="{{ $order['no'] }}"
                                                    data-design="{{ $order['design'] }}">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        <tr data-oms-empty hidden>
                            <td colspan="8" class="oms-table-empty">No data available in table</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="oms-table-foot">
                <div data-oms-info></div>
                <div class="oms-pager" data-oms-pager></div>
            </div>

        </div>
    </div>

    {{-- Request revision: fill in, review, submit.
         Shared partial — posts order_id (the real primary key), not the
         display number the old inline copy used. --}}
    @include('orders.partials._revision_modal', ['action' => route('user.revisions.store')])

@endsection
