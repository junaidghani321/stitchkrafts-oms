<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Staff\StaffOrderController;

class OrderController extends StaffOrderController
{
    protected function role(): string
    {
        return 'admin';
    }
}
