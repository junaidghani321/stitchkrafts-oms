@extends('layouts.app')

@section('title', 'Edit User')
@section('page_title', 'Edit User')
@section('page_heading', $user['name'])

@section('breadcrumb')
    <li><a href="{{ route('admin.users.index') }}">Users</a></li>
    <li><a href="{{ route('admin.users.show', $user['id']) }}">{{ $user['name'] }}</a></li>
@endsection

@section('content')

    <div class="card">
        <div class="card-body p-3 p-md-4">

            <h2 class="oms-form-title">Edit {{ $user['name'] }}</h2>

            @include('admin.users._form', [
                'action'      => route('admin.users.update', $user['id']),
                'method'      => 'PUT',
                'submitLabel' => 'Update User',
                'user'        => $user,
            ])

        </div>
    </div>

@endsection
