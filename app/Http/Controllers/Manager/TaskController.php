<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Staff\StaffTaskController;

class TaskController extends StaffTaskController
{
    protected function role(): string
    {
        return 'manager';
    }
}
