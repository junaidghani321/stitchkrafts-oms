@extends('layouts.app')

@section('title', 'User Profile')
@section('page_title', 'User Profile')
@section('page_heading', 'User Profile')

@section('breadcrumb')
    <li><a href="{{ route('admin.users.index') }}">Users</a></li>
@endsection

@section('content')

@php
    /* Data comes from the Admin UserController. */
    $typeLabels   = ['user' => 'Customer', 'employee' => 'Employee', 'manager' => 'Manager', 'admin' => 'Admin'];
    /* Straight from the enum so a new status cannot be missed here. */
    $statusLabels = App\Enums\OrderStatus::badgeOptions();
@endphp

    <div class="row g-3">

        {{-- Profile --}}
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-body p-3 p-md-4">

                    <div class="oms-profile">
                        <div class="oms-profile__avatar">{{ Str::substr($user['name'], 0, 2) }}</div>
                        <div>
                            <div class="oms-profile__name">{{ $user['name'] }}</div>
                            <div class="oms-profile__role">{{ $typeLabels[$user['type']] }}</div>
                        </div>
                    </div>

                    <dl class="oms-detail">
                        <dt>Email</dt>
                        <dd><a href="mailto:{{ $user['email'] }}">{{ $user['email'] }}</a></dd>

                        <dt>Phone</dt>
                        <dd>{{ $user['phone'] ?: '—' }}</dd>

                        <dt>Company</dt>
                        <dd>{{ $user['company'] ?: '—' }}</dd>

                        <dt>Status</dt>
                        <dd>
                            <span class="oms-badge oms-badge--{{ $user['status'] === 'active' ? 'done' : 'unpaid' }}">
                                {{ ucfirst($user['status']) }}
                            </span>
                        </dd>

                        <dt>Joined</dt>
                        <dd>{{ $user['joined'] }}</dd>

                        <dt>Internal Notes</dt>
                        <dd>{{ $user['notes'] ?: '—' }}</dd>
                    </dl>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a href="{{ route('admin.users.edit', $user['id']) }}" class="btn btn-primary">
                            <i class="fa-solid fa-pen me-1"></i> Edit
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-brand">Back to Users</a>
                    </div>

                </div>
            </div>
        </div>

        {{-- Activity --}}
        <div class="col-12 col-xl-8">

            <div class="row g-3 mb-3">
                @foreach ($stats as $stat)
                    <div class="col-12 col-sm-6">
                        <div class="oms-stat">
                            <div class="oms-stat__icon {{ $stat['tone'] }}"><i class="fa-solid {{ $stat['icon'] }}"></i></div>
                            <div>
                                <div class="oms-stat__label">{{ $stat['label'] }}</div>
                                <div class="oms-stat__value">{{ $stat['value'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card">
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
                                    <th>Design Name</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $order)
                                    <tr>
                                        <td>{{ $order['no'] }}</td>
                                        <td>{{ $order['design'] }}</td>
                                        <td>{{ $order['date'] }}</td>
                                        <td><span class="oms-badge oms-badge--{{ $order['status'] }}">{{ $statusLabels[$order['status']] }}</span></td>
                                        <td><span class="oms-price {{ $order['price'] == 0 ? 'oms-price--zero' : '' }}">$ {{ number_format($order['price'], 2) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>

@endsection
