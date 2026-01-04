<?php

namespace App\Filament\Employee\Resources\MyLeaveResource\Pages;

use App\Filament\Employee\Resources\MyLeaveResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMyLeave extends CreateRecord
{
    protected static string $resource = MyLeaveResource::class;
}
