@extends('layouts.app')

@section('title', 'Manager Dashboard')
@section('page_title', 'Manager Dashboard')
@section('page_heading', 'Dashboard')

@section('content')

@php
    /* All dashboard data comes from DashboardService via HomeController.
     | Billing and payment figures are deliberately absent: this role has no
     | access to those modules.
     */
    $now = now();

    /* assigned_ago in minutes, duration in hours. */
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

    <div class="row g-3 mb-3">

        {{-- Order mix --}}
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header">Orders by Type</div>
                <div class="card-body p-3 p-md-4">
                    @foreach ($orderStats as $stat)
                        @php $percent = (int) round($stat['value'] / $stat['total'] * 100); @endphp

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold small">{{ $stat['label'] }}</span>
                                <span class="text-muted small">{{ $stat['value'] }} · {{ $percent }}%</span>
                            </div>
                            <div class="oms-progress">
                                <div class="oms-progress__bar {{ $stat['bar'] ? 'oms-progress__bar--' . $stat['bar'] : '' }}"
                                     style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @endforeach

                    <hr>

                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="oms-meter__value">37</div>
                            <div class="oms-meter__label">In Progress</div>
                        </div>
                        <div class="col-4">
                            <div class="oms-meter__value text-success">18</div>
                            <div class="oms-meter__label">Done Today</div>
                        </div>
                        <div class="col-4">
                            <div class="oms-meter__value text-danger">3</div>
                            <div class="oms-meter__label">Overdue</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Team performance --}}
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Team Performance</span>
                    <a href="{{ route('manager.employees.index') }}" class="small">View all</a>
                </div>
                <div class="card-body p-3">
                    <div class="oms-table-wrap">
                        <table class="oms-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Active</th>
                                    <th>Completed</th>
                                    <th>On Time</th>
                                    <th>Performance</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($team as $member)
                                    @php
                                        /* Guarded: a new employee has completed nothing yet, and the
                                           mock data never had a zero here. */
                                        $score = $member['completed'] > 0
                                            ? (int) round($member['on_time'] / $member['completed'] * 100)
                                            : 0;
                                        $bar   = $score >= 90 ? 'good' : ($score >= 75 ? 'warn' : 'bad');
                                    @endphp

                                    <tr>
                                        <td>{{ $member['name'] }}</td>
                                        <td>{{ $member['active'] }}</td>
                                        <td>{{ $member['completed'] }}</td>
                                        <td class="text-success fw-semibold">{{ $member['on_time'] }}</td>
                                        <td style="min-width:150px">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="oms-progress flex-grow-1">
                                                    <div class="oms-progress__bar oms-progress__bar--{{ $bar }}" style="width: {{ $score }}%"></div>
                                                </div>
                                                <span class="fw-bold small">{{ $score }}%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="oms-actions">
                                                <a href="{{ route('manager.employees.show', $member['id']) }}" class="oms-action"
                                                   title="View performance" aria-label="View performance">
                                                    <i class="fa-solid fa-chart-line"></i>
                                                </a>
                                            </div>
                                        </td>
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
            <a href="{{ route('manager.tasks.index') }}" class="small">View all</a>
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
