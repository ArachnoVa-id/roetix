<?php

namespace App\Filament\NovatixAdmin\Resources\UserResource\Pages;

use App\Filament\NovatixAdmin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
