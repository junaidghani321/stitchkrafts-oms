@extends('layouts.app')

@section('title', 'My Orders')
@section('page_title', 'My Orders')
@section('page_heading', 'My Orders')

@section('content')

@php
    /* $orders comes from Employee\OrderController@index via OrderRowPresenter. */
    $typeLabels = ['digitizing' => 'Digitizing', 'vector' => 'Vector', 'patch' => 'Patch'];

    $statusMeta = [
        'progress'  => ['label' => 'In Progress', 'badge' => 'progress'],
        'revision'  => ['label' => 'In Revision', 'badge' => 'urgent'],
        'done'      => ['label' => 'Completed',   'badge' => 'done'],
        'cancelled' => ['label' => 'Cancelled',   'badge' => 'cancelled'],
    ];
@endphp

    {{-- Summary --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="oms-stat">
                <div class="oms-stat__icon"><i class="fa-solid fa-cart-flatbed"></i></div>
                <div>
                    <div class="oms-stat__label">Orders Assigned</div>
                    <div class="oms-stat__value">{{ count($orders) }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <div class="oms-stat">
                <div class="oms-stat__icon oms-stat__icon--accent"><i class="fa-solid fa-spinner"></i></div>
                <div>
                    <div class="oms-stat__label">In Progress</div>
                    <div class="oms-stat__value">{{ collect($orders)->where('status', 'progress')->count() }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <div class="oms-stat">
                <div class="oms-stat__icon oms-stat__icon--good"><i class="fa-solid fa-circle-check"></i></div>
                <div>
                    <div class="oms-stat__label">Completed</div>
                    <div class="oms-stat__value">{{ collect($orders)->where('status', 'done')->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Orders Assigned To Me</div>
        <div class="card-body p-3 p-md-4" data-oms-table>

            <div class="oms-table-toolbar">
                <div class="oms-table-filters">
                    <label>
                        Show
                        <select class="form-select" data-oms-entries>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
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
                            @foreach ($statusMeta as $value => $meta)
                                <option value="{{ $value }}">{{ $meta['label'] }}</option>
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
                            <th>Due</th>
                            <th>Status</th>
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
                                <td>{{ $order['due'] }}</td>
                                <td>
                                    <span class="oms-badge oms-badge--{{ $statusMeta[$order['status']]['badge'] }}">
                                        {{ $statusMeta[$order['status']]['label'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="oms-actions">
                                        {{-- Opens the brief. It used to point at the order's
                                             conversation anchor, which went with the thread. --}}
                                        <a href="{{ route('employee.orders.show', $order['id']) }}"
                                           class="oms-action" title="Open order" aria-label="Open order">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
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

@endsection
