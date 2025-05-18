<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class Notifications extends Page implements HasTable
{
  use InteractsWithTable;

  protected static ?string $navigationIcon = 'heroicon-o-bell';

  protected static ?string $navigationGroup = 'System';

  protected static ?int $navigationSort = 3;

  protected static string $view = 'filament.pages.notifications';

  public function table(Table $table): Table
  {
    return $table
      ->query(
        DatabaseNotification::query()
          ->where('notifiable_id', auth()->id())
          ->where('notifiable_type', auth()->user()::class)
      )
      ->columns([
        IconColumn::make('read_at')
          ->label('')
          ->boolean()
          ->trueIcon('heroicon-o-check-circle')
          ->falseIcon('heroicon-o-bell-alert')
          ->trueColor('success')
          ->falseColor('warning')
          ->sortable(),

        TextColumn::make('data.message')
          ->label('Message')
          ->searchable()
          ->wrap(),

        TextColumn::make('data.contract_number')
          ->label('Contract')
          ->searchable()
          ->sortable(),

        TextColumn::make('created_at')
          ->label('Received')
          ->dateTime()
          ->sortable(),

        IconColumn::make('data.icon')
          ->label('')
          ->icon(fn ($record) => $record->data['icon'] ?? 'heroicon-o-information-circle')
          ->color(fn ($record) => $record->data['color'] ?? 'primary'),
      ])
      ->defaultSort('created_at', 'desc')
      ->actions([
        Action::make('mark_as_read')
          ->label('Mark as Read')
          ->icon('heroicon-o-check')
          ->action(fn (DatabaseNotification $record) => $record->markAsRead())
          ->visible(fn (DatabaseNotification $record) => $record->read_at === null),

        Action::make('view_details')
          ->label('View')
          ->icon('heroicon-o-eye')
          ->url(fn (DatabaseNotification $record) =>
          $record->data['contract_id']
            ? route('filament.admin.resources.contracts.view', ['record' => $record->data['contract_id']])
            : null
          )
          ->visible(fn (DatabaseNotification $record) => isset($record->data['contract_id'])),
      ])
      ->bulkActions([
        \Filament\Tables\Actions\BulkAction::make('mark_as_read')
          ->label('Mark as Read')
          ->icon('heroicon-o-check')
          ->action(fn ($records) => $records->each->markAsRead())
          ->deselectRecordsAfterCompletion()
          ->requiresConfirmation(false),

        \Filament\Tables\Actions\BulkAction::make('delete')
          ->label('Delete')
          ->icon('heroicon-o-trash')
          ->action(fn ($records) => $records->each->delete())
          ->deselectRecordsAfterCompletion()
          ->color('danger')
          ->requiresConfirmation(),
      ])
      ->filters([
        \Filament\Tables\Filters\SelectFilter::make('read')
          ->options([
            'read' => 'Read',
            'unread' => 'Unread',
          ])
          ->query(function (Builder $query, array $data) {
            if ($data['value'] === 'read') {
              $query->whereNotNull('read_at');
            } elseif ($data['value'] === 'unread') {
              $query->whereNull('read_at');
            }
          }),

        \Filament\Tables\Filters\SelectFilter::make('type')
          ->options(function () {
            return DatabaseNotification::query()
              ->where('notifiable_id', auth()->id())
              ->get()
              ->pluck('data.type')
              ->unique()
              ->mapWithKeys(fn ($type) => [
                $type => Str::title(str_replace('_', ' ', $type))
              ])
              ->toArray();
          }),
      ]);
  }

  public static function getNavigationLabel(): string
  {
    $count = DatabaseNotification::query()
      ->where('notifiable_id', auth()->id())
      ->whereNull('read_at')
      ->count();

    return $count > 0
      ? "Notifications ({$count})"
      : 'Notifications';
  }

  public function getTitle(): string
  {
    return 'My Notifications';
  }

  public static function shouldRegister(): bool
  {
    return auth()->check();
  }
}
