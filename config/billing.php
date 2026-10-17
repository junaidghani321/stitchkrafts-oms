<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | ISO code used on every invoice and payment. The UI renders a bare "$",
    | so changing this alone will not relabel the screens.
    |
    */

    'currency' => env('BILLING_CURRENCY', 'usd'),

    /*
    |--------------------------------------------------------------------------
    | Payment terms
    |--------------------------------------------------------------------------
    |
    | Days from issue to due date on a generated invoice.
    |
    */

    'due_days' => (int) env('BILLING_DUE_DAYS', 14),

];
