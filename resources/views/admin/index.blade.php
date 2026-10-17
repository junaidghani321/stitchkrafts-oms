@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page_title', 'Admin Dashboard')
@section('page_heading', 'Dashboard')

@section('content')

@php
    /* All dashboard data comes from DashboardService via HomeController. */
    $now = now();

    /* Module counts – every module the admin can reach. */
    /* assigned_ago in minutes, duration in hours. */
    $statusLabels = ['pending' => 'Pending', 'progress' => 'In Progress', 'done' => 'Completed'];

    $asDuration = function ($minutes) {
        $minutes = (int) abs($minutes);
        return intdiv($minutes, 60) . 'h ' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) . 'm';
    };
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
            <a href="{{ route('admin.orders.digitizing') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Digitizing Order</a>
            <a href="{{ route('admin.orders.vector') }}"     class="btn btn-brand"><i class="fa-solid fa-plus me-1"></i> Vector Order</a>
            <a href="{{ route('admin.orders.patch') }}"      class="btn btn-brand"><i class="fa-solid fa-plus me-1"></i> Patch Order</a>
            <a href="{{ route('admin.tasks.index') }}"       class="btn btn-brand"><i class="fa-solid fa-list-check me-1"></i> Assign Task</a>
            <a href="{{ route('admin.billing') }}"           class="btn btn-brand"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Billing</a>
        </div>
    </div>

    <div class="row g-3 mb-3">

        {{-- Recent orders --}}
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Recent Orders</span>
                    <a href="{{ route('admin.orders.index') }}" class="small">View all</a>
                </div>
                <div class="card-body p-3">
                    <div class="oms-table-wrap">
                        <table class="oms-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Design Name</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recent as $order)
                                    <tr>
                                        <td>{{ $order['no'] }}</td>
                                        <td>{{ $order['customer'] }}</td>
                                        <td>{{ $order['design'] }}</td>
                                        <td>{{ $order['date'] }}</td>
                                        <td><span class="oms-badge oms-badge--{{ $order['status'] }}">{{ $statusLabels[$order['status']] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Live task board --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span>Tasks Running Now</span>
            <a href="{{ route('admin.tasks.index') }}" class="small">View all</a>
        </div>
        <div class="card-body p-3">
            <div class="oms-table-wrap">
                <table class="oms-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Employee</th>
                            <th>Allowed</th>
                            <th>Time Left</th>
                            <th>Progress</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tasks as $task)
                            @php
                                $allowed  = $task['duration'] * 60;
                                $deadline = $now->copy()->subMinutes($task['assigned_ago'])->addMinutes($allowed);
                                $overdue  = $task['assigned_ago'] > $allowed;
                                $percent  = min(100, (int) round($task['assigned_ago'] / $allowed * 100));
                            @endphp

                            <tr>
                                <td>{{ $task['order'] }}</td>
                                <td>{{ $task['employee'] }}</td>
                                <td>{{ $task['duration'] }}h</td>
                                <td>
                                    <span class="oms-timer" data-oms-countdown="{{ $deadline->getTimestamp() }}">
                                        {{ $asDuration($allowed - $task['assigned_ago']) }}
                                    </span>
                                </td>
                                <td style="min-width:130px">
                                    <div class="oms-progress">
                                        <div class="oms-progress__bar oms-progress__bar--{{ $overdue ? 'bad' : 'warn' }}" style="width: {{ $percent }}%"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="oms-badge oms-badge--{{ $overdue ? 'unpaid' : 'progress' }}">
                                        {{ $overdue ? 'Overdue' : 'In Progress' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
