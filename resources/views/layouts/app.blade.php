<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0a4354">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') · {{ config('app.name', 'OMS') }}</title>

    <link rel="icon" href="{{ asset('assets/images/logo.webp') }}" type="image/webp">
    <link href="{{ asset('assets/bootstrap-5.3.8/css/bootstrap.min.css') }}" rel="stylesheet">

    <link href="{{ asset('assets/fontawesome-7.3.1/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/style.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body>
@php
    $authUser = auth()->user();
    $role     = (string) ($authUser->type ?? 'user');

    $homeRoute = match ($role) {
        'admin'    => Route::has('admin.dashboard')    ? route('admin.dashboard')    : url('/'),
        'manager'  => Route::has('manager.dashboard')  ? route('manager.dashboard')  : url('/'),
        'employee' => Route::has('employee.dashboard') ? route('employee.dashboard') : url('/'),
        'user'     => Route::has('user.dashboard')     ? route('user.dashboard')     : url('/'),
        default    => url('/'),
    };
@endphp

{{-- ============================== SIDEBAR ============================== --}}
<aside class="oms-sidebar" id="omsSidebar">

    <div class="oms-sidebar__brand">
        <a href="{{ $homeRoute }}" class="oms-sidebar__logo">
            <img src="{{ asset('assets/images/logo.webp') }}" alt="{{ config('app.name', 'OMS') }}">
        </a>
        <a href="{{ $homeRoute }}" class="oms-sidebar__name">{{ config('app.name', 'OMS') }}</a>
    </div>

    <nav class="oms-sidebar__nav">
        <div class="oms-nav-heading">Navigation</div>
        <ul class="oms-nav">
            <li>
                <a href="{{ $homeRoute }}" class="oms-nav-link {{ request()->routeIs('*dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            @if ($role === 'admin')
                @php
                    $ordersOpen = request()->routeIs('admin.orders.*');
                    $quotesOpen = request()->routeIs('admin.quotes.*');
                    $usersOpen  = request()->routeIs('admin.users.*');
                @endphp

                <li>
                    <a href="#omsNavOrders" class="oms-nav-link" data-bs-toggle="collapse"
                       role="button" aria-expanded="{{ $ordersOpen ? 'true' : 'false' }}" aria-controls="omsNavOrders">
                        <i class="fa-solid fa-cart-flatbed"></i>
                        <span>Orders</span>
                        <i class="fa-solid fa-chevron-right oms-nav-link__caret"></i>
                    </a>
                    <ul class="collapse oms-nav-sub {{ $ordersOpen ? 'show' : '' }}" id="omsNavOrders">
                        <li><a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.index') ? 'active' : '' }}">All Orders</a></li>
                        <li><a href="{{ route('admin.orders.digitizing') }}" class="{{ request()->routeIs('admin.orders.digitizing') ? 'active' : '' }}">New Digitizing Order</a></li>
                        <li><a href="{{ route('admin.orders.vector') }}" class="{{ request()->routeIs('admin.orders.vector') ? 'active' : '' }}">New Vector Order</a></li>
                        <li><a href="{{ route('admin.orders.patch') }}" class="{{ request()->routeIs('admin.orders.patch') ? 'active' : '' }}">New Patch Order</a></li>
                    </ul>
                </li>

                <li>
                    <a href="#omsNavQuotes" class="oms-nav-link" data-bs-toggle="collapse"
                       role="button" aria-expanded="{{ $quotesOpen ? 'true' : 'false' }}" aria-controls="omsNavQuotes">
                        <i class="fa-solid fa-file-invoice"></i>
                        <span>Quotes</span>
                        <i class="fa-solid fa-chevron-right oms-nav-link__caret"></i>
                    </a>
                    <ul class="collapse oms-nav-sub {{ $quotesOpen ? 'show' : '' }}" id="omsNavQuotes">
                        <li><a href="{{ route('admin.quotes.index') }}" class="{{ request()->routeIs('admin.quotes.index') ? 'active' : '' }}">All Quotes</a></li>
                        <li><a href="{{ route('admin.quotes.digitizing') }}" class="{{ request()->routeIs('admin.quotes.digitizing') ? 'active' : '' }}">New Digitizing Quote</a></li>
                        <li><a href="{{ route('admin.quotes.vector') }}" class="{{ request()->routeIs('admin.quotes.vector') ? 'active' : '' }}">New Vector Quote</a></li>
                        <li><a href="{{ route('admin.quotes.patch') }}" class="{{ request()->routeIs('admin.quotes.patch') ? 'active' : '' }}">New Patch Quote</a></li>
                    </ul>
                </li>

                <li>
                    <a href="#omsNavUsers" class="oms-nav-link" data-bs-toggle="collapse"
                       role="button" aria-expanded="{{ $usersOpen ? 'true' : 'false' }}" aria-controls="omsNavUsers">
                        <i class="fa-solid fa-users"></i>
                        <span>Users</span>
                        <i class="fa-solid fa-chevron-right oms-nav-link__caret"></i>
                    </a>
                    <ul class="collapse oms-nav-sub {{ $usersOpen ? 'show' : '' }}" id="omsNavUsers">
                        <li><a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.index') ? 'active' : '' }}">All Users</a></li>
                        <li><a href="{{ route('admin.users.create') }}" class="{{ request()->routeIs('admin.users.create') ? 'active' : '' }}">Add User</a></li>
                    </ul>
                </li>

                <li>
                    <a href="{{ route('admin.tasks.index') }}" class="oms-nav-link {{ request()->routeIs('admin.tasks.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-list-check"></i>
                        <span>Tasks</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.employees.index') }}" class="oms-nav-link {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-user-gear"></i>
                        <span>Employees</span>
                    </a>
                </li>


                <li>
                    <a href="{{ route('admin.billing') }}" class="oms-nav-link {{ request()->routeIs('admin.billing') ? 'active' : '' }}">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>Billing</span>
                    </a>
                </li>


                <li class="oms-nav-heading">Settings</li>

                <li>
                    <a href="{{ route('admin.settings.profile') }}" class="oms-nav-link {{ request()->routeIs('admin.settings.profile') ? 'active' : '' }}">
                        <i class="fa-regular fa-user"></i>
                        <span>My Profile</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.settings.email') }}" class="oms-nav-link {{ request()->routeIs('admin.settings.email') ? 'active' : '' }}">
                        <i class="fa-regular fa-envelope"></i>
                        <span>Email Settings</span>
                    </a>
                </li>
            @endif

            {{-- Manager: everything the admin sees except billing, payments and settings. --}}
            @if ($role === 'manager')
                <li>
                    <a href="{{ route('manager.orders.index') }}" class="oms-nav-link {{ request()->routeIs('manager.orders.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-cart-flatbed"></i>
                        <span>Orders</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('manager.quotes.index') }}" class="oms-nav-link {{ request()->routeIs('manager.quotes.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-file-invoice"></i>
                        <span>Quotes</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('manager.tasks.index') }}" class="oms-nav-link {{ request()->routeIs('manager.tasks.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-list-check"></i>
                        <span>Tasks</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('manager.employees.index') }}" class="oms-nav-link {{ request()->routeIs('manager.employees.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-user-gear"></i>
                        <span>Employees</span>
                    </a>
                </li>



                <li class="oms-nav-heading">Settings</li>

                <li>
                    <a href="{{ route('manager.settings.profile') }}" class="oms-nav-link {{ request()->routeIs('manager.settings.profile') ? 'active' : '' }}">
                        <i class="fa-regular fa-user"></i>
                        <span>My Profile</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('manager.settings.email') }}" class="oms-nav-link {{ request()->routeIs('manager.settings.email') ? 'active' : '' }}">
                        <i class="fa-regular fa-envelope"></i>
                        <span>Email Settings</span>
                    </a>
                </li>
            @endif

            {{-- Employee: only their own work. --}}
            @if ($role === 'employee')
                <li>
                    <a href="{{ route('employee.tasks.index') }}" class="oms-nav-link {{ request()->routeIs('employee.tasks.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-list-check"></i>
                        <span>My Tasks</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('employee.orders.index') }}" class="oms-nav-link {{ request()->routeIs('employee.orders.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-cart-flatbed"></i>
                        <span>My Orders</span>
                    </a>
                </li>



                <li>
                    <a href="{{ route('employee.performance') }}" class="oms-nav-link {{ request()->routeIs('employee.performance') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>My Performance</span>
                    </a>
                </li>

                <li class="oms-nav-heading">Settings</li>

                <li>
                    <a href="{{ route('employee.settings.profile') }}" class="oms-nav-link {{ request()->routeIs('employee.settings.profile') ? 'active' : '' }}">
                        <i class="fa-regular fa-user"></i>
                        <span>My Profile</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('employee.settings.email') }}" class="oms-nav-link {{ request()->routeIs('employee.settings.email') ? 'active' : '' }}">
                        <i class="fa-regular fa-envelope"></i>
                        <span>Email Settings</span>
                    </a>
                </li>
            @endif

            {{-- Customer: their own orders, quotes and invoices. --}}
            @if ($role === 'user')
                @php
                    $userOrdersOpen = request()->routeIs('user.orders.*');
                    $userQuotesOpen = request()->routeIs('user.quotes.*');
                @endphp

                <li>
                    <a href="#omsNavUserOrders" class="oms-nav-link" data-bs-toggle="collapse"
                       role="button" aria-expanded="{{ $userOrdersOpen ? 'true' : 'false' }}" aria-controls="omsNavUserOrders">
                        <i class="fa-solid fa-cart-flatbed"></i>
                        <span>Orders</span>
                        <i class="fa-solid fa-chevron-right oms-nav-link__caret"></i>
                    </a>
                    <ul class="collapse oms-nav-sub {{ $userOrdersOpen ? 'show' : '' }}" id="omsNavUserOrders">
                        <li><a href="{{ route('user.orders.index') }}" class="{{ request()->routeIs('user.orders.index') ? 'active' : '' }}">My Orders</a></li>
                        <li><a href="{{ route('user.orders.digitizing') }}" class="{{ request()->routeIs('user.orders.digitizing') ? 'active' : '' }}">New Digitizing Order</a></li>
                        <li><a href="{{ route('user.orders.vector') }}" class="{{ request()->routeIs('user.orders.vector') ? 'active' : '' }}">New Vector Order</a></li>
                        <li><a href="{{ route('user.orders.patch') }}" class="{{ request()->routeIs('user.orders.patch') ? 'active' : '' }}">New Patch Order</a></li>
                    </ul>
                </li>

                <li>
                    <a href="#omsNavUserQuotes" class="oms-nav-link" data-bs-toggle="collapse"
                       role="button" aria-expanded="{{ $userQuotesOpen ? 'true' : 'false' }}" aria-controls="omsNavUserQuotes">
                        <i class="fa-solid fa-file-invoice"></i>
                        <span>Quotes</span>
                        <i class="fa-solid fa-chevron-right oms-nav-link__caret"></i>
                    </a>
                    <ul class="collapse oms-nav-sub {{ $userQuotesOpen ? 'show' : '' }}" id="omsNavUserQuotes">
                        <li><a href="{{ route('user.quotes.index') }}" class="{{ request()->routeIs('user.quotes.index') ? 'active' : '' }}">My Quotes</a></li>
                        <li><a href="{{ route('user.quotes.digitizing') }}" class="{{ request()->routeIs('user.quotes.digitizing') ? 'active' : '' }}">New Digitizing Quote</a></li>
                        <li><a href="{{ route('user.quotes.vector') }}" class="{{ request()->routeIs('user.quotes.vector') ? 'active' : '' }}">New Vector Quote</a></li>
                        <li><a href="{{ route('user.quotes.patch') }}" class="{{ request()->routeIs('user.quotes.patch') ? 'active' : '' }}">New Patch Quote</a></li>
                    </ul>
                </li>


                <li>
                    <a href="{{ route('user.billing') }}" class="oms-nav-link {{ request()->routeIs('user.billing') ? 'active' : '' }}">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>Billing</span>
                    </a>
                </li>


                <li class="oms-nav-heading">Settings</li>

                <li>
                    <a href="{{ route('user.settings.profile') }}" class="oms-nav-link {{ request()->routeIs('user.settings.profile') ? 'active' : '' }}">
                        <i class="fa-regular fa-user"></i>
                        <span>My Profile</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('user.settings.email') }}" class="oms-nav-link {{ request()->routeIs('user.settings.email') ? 'active' : '' }}">
                        <i class="fa-regular fa-envelope"></i>
                        <span>Email Settings</span>
                    </a>
                </li>
            @endif
        </ul>
    </nav>

</aside>

<div class="oms-backdrop" id="omsBackdrop"></div>

{{-- =============================== MAIN =============================== --}}
<div class="oms-main">

    {{-- Topbar --}}
    <header class="oms-topbar">
        <button type="button" class="oms-icon-btn oms-icon-btn--plain" id="omsSidebarToggle" aria-label="Toggle menu">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="oms-topbar__title">@yield('page_title', 'Dashboard')</div>

        <div class="dropdown">
            <a href="#" class="oms-avatar" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                {{ Str::substr($authUser->name, 0, 2) }}
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <h6 class="dropdown-header">
                        {{ $authUser->name }}<br>
                        <span class="text-capitalize">{{ $role }}</span>
                    </h6>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="{{ route($role.'.settings.profile') }}">
                        <i class="fa-regular fa-user me-1"></i> My Profile
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        {{ __('Logout') }}
                    </a>
                </li>
            </ul>
        </div>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    </header>

    {{-- Page title bar --}}
    <div class="oms-pagebar">
        <h1>@yield('page_heading', 'Dashboard')</h1>
        <ul class="oms-breadcrumb">
            <li><a href="{{ $homeRoute }}">{{ config('app.name', 'OMS') }}</a></li>
            @yield('breadcrumb')
            <li class="active">@yield('page_heading', 'Dashboard')</li>
        </ul>
    </div>

    {{-- Content --}}
    <main class="oms-content">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

</div>

<script src="{{ asset('assets/bootstrap-5.3.8/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/oms-table.js') }}"></script>
<script src="{{ asset('assets/oms-timer.js') }}"></script>
<script>
    (function () {
        var body     = document.body;
        var toggle   = document.getElementById('omsSidebarToggle');
        var backdrop = document.getElementById('omsBackdrop');
        var DESKTOP  = window.matchMedia('(min-width: 992px)');
        var STORE_KEY = 'oms.sidebar.hidden';

        // Restore the desktop preference before the user interacts.
        if (DESKTOP.matches && localStorage.getItem(STORE_KEY) === '1') {
            body.classList.add('sidebar-hidden');
        }

        function close() {
            body.classList.remove('sidebar-open');
        }

        toggle.addEventListener('click', function () {
            if (DESKTOP.matches) {
                body.classList.toggle('sidebar-hidden');
                localStorage.setItem(STORE_KEY, body.classList.contains('sidebar-hidden') ? '1' : '0');
            } else {
                body.classList.toggle('sidebar-open');
            }
        });

        backdrop.addEventListener('click', close);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') close();
        });

        // Leaving mobile width should never leave the off-canvas state behind.
        DESKTOP.addEventListener('change', close);
    })();
</script>

@stack('scripts')
</body>
</html>
