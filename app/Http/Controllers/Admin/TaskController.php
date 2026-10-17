<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Staff\StaffTaskController;

class TaskController extends StaffTaskController
{
    protected function role(): string
    {
        return 'admin';
    }
}
