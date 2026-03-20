<?php
namespace App\Filament\Resources\PlaylistResource\Pages;
use App\Filament\Resources\PlaylistResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePlaylist extends CreateRecord
{
    protected static string $resource = PlaylistResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // $data['user_id'] = auth()->id();
        $data['user_id'] = Auth::id();
        return $data;
    }
}
