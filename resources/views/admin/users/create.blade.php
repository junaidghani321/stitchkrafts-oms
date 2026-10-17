@extends('layouts.app')

@section('title', 'Add User')
@section('page_title', 'Add User')
@section('page_heading', 'Add User')

@section('breadcrumb')
    <li><a href="{{ route('admin.users.index') }}">Users</a></li>
@endsection

@section('content')

    <div class="card">
        <div class="card-body p-3 p-md-4">

            <h2 class="oms-form-title">New User</h2>

            @include('admin.users._form', [
                'action'      => route('admin.users.store'),
                'submitLabel' => 'Create User',
            ])

        </div>
    </div>

@endsection
