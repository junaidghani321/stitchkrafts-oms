<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Staff\StaffRevisionController;

class RevisionController extends StaffRevisionController
{
    protected function role(): string
    {
        return 'admin';
    }
}
