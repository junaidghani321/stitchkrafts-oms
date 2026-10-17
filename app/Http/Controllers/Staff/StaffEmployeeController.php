<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\EmployeePerformanceService;
use App\Support\OrderRowPresenter;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** admin/employees and manager/employees are identical bar the routes. */
abstract class StaffEmployeeController extends Controller
{
    public function __construct(
        protected readonly EmployeePerformanceService $performance,
        protected readonly OrderRowPresenter $presenter,
    ) {}

    abstract protected function role(): string;

    public function index(): View
    {
        return view($this->role().'.employees.index', [
            'employees' => $this->performance->team(),
            'role' => $this->role(),
        ]);
    }

    /**
     * The route parameter is {employee}, which implicit binding resolves as a
     * User. Anyone who is not an employee must 404 rather than render an
     * empty profile for a customer.
     */
    public function show(User $employee): View
    {
        if (! $employee->isEmployee()) {
            throw new NotFoundHttpException;
        }

        return view($this->role().'.employees.show', $this->performance->fullProfile($employee) + [
            'employeeId' => $employee->id,
            'role' => $this->role(),
            'employees' => User::employees()->active()->orderBy('name')->get(['id', 'name']),

            // Feeds the assign dialog on this page.
            'assignableOrders' => $this->presenter->selectOptions(
                Order::notCancelled()->latest('created_at')->get(['id', 'order_number', 'design_name', 'type']),
            ),
        ]);
    }
}
