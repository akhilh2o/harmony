<?php

namespace App\Filament\Resources\SessionCategoryResource\Pages;

use App\Filament\Resources\SessionCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSessionCategories extends ListRecords
{
    protected static string $resource = SessionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
