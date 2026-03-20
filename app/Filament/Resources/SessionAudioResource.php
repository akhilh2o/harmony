<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SessionAudioResource\Pages;
use App\Models\SessionAudio;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SessionAudioResource extends Resource
{
    protected static ?string $model           = SessionAudio::class;
    protected static ?string $navigationIcon  = 'heroicon-o-musical-note';
    protected static ?string $navigationGroup = 'Session Management';
    protected static ?string $navigationLabel = 'Session Audios';
    protected static ?int    $navigationSort  = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([

                Section::make('Session Details')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->lazy()
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', str($state)->slug())),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(SessionAudio::class, 'slug', ignoreRecord: true),

                    Forms\Components\Textarea::make('description')
                        ->required()->rows(5)->columnSpanFull(),
                ])->columns(2),

                Section::make('Audio File')->schema([
                    Forms\Components\Select::make('session_category_id')
                        ->relationship('session_category', 'name')
                        ->searchable()->preload()->required(),

                    Forms\Components\TextInput::make('duration')
                        ->label('Duration (MM:SS)')
                        ->placeholder('12:30')
                        ->required()->maxLength(10),

                    Forms\Components\FileUpload::make('file')
                        ->label('Audio File')
                        ->disk('public')                        // ✅ disk set
                        ->directory('session-audios')
                        ->maxSize(51200)
                        ->acceptedFileTypes(['audio/mpeg','audio/mp3','audio/ogg','audio/wav','audio/webm'])
                        ->columnSpanFull()
                        // ✅ create pe required, edit pe optional
                        ->required(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord),
                ])->columns(2),

                Section::make('Thumbnail')->schema([
                    Forms\Components\FileUpload::make('image')
                        ->disk('public')                        // ✅ disk set
                        ->directory('session-images')
                        ->image()->imageEditor()
                        ->maxSize(5120)
                        ->columnSpanFull()
                        // ✅ create pe required, edit pe optional
                        ->required(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord),
                ]),

            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([

                Section::make('Access & Settings')->schema([
                    Forms\Components\Radio::make('is_free')
                        ->label('Access Type')
                        ->options([
                            true  => '🆓  Free — All users',
                            false => '⭐  Premium — Subscribers only',
                        ])
                        ->default(true)
                        ->required(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')->required()->default(true),

                    Forms\Components\ColorPicker::make('color')
                        ->label('Color Tag'),
                ]),

            ])->columnSpan(['lg' => 1]),

        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->disk('public')                            // ✅ disk set
                    ->circular()->size(44)->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()->sortable()->limit(30),
                Tables\Columns\TextColumn::make('session_category.name')
                    ->label('Category')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->formatStateUsing(fn ($state) => $state . ' mins')->sortable(),
                Tables\Columns\IconColumn::make('is_free')
                    ->label('Free')->boolean()->sortable()
                    ->trueIcon('heroicon-o-lock-open')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')->falseColor('warning'),
                Tables\Columns\ColorColumn::make('color')->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_free')
                    ->label('Access')
                    ->trueLabel('Free only')->falseLabel('Premium only'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\SelectFilter::make('session_category_id')
                    ->label('Category')
                    ->relationship('session_category', 'name'),
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
            'index'  => Pages\ListSessionAudio::route('/'),
            'create' => Pages\CreateSessionAudio::route('/create'),
            'edit'   => Pages\EditSessionAudio::route('/{record}/edit'),
        ];
    }
}
