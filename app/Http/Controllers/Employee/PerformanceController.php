<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\EmployeePerformanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** The employee's own performance page — same figures, their own record. */
class PerformanceController extends Controller
{
    public function __construct(private readonly EmployeePerformanceService $performance) {}

    public function __invoke(Request $request): View
    {
        return view('employee.performance', $this->performance->fullProfile($request->user()));
    }
}
