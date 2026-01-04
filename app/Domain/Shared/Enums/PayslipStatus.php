<?php

namespace App\Domain\Shared\Enums;

enum PayslipStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Paid = 'paid';
}
