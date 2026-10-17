@extends('layouts.app')

@section('title', 'Employees')
@section('page_title', 'Employees')
@section('page_heading', 'Employees')

@section('content')

@php
    /* Data comes from EmployeePerformanceService via the controller. */
    $statusLabels = ['active' => 'Active', 'inactive' => 'Inactive'];
@endphp

    <div class="card">
        <div class="card-header">Team Performance</div>
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
                            <th>Employee</th>
                            <th>Designation</th>
                            <th>Active</th>
                            <th>Completed</th>
                            <th>On Time</th>
                            <th>Late</th>
                            <th>Performance</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $employee)
                            @php
                                $score = $employee['completed']
                                    ? (int) round($employee['on_time'] / $employee['completed'] * 100)
                                    : 0;

                                $bar = $score >= 90 ? 'good' : ($score >= 75 ? 'warn' : 'bad');
                            @endphp

                            <tr data-row data-status="{{ $employee['status'] }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $employee['name'] }}</td>
                                <td>{{ $employee['designation'] }}</td>
                                <td>{{ $employee['active'] }}</td>
                                <td>{{ $employee['completed'] }}</td>
                                <td class="text-success fw-semibold">{{ $employee['on_time'] }}</td>
                                <td class="text-danger fw-semibold">{{ $employee['late'] }}</td>
                                <td style="min-width:150px">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="oms-progress flex-grow-1">
                                            <div class="oms-progress__bar oms-progress__bar--{{ $bar }}" style="width: {{ $score }}%"></div>
                                        </div>
                                        <span class="fw-bold small">{{ $score }}%</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="oms-badge oms-badge--{{ $employee['status'] === 'active' ? 'done' : 'unpaid' }}">
                                        {{ $statusLabels[$employee['status']] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="oms-actions">
                                        <a href="{{ route('manager.employees.show', $employee['id']) }}" class="oms-action"
                                           title="View performance" aria-label="View performance">
                                            <i class="fa-solid fa-chart-line"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        <tr data-oms-empty hidden>
                            <td colspan="10" class="oms-table-empty">No data available in table</td>
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
