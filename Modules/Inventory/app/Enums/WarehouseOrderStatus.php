<?php

namespace Modules\Inventory\Enums;

enum WarehouseOrderStatus: string
{
    case Pending = 'Pending';
    case Confirmed = 'Confirmed';
    case Delivered = 'Delivered';
    case Accepted = 'Accepted';
    case Delivery = 'Delivery';
    case Finished = 'Finished';
    case Rejected = 'Rejected';
    case Revision = 'Revision';
}
