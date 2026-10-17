<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Staff\StaffQuoteController;

class QuoteController extends StaffQuoteController
{
    protected function role(): string
    {
        return 'manager';
    }
}
