<?php

namespace App\Filament\Employee\Resources\MyPayslipResource\Pages;

use App\Filament\Employee\Resources\MyPayslipResource;
use Filament\Resources\Pages\ListRecords;

class ListMyPayslips extends ListRecords
{
    protected static string $resource = MyPayslipResource::class;
}
