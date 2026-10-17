<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Staff\StaffQuoteController;

class QuoteController extends StaffQuoteController
{
    protected function role(): string
    {
        return 'admin';
    }
}
