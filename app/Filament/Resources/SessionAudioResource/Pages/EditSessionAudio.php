<?php

namespace App\Filament\Resources\SessionAudioResource\Pages;

use App\Filament\Resources\SessionAudioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSessionAudio extends EditRecord
{
    protected static string $resource = SessionAudioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
