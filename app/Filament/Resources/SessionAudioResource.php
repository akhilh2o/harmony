<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SessionAudioResource\Pages;
use App\Filament\Resources\SessionAudioResource\RelationManagers;
use App\Models\SessionAudio;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SessionAudioResource extends Resource
{
    protected static ?string $model = SessionAudio::class;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';

    protected static ?string $navigationGroup = 'Session Management';

    protected static ?string $navigationLabel = 'Session Audios';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Section::make('Session Audio Details')
                            ->description('Manage your session audio here.')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $set('slug', str($state)->slug());
                                    })
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->required()
                                    ->rows(6)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                        Section::make('Session Audio File')
                            ->description('Manage your session audio here.')
                            ->schema([
                                Forms\Components\Select::make('session_category_id')
                                    ->relationship('session_category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\TextInput::make('duration')
                                    ->required()
                                    ->label('Audio Duration')
                                    ->placeholder('e.g., 3:45')
                                    ->postfix('Mins')
                                    ->maxLength(255),
                                Forms\Components\FileUpload::make('file')
                                    ->maxSize(51200) // 50MB
                                    ->label('Audio File')
                                    ->columnSpanFull()
                                    ->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'audio/ogg', 'audio/wav', 'audio/webm']),
                            ])
                            ->columns(2),
                        Section::make('Session Audio Thumbnail')
                            ->description('Manage your session audio thumbnail here.')
                            ->schema([
                                Forms\Components\FileUpload::make('image')
                                    ->required()
                                    ->columnSpanFull()
                                    ->imageEditor()
                                    ->image(),

                            ])->columns(2)
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Section::make('Session Audio Settings')
                            ->description('Manage your session audio settings here.')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->required(),
                                Forms\Components\Toggle::make('is_free')
                                    ->label('Free')
                                    ->required(),
                                Forms\Components\ColorPicker::make('color')
                                    ->label('Color')
                                    ->placeholder('Pick a color')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->circular()
                    ->size(50)
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('session_category.name')
                    ->searchable()
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('file')
                    ->label('Audio File')
                    ->searchable(),
                Tables\Columns\TextColumn::make('duration')
                    ->formatStateUsing(function ($state) {
                        return $state . ' Mins';
                    })
                    ->searchable(),
                Tables\Columns\ColorColumn::make('color'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_free')
                    ->label('Free')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSessionAudio::route('/'),
            'create' => Pages\CreateSessionAudio::route('/create'),
            'edit' => Pages\EditSessionAudio::route('/{record}/edit'),
        ];
    }
}
