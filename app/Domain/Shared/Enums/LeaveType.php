<?php

namespace App\Domain\Shared\Enums;

enum LeaveType: string
{
    case Annual = 'annual';
    case Sick = 'sick';
    case Unpaid = 'unpaid';
    case Other = 'other';
}
