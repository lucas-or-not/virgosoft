<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Trade;

interface MatchingServiceInterface
{
    public function matchOrder(Order $order): ?Trade;
}

