<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SessionCategoryResource\Pages;
use App\Filament\Resources\SessionCategoryResource\RelationManagers;
use App\Models\SessionCategory;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SessionCategoryResource extends Resource
{
    protected static ?string $model = SessionCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationGroup = 'Session Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Section::make('Session Category Details')
                            ->description('Manage your session categories here.')
                            ->schema([
                                Forms\Components\TextInput::make('id')
                                    ->hidden(),
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $set('slug', str($state)->slug());
                                    })
                                    ->placeholder('Enter category name')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->unique(SessionCategory::class, 'slug', ignoreRecord: true)
                                    ->placeholder('Enter category slug')
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->required()
                                    ->placeholder('Enter category description')
                                    ->rows(6)
                                    ->columnSpanFull(),
                                Forms\Components\FileUpload::make('image')
                                    ->required()
                                    ->directory('session-categories')
                                    ->imageEditor()
                                    ->image()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Category Settings')
                            ->description('Manage settings for this category.')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')->required(),
                                Forms\Components\ColorPicker::make('color')
                                    ->placeholder('Pick a color'),
                            ])
                            ->columns(1),
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
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\ColorColumn::make('color'),
                Tables\Columns\IconColumn::make('is_active')
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
            'index' => Pages\ListSessionCategories::route('/'),
            'create' => Pages\CreateSessionCategory::route('/create'),
            'edit' => Pages\EditSessionCategory::route('/{record}/edit'),
        ];
    }
}
