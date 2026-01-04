<?php

namespace App\Domain\Shared\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Leave = 'leave';
}
