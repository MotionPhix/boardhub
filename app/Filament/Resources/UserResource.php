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
use Illuminate\Support\Str;

class UserResource extends Resource
{
  protected static ?string $model = User::class;

  protected static ?string $navigationIcon = 'heroicon-o-users';

  protected static ?string $navigationGroup = 'Management';

  protected static ?int $navigationSort = 1;

  protected static function getRoleLabel(string $role): string
  {
    return match ($role) {
      'super_admin' => 'Super Admin',
      'admin' => 'Administrator',
      'manager' => 'Manager',
      'agent' => 'Agent',
      'viewer' => 'Viewer',
      default => Str::title(str_replace('_', ' ', $role)),
    };
  }

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

            Forms\Components\Select::make('roles')
              ->multiple()
              ->relationship(
                'roles',
                'name',
                fn ($query) => $query->orderBy('name')
              )
              ->options(function () {
                return \Spatie\Permission\Models\Role::query()
                  ->orderBy('name')
                  ->pluck('name', 'name')
                  ->mapWithKeys(function ($role) {
                    return [$role => static::getRoleLabel($role)];
                  });
              })
              ->preload()
              ->searchable()
              ->helperText('Assign roles to determine user permissions.')
              ->required(),

            Forms\Components\Toggle::make('is_active')
              ->label('Active Status')
              ->helperText('Deactivating a user will prevent them from logging in.')
              ->default(true),
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
          ->formatStateUsing(fn (string $state): string => static::getRoleLabel($state))
          ->colors([
            'danger' => 'super_admin',
            'warning' => 'admin',
            'success' => 'manager',
            'info' => 'agent',
            'gray' => 'viewer',
          ])
          ->searchable()
          ->sortable(),

        TextColumn::make('is_active')
          ->label('Status')
          ->formatStateUsing(fn (string $state): string => $state == 1 ? 'Active' : 'Inactive')
          ->sortable(),
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
          ->preload()
          ->options(function () {
            return \Spatie\Permission\Models\Role::query()
              ->orderBy('name')
              ->pluck('name', 'name')
              ->mapWithKeys(function ($role) {
                return [$role => static::getRoleLabel($role)];
              });
          }),
        Tables\Filters\Filter::make('active')
          ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
        Tables\Filters\Filter::make('inactive')
          ->query(fn (Builder $query): Builder => $query->where('is_active', false)),
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make(),
        Tables\Actions\Action::make('impersonate')
          ->icon('heroicon-m-user')
          ->requiresConfirmation()
          ->hidden(fn (User $record) =>
            $record->id === auth()->id() ||
            !auth()->user()->hasRole('super_admin')
          )
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
