<?php

namespace App\Filament\Resources\Attendances\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->relationship('employee', 'id')
                    ->required(),
                DatePicker::make('work_date')
                    ->required(),
                DateTimePicker::make('check_in_at'),
                DateTimePicker::make('check_out_at'),
                TextInput::make('status')
                    ->required()
                    ->default('HADIR'),
                Textarea::make('note')
                    ->columnSpanFull(),
            ]);
    }
}
