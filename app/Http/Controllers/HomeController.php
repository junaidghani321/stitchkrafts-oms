<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(private readonly DashboardService $dashboard)
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     */
    public function userDashboard(Request $request): View
    {
        return view('user/index', $this->dashboard->forCustomer($request->user()));
    }

    /**
     * Show the application dashboard.
     */
    public function adminDashboard(Request $request): View
    {
        return view('admin/index', $this->dashboard->forAdmin($request->user()));
    }

    /**
     * Show the application dashboard.
     */
    public function managerDashboard(Request $request): View
    {
        return view('manager/index', $this->dashboard->forManager($request->user()));
    }

    /**
     * Show the application dashboard.
     */
    public function employeeDashboard(Request $request): View
    {
        return view('employee/index', $this->dashboard->forEmployee($request->user()));
    }
}
