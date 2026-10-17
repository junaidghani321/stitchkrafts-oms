<?php

namespace App\Http\Requests\Orders\Staff;

use App\Http\Requests\Orders\Concerns\StaffCustomerRule;
use App\Http\Requests\Orders\User\StorePatchOrderRequest as BaseRequest;

class StorePatchOrderRequest extends BaseRequest
{
    use StaffCustomerRule;

    public function rules(): array
    {
        return parent::rules() + $this->customerRule();
    }
}
