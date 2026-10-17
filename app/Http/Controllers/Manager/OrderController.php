<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Staff\StaffOrderController;

class OrderController extends StaffOrderController
{
    protected function role(): string
    {
        return 'manager';
    }
}
