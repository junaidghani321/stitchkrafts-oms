<?php

namespace App\Http\Requests\Quotes\Staff;

use App\Http\Requests\Orders\Concerns\StaffCustomerRule;
use App\Http\Requests\Quotes\User\StoreDigitizingQuoteRequest as BaseRequest;

class StoreDigitizingQuoteRequest extends BaseRequest
{
    use StaffCustomerRule;

    public function rules(): array
    {
        return parent::rules() + $this->customerRule();
    }
}
