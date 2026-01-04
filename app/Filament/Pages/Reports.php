<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;
use BackedEnum;

class Reports extends Page
{
    protected static ?string $title = 'Reports';

    protected static string | UnitEnum | null $navigationGroup = 'HRIS';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-pie';

    protected string $view = 'filament.pages.reports.overview';
}
