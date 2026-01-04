<?php

namespace App\Filament\Resources\PayslipResource\Pages;

use App\Filament\Resources\PayslipResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayslip extends CreateRecord
{
    protected static string $resource = PayslipResource::class;

    public function mount(): void
    {
        parent::mount();
    }
}
