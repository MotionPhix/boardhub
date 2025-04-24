<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\RelationManagers\LoginActivitiesRelationManager;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class UserResource extends Resource
{
  protected static ?string $model = User::class;

  protected static ?string $navigationIcon = 'heroicon-o-users';

  protected static ?string $navigationGroup = 'Settings';

  protected static ?int $navigationSort = 1;

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('User Details')
          ->description('Manage user account information.')
          ->schema([
            Forms\Components\TextInput::make('name')
              ->required()
              ->maxLength(255),

            Forms\Components\TextInput::make('email')
              ->email()
              ->required()
              ->maxLength(255)
              ->unique(ignoreRecord: true),

            Forms\Components\DateTimePicker::make('email_verified_at')
              ->native(false)
              ->displayFormat('M d, Y H:i:s')
              ->label('Email Verified At'),

            Forms\Components\TextInput::make('password')
              ->password()
              ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
              ->dehydrated(fn (?string $state): bool => filled($state))
              ->required(fn (string $operation): bool => $operation === 'create'),

            Forms\Components\Toggle::make('is_admin')
              ->label('Administrator')
              ->helperText('Grant administrative privileges to this user.')
              ->default(false),

            Forms\Components\Select::make('roles')
              ->multiple()
              ->relationship('roles', 'name')
              ->preload(),
          ])
          ->columns(2),

        Forms\Components\Section::make('Profile Settings')
          ->schema([
            Forms\Components\FileUpload::make('avatar')
              ->image()
              ->disk('public')
              ->directory('avatars')
              ->imageEditor()
              ->circleCropper()
              ->maxSize(5120)
              ->columnSpanFull(),

            Forms\Components\TextInput::make('phone')
              ->tel()
              ->maxLength(20),

            Forms\Components\Textarea::make('bio')
              ->maxLength(500)
              ->columnSpanFull(),
          ])
          ->collapsible(),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\ImageColumn::make('avatar')
          ->circular()
          ->size(40),

        TextColumn::make('name')
          ->searchable()
          ->sortable(),

        TextColumn::make('email')
          ->searchable()
          ->sortable(),

        IconColumn::make('email_verified_at')
          ->label('Verified')
          ->boolean()
          ->sortable()
          ->toggleable(),

        TextColumn::make('roles.name')
          ->badge()
          ->searchable()
          ->sortable(),

        IconColumn::make('is_admin')
          ->label('Admin')
          ->boolean()
          ->sortable()
          ->toggleable(),

        TextColumn::make('created_at')
          ->dateTime('M d, Y')
          ->sortable()
          ->toggleable(),

        TextColumn::make('updated_at')
          ->dateTime('M d, Y')
          ->sortable()
          ->toggleable()
          ->toggledHiddenByDefault(),
      ])
      ->filters([
        Tables\Filters\TrashedFilter::make(),
        Tables\Filters\Filter::make('verified')
          ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at')),
        Tables\Filters\Filter::make('unverified')
          ->query(fn (Builder $query): Builder => $query->whereNull('email_verified_at')),
        Tables\Filters\SelectFilter::make('roles')
          ->relationship('roles', 'name')
          ->multiple()
          ->preload(),
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make(),
        Tables\Actions\Action::make('impersonate')
          ->icon('heroicon-m-user')
          ->requiresConfirmation()
          ->hidden(fn (User $record) => $record->id === auth()->id())
          ->action(function (User $record) {
            auth()->user()->impersonate($record);
            return redirect()->route('filament.admin.pages.dashboard');
          }),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
          Tables\Actions\ForceDeleteBulkAction::make(),
          Tables\Actions\RestoreBulkAction::make(),
        ]),
      ])
      ->emptyStateActions([
        Tables\Actions\CreateAction::make(),
      ]);
  }

  public static function getRelations(): array
  {
    return [
      LoginActivitiesRelationManager::class
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListUsers::route('/'),
      'create' => Pages\CreateUser::route('/create'),
      'edit' => Pages\EditUser::route('/{record}/edit'),
      'view' => Pages\ViewUser::route('/{record}'),
    ];
  }

  public static function getEloquentQuery(): Builder
  {
    return parent::getEloquentQuery()
      ->withoutGlobalScopes([
        SoftDeletingScope::class,
      ]);
  }

  public static function getNavigationBadge(): ?string
  {
    return static::getModel()::count();
  }
}
