@extends('layouts.app')

@section('title', 'Employee Dashboard')
@section('page_title', 'Employee Dashboard')
@section('page_heading', 'Dashboard')

@section('content')

@php
    /* All dashboard data comes from DashboardService via HomeController.
     | Only the modules this role can reach are shown.
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

    {{-- Tasks assigned to me --}}
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span>My Current Tasks</span>
            <a href="{{ route('employee.tasks.index') }}" class="small">View all</a>
        </div>
        <div class="card-body p-3">
            <div class="oms-table-wrap">
                <table class="oms-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Design Name</th>
                            <th>Type</th>
                            <th>Allowed</th>
                            <th>Deadline</th>
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
                                <td>
                                    {{ $task['order'] }}
                                    @if ($task['priority'] === 'urgent')
                                        <span class="oms-badge oms-badge--urgent ms-1">Urgent</span>
                                    @endif
                                </td>
                                <td>{{ $task['design'] }}</td>
                                <td>{{ $task['type'] }}</td>
                                <td>{{ $task['duration'] }}h</td>
                                <td>{{ $deadline->format('d-m-Y H:i') }}</td>
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

    <div class="row g-3">

        {{-- Performance snapshot --}}
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>My Performance</span>
                    <a href="{{ route('employee.performance') }}" class="small">Details</a>
                </div>
                <div class="card-body p-3 p-md-4">

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="oms-meter__value">{{ $score }}%</div>
                        <div class="flex-grow-1">
                            <div class="oms-meter__label mb-1">On-time completion</div>
                            <div class="oms-progress oms-progress--lg">
                                <div class="oms-progress__bar oms-progress__bar--{{ $score >= 90 ? 'good' : ($score >= 75 ? 'warn' : 'bad') }}"
                                     style="width: {{ $score }}%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="oms-meter__value">{{ $totals['completed'] }}</div>
                            <div class="oms-meter__label">Completed</div>
                        </div>
                        <div class="col-4">
                            <div class="oms-meter__value text-success">{{ $totals['on_time'] }}</div>
                            <div class="oms-meter__label">On Time</div>
                        </div>
                        <div class="col-4">
                            <div class="oms-meter__value text-danger">{{ $totals['late'] }}</div>
                            <div class="oms-meter__label">Late</div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

@endsection
