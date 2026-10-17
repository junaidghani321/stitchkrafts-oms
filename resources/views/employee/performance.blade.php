@extends('layouts.app')

@section('title', 'My Performance')
@section('page_title', 'My Performance')
@section('page_heading', 'My Performance')

@section('content')

@php
    /* Data comes from EmployeePerformanceService via the controller. */
    $now = now();



    $asDuration = function ($minutes) {
        $minutes = (int) abs($minutes);
        return intdiv($minutes, 60) . 'h ' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) . 'm';
    };
@endphp

    <div class="row g-3">

        {{-- Profile & score --}}
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-body p-3 p-md-4">

                    <div class="oms-profile">
                        <div class="oms-profile__avatar">{{ Str::substr($employee['name'], 0, 2) }}</div>
                        <div>
                            <div class="oms-profile__name">{{ $employee['name'] }}</div>
                            <div class="oms-profile__role">{{ $employee['designation'] }}</div>
                        </div>
                    </div>

                    <dl class="oms-detail">
                        <dt>Department</dt>
                        <dd>{{ $employee['department'] }}</dd>

                        <dt>Shift</dt>
                        <dd>{{ $employee['shift'] }}</dd>

                        <dt>Joined</dt>
                        <dd>{{ $employee['joined'] }}</dd>

                        <dt>Open Tasks</dt>
                        <dd>{{ $totals['active'] }}</dd>

                        <dt>Revisions Handled</dt>
                        <dd>{{ $totals['revisions'] }}</dd>
                    </dl>

                    <hr>

                    <div class="oms-meter__label mb-1">Overall Performance</div>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="oms-meter__value">{{ $score }}%</div>
                        <div class="flex-grow-1">
                            <div class="oms-progress oms-progress--lg">
                                <div class="oms-progress__bar oms-progress__bar--{{ $score >= 90 ? 'good' : ($score >= 75 ? 'warn' : 'bad') }}"
                                     style="width: {{ $score }}%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="oms-legend">
                        <span><i style="background:#198754"></i> On time {{ $score }}%</span>
                        <span><i style="background:#b02a2f"></i> Late {{ $latePc }}%</span>
                    </div>

                </div>
            </div>
        </div>

        {{-- Stats & chart --}}
        <div class="col-12 col-xl-8">

            <div class="row g-3 mb-3">
                <div class="col-6 col-lg-3">
                    <div class="oms-stat">
                        <div class="oms-stat__icon"><i class="fa-solid fa-clipboard-check"></i></div>
                        <div>
                            <div class="oms-stat__label">Completed</div>
                            <div class="oms-stat__value">{{ $totals['completed'] }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="oms-stat">
                        <div class="oms-stat__icon oms-stat__icon--good"><i class="fa-solid fa-circle-check"></i></div>
                        <div>
                            <div class="oms-stat__label">On Time</div>
                            <div class="oms-stat__value">{{ $totals['on_time'] }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="oms-stat">
                        <div class="oms-stat__icon oms-stat__icon--bad"><i class="fa-solid fa-circle-exclamation"></i></div>
                        <div>
                            <div class="oms-stat__label">Late</div>
                            <div class="oms-stat__value">{{ $totals['late'] }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="oms-stat">
                        <div class="oms-stat__icon oms-stat__icon--accent"><i class="fa-solid fa-stopwatch"></i></div>
                        <div>
                            <div class="oms-stat__label">Avg. Time</div>
                            <div class="oms-stat__value">{{ $totals['avg_hours'] }}h</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span>Tasks Completed — Last 7 Days</span>
                    <div class="oms-legend">
                        <span><i style="background:#198754"></i> On time</span>
                        <span><i style="background:#b02a2f"></i> Late</span>
                    </div>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div class="oms-bars">
                        @foreach ($week as $day)
                            @php
                                $onTimeHeight = $peak ? round($day['on_time'] / $peak * 100, 1) : 0;
                                $lateHeight   = $peak ? round($day['late'] / $peak * 100, 1) : 0;
                            @endphp

                            <div class="oms-bars__col">
                                <div class="oms-bars__track"
                                     title="{{ $day['day'] }}: {{ $day['on_time'] }} on time, {{ $day['late'] }} late">
                                    <div class="oms-bars__seg oms-bars__seg--good" style="height: {{ $onTimeHeight }}%"></div>
                                    <div class="oms-bars__seg oms-bars__seg--bad"  style="height: {{ $lateHeight }}%"></div>
                                </div>
                                <div class="oms-bars__label">{{ $day['day'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="row g-3 mt-0">

        {{-- Finished tasks --}}
        <div class="col-12 col-lg-7">
            <div class="card h-100">
                <div class="card-header">Recently Completed</div>
                <div class="card-body p-3">
                    <div class="oms-table-wrap">
                        <table class="oms-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Design Name</th>
                                    <th>Allowed</th>
                                    <th>Taken</th>
                                    <th>Finished</th>
                                    <th>Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recent as $task)
                                    @php
                                        $allowed = $task['allowed'] * 60;
                                        $late    = $task['taken'] > $allowed;
                                    @endphp

                                    <tr>
                                        <td>{{ $task['order'] }}</td>
                                        <td>{{ $task['design'] }}</td>
                                        <td>{{ $task['allowed'] }}h</td>
                                        <td>{{ $asDuration($task['taken']) }}</td>
                                        <td>{{ $task['finished'] }}</td>
                                        <td>
                                            <span class="oms-badge oms-badge--{{ $late ? 'unpaid' : 'done' }}">
                                                {{ $late
                                                    ? $asDuration($task['taken'] - $allowed) . ' over'
                                                    : $asDuration($allowed - $task['taken']) . ' early' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Activity --}}
        <div class="col-12 col-lg-5">
            <div class="card h-100">
                <div class="card-header">Recent Activity</div>
                <div class="card-body p-3 p-md-4">
                    <ul class="oms-timeline">
                        @foreach ($timeline as $entry)
                            <li class="{{ $entry['tone'] }}">
                                <div class="oms-timeline__title">{{ $entry['title'] }}</div>
                                <div class="oms-timeline__meta">{{ $entry['meta'] }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection
