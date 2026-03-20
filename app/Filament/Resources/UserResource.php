<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon  = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Users Management';
    protected static ?int    $navigationSort  = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([

                Section::make('Basic Information')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->email()->required()->maxLength(255)
                        ->unique(User::class, 'email', ignoreRecord: true),
                    Forms\Components\TextInput::make('phone_code')
                        ->label('Phone Code')->tel()->maxLength(10),
                    Forms\Components\TextInput::make('phone')
                        ->tel()->maxLength(20)
                        ->unique(User::class, 'phone', ignoreRecord: true),
                    Forms\Components\TextInput::make('password')
                        ->password()->maxLength(255)
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->label('Password (leave blank to keep current)'),
                    Forms\Components\FileUpload::make('avatar')
                        ->image()->directory('avatars')->imageEditor(),
                ])->columns(2),

            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([

                Section::make('Account Settings')->schema([
                    Forms\Components\Toggle::make('is_admin')
                        ->label('Admin')->required(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')->required()->default(true),
                ]),

                Section::make('Subscription')->schema([
                    Forms\Components\Toggle::make('is_subscribed')
                        ->label('Subscribed')->reactive(),
                    Forms\Components\Select::make('subscription_plan')
                        ->options(['monthly' => 'Monthly', 'yearly' => 'Yearly'])
                        ->visible(fn ($get) => $get('is_subscribed')),
                    Forms\Components\DateTimePicker::make('subscription_expires_at')
                        ->label('Expires At')
                        ->visible(fn ($get) => $get('is_subscribed')),
                ]),

                Section::make('Verification')->schema([
                    Forms\Components\DateTimePicker::make('email_verified_at')
                        ->label('Email Verified'),
                    Forms\Components\DateTimePicker::make('phone_verified_at')
                        ->label('Phone Verified'),
                ]),

            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular()->size(40)->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&color=c9a84c&background=27221a'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->searchable()->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
                Tables\Columns\IconColumn::make('is_admin')->label('Admin')->boolean()->sortable(),
                Tables\Columns\IconColumn::make('is_subscribed')->label('Pro')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('subscription_plan')
                    ->label('Plan')->badge()
                    ->color(fn ($state) => match($state) { 'yearly' => 'success', 'monthly' => 'warning', default => 'gray' })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subscription_expires_at')
                    ->label('Expires')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\TernaryFilter::make('is_admin')->label('Admin'),
                Tables\Filters\TernaryFilter::make('is_subscribed')->label('Subscribed'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view'   => Pages\ViewUser::route('/{record}'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
