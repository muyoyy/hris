<?php

namespace App\Filament\Employee\Resources\MyLeaveResource\Pages;

use App\Filament\Employee\Resources\MyLeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMyLeaves extends ListRecords
{
    protected static string $resource = MyLeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
