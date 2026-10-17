<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Staff\StaffEmployeeController;

class EmployeeController extends StaffEmployeeController
{
    protected function role(): string
    {
        return 'manager';
    }
}
