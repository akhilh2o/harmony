<?php

namespace App\Filament\Resources\SessionCategoryResource\Pages;

use App\Filament\Resources\SessionCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSessionCategory extends EditRecord
{
    protected static string $resource = SessionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
