<?php

namespace App\Http\Requests\Orders\Staff;

use App\Http\Requests\Orders\Concerns\StaffCustomerRule;
use App\Http\Requests\Orders\User\StoreDigitizingOrderRequest as BaseRequest;

class StoreDigitizingOrderRequest extends BaseRequest
{
    use StaffCustomerRule;

    public function rules(): array
    {
        return parent::rules() + $this->customerRule();
    }
}
