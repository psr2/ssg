<?php

namespace Modules\Warehouse\Enums;

enum PaymentMethod: string
{
    case CASH   = 'cash';
    case UPI    = 'upi';
    case BANK   = 'bank';
    case OTHER  = 'other';

    case DEFAULT = 'cash';
}
