<?php

namespace App\Filament\Resources\SessionAudioResource\Pages;

use App\Filament\Resources\SessionAudioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSessionAudio extends ListRecords
{
    protected static string $resource = SessionAudioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
