@extends('layouts.app')

@section('title', 'All Users')
@section('page_title', 'All Users')
@section('page_heading', 'All Users')

@section('breadcrumb')
    <li><a href="{{ route('admin.users.index') }}">Users</a></li>
@endsection

@section('content')

@php
    /* Data comes from the Admin UserController. */
    $typeLabels   = ['user' => 'Customer', 'employee' => 'Employee', 'manager' => 'Manager', 'admin' => 'Admin'];
    $statusLabels = ['active' => 'Active', 'inactive' => 'Inactive'];
    $statusStyles = ['active' => 'done', 'inactive' => 'unpaid'];
@endphp

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span>Users</span>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-user-plus me-1"></i> Add User
            </a>
        </div>
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
                        Role
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
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Company</th>
                            <th>Orders</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr data-row data-type="{{ $user['type'] }}" data-status="{{ $user['status'] }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $user['name'] }}</td>
                                <td>{{ $user['email'] }}</td>
                                <td>{{ $typeLabels[$user['type']] }}</td>
                                <td>{{ $user['company'] }}</td>
                                <td>{{ $user['orders'] }}</td>
                                <td><span class="oms-badge oms-badge--{{ $statusStyles[$user['status']] }}">{{ $statusLabels[$user['status']] }}</span></td>
                                <td>{{ $user['joined'] }}</td>
                                <td>
                                    <div class="oms-actions">
                                        <a href="{{ route('admin.users.show', $user['id']) }}" class="oms-action" title="View user" aria-label="View user">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user['id']) }}" class="oms-action" title="Edit user" aria-label="Edit user">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        {{-- Hidden for yourself and for the last admin: the policy
                                             refuses both, so the button is not offered. --}}
                                        @if ($user['deletable'])
                                            <button type="button" class="oms-action oms-action--danger"
                                                    title="Delete user" aria-label="Delete user"
                                                    data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                                    data-action="{{ route('admin.users.destroy', $user['id']) }}"
                                                    data-user-name="{{ $user['name'] }}">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        <tr data-oms-empty hidden>
                            <td colspan="9" class="oms-table-empty">No data available in table</td>
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

    {{-- Delete confirmation --}}
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong data-delete-user-name>this user</strong>?
                    Their orders and invoices stay in the system.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-brand" data-bs-dismiss="modal">Cancel</button>
                    <form action="#" method="POST" class="m-0" id="deleteUserForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    (function () {
        var modal = document.getElementById('deleteUserModal');

        modal.addEventListener('show.bs.modal', function (event) {
            if (! event.relatedTarget) return;

            var name = event.relatedTarget.getAttribute('data-user-name');

            modal.querySelector('[data-delete-user-name]').textContent = name || 'this user';

            // Route binding carries the id, so there is no hidden field to mismatch.
            var form = modal.querySelector('#deleteUserForm');
            if (form) form.action = event.relatedTarget.getAttribute('data-action') || '#';
        });
    })();
</script>
@endpush
