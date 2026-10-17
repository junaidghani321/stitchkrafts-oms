<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Staff\StaffEmployeeController;

class EmployeeController extends StaffEmployeeController
{
    protected function role(): string
    {
        return 'admin';
    }
}
