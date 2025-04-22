<?php

namespace App\Filament\Components;

use Filament\Tables\Table;

class CustomPagination
{
    public static function apply(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 10, 25, 50]);
    }
}
