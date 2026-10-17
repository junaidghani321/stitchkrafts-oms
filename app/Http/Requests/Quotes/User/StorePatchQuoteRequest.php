<?php

namespace App\Http\Requests\Quotes\User;

use App\Http\Requests\Orders\User\StorePatchOrderRequest;

/**
 * A quote asks for the same spec as an order — the two forms are
 * field-for-field identical — so the rules are inherited outright rather than
 * copied. If an order field changes, the quote follows automatically.
 */
class StorePatchQuoteRequest extends StorePatchOrderRequest {}
