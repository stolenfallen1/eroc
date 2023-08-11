<?php

namespace App\Helpers\PosSearchFilter;

use Illuminate\Support\Facades\Auth;
use App\Models\POS\Customers;

class FloatConverter
{
    public function value($val)
    {
        return (float)$val;
    }
}