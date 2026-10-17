<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Staff\StaffRevisionController;

class RevisionController extends StaffRevisionController
{
    protected function role(): string
    {
        return 'manager';
    }
}
