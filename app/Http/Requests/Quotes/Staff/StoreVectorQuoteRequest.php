<?php

namespace App\Http\Requests\Quotes\Staff;

use App\Http\Requests\Orders\Concerns\StaffCustomerRule;
use App\Http\Requests\Quotes\User\StoreVectorQuoteRequest as BaseRequest;

class StoreVectorQuoteRequest extends BaseRequest
{
    use StaffCustomerRule;

    public function rules(): array
    {
        return parent::rules() + $this->customerRule();
    }
}
