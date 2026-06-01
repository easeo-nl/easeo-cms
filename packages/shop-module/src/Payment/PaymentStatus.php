<?php
declare(strict_types=1);

namespace Easeo\Shop\Payment;

enum PaymentStatus: string
{
    case Pending   = 'pending';
    case Paid      = 'paid';
    case Failed    = 'failed';
    case Expired   = 'expired';
    case Refunded  = 'refunded';
    case Cancelled = 'cancelled';
}
