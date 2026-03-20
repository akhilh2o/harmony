<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlaylistResource\Pages;
use App\Models\Playlist;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlaylistResource extends Resource
{
    protected static ?string $model           = Playlist::class;
    protected static ?string $navigationIcon  = 'heroicon-o-queue-list';
    protected static ?string $navigationGroup = 'Session Management';
    protected static ?string $navigationLabel = 'Playlists';
    protected static ?int    $navigationSort  = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([

                Section::make('Playlist Details')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->rows(4)->columnSpanFull(),
                ])->columns(2),

                Section::make('Sessions in Playlist')->schema([
                    Forms\Components\Repeater::make('audios')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('session_audio_id')
                                ->label('Session Audio')
                                ->relationship('sessionAudio', 'name')
                                ->searchable()->preload()->required(),
                            Forms\Components\TextInput::make('order')
                                ->numeric()->default(0)->label('Order'),
                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')->default(true)->inline(),
                        ])
                        ->columns(3)
                        ->orderColumn('order')
                        ->defaultItems(0)
                        ->addActionLabel('Add Session'),
                ]),

            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([

                Section::make('Settings')->schema([
                    Forms\Components\Radio::make('is_free')
                        ->label('Access Type')
                        ->options([
                            true  => '🆓  Free',
                            false => '⭐  Premium',
                        ])
                        ->default(true)->required(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')->default(true),
                    Forms\Components\Toggle::make('is_public')
                        ->label('Public')->default(false),
                    Forms\Components\ColorPicker::make('color')
                        ->label('Color'),
                    Forms\Components\FileUpload::make('image')
                        ->label('Cover Image')
                        ->image()->imageEditor()
                        ->directory('playlists'),
                ]),

            ])->columnSpan(['lg' => 1]),

        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->size(44)->rounded()->toggleable(),
                Tables\Columns\ColorColumn::make('color')->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()->sortable()->limit(30),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')->searchable()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('audios_count')
                    ->label('Sessions')
                    ->counts('audios')
                    ->badge()->color('info'),
                Tables\Columns\IconColumn::make('is_free')
                    ->label('Free')->boolean()
                    ->trueIcon('heroicon-o-lock-open')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')->falseColor('warning'),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
                Tables\Columns\IconColumn::make('is_public')->label('Public')->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_free')
                    ->label('Access')
                    ->trueLabel('Free')->falseLabel('Premium'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\TernaryFilter::make('is_public')->label('Public'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPlaylists::route('/'),
            'create' => Pages\CreatePlaylist::route('/create'),
            'edit'   => Pages\EditPlaylist::route('/{record}/edit'),
        ];
    }
}
