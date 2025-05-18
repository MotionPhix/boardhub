<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ViewColumn;
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
          ->width('10%')
          ->trueIcon('heroicon-o-check-circle')
          ->falseIcon('heroicon-o-bell-alert')
          ->trueColor('success')
          ->falseColor('warning')
          ->sortable(),

        ViewColumn::make('notification_content')
          ->label('Notification')
          ->view('filament.tables.columns.notification-content')
          ->width('50%')
          ->searchable(query: function (Builder $query, string $search): Builder {
            return $query->where('data->message', 'like', "%{$search}%")
              ->orWhere('data->title', 'like', "%{$search}%");
          }),

        TextColumn::make('created_at')
          ->label('Received')
          ->dateTime()
          ->width('10%')
          ->sortable(),

        IconColumn::make('data.icon')
          ->label('')
          ->width('10%')
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
          ->url(fn (DatabaseNotification $record) => $this->getNotificationUrl($record))
          ->visible(fn (DatabaseNotification $record) => $this->hasDetailsPage($record)),
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
            $types = DatabaseNotification::query()
              ->where('notifiable_id', auth()->id())
              ->get()
              ->map(function ($notification) {
                return $notification->data['type'] ?? 'other';
              })
              ->unique()
              ->filter()
              ->mapWithKeys(function ($type) {
                return [$type => $this->formatNotificationType($type)];
              })
              ->toArray();

            // Add "Other" option if we have notifications without type
            if (DatabaseNotification::query()
              ->where('notifiable_id', auth()->id())
              ->whereNull('data->type')
              ->exists()
            ) {
              $types['other'] = 'Other';
            }

            return $types;
          })
          ->query(function (Builder $query, array $data) {
            if ($data['value'] === 'other') {
              $query->whereNull('data->type');
            } elseif ($data['value']) {
              $query->where('data->type', $data['value']);
            }
          }),
      ]);
  }

  protected function getNotificationUrl(DatabaseNotification $notification): ?string
  {
    return match ($notification->data['type'] ?? null) {
      'contract_expiring', 'contract_renewal', 'new_contract' =>
      isset($notification->data['contract_id'])
        ? route('filament.admin.resources.contracts.view', ['record' => $notification->data['contract_id']])
        : null,
      'billboard_maintenance', 'billboard_availability' =>
      isset($notification->data['billboard_id'])
        ? route('filament.admin.resources.billboards.view', ['record' => $notification->data['billboard_id']])
        : null,
      'payment_due', 'payment_overdue' =>
      isset($notification->data['payment_id'])
        ? route('filament.admin.resources.payments.view', ['record' => $notification->data['payment_id']])
        : null,
      default => null,
    };
  }

  protected function hasDetailsPage(DatabaseNotification $notification): bool
  {
    return match ($notification->data['type'] ?? null) {
      'contract_expiring', 'contract_renewal', 'new_contract' => isset($notification->data['contract_id']),
      'billboard_maintenance', 'billboard_availability' => isset($notification->data['billboard_id']),
      'payment_due', 'payment_overdue' => isset($notification->data['payment_id']),
      default => false,
    };
  }

  protected function formatNotificationType(?string $type): string
  {
    if ($type === null) {
      return 'Other';
    }

    return match ($type) {
      'contract_expiring' => 'Contract Expiration',
      'contract_renewal' => 'Contract Renewal',
      'new_contract' => 'New Contract',
      'payment_due' => 'Payment Due',
      'payment_overdue' => 'Payment Overdue',
      'billboard_maintenance' => 'Billboard Maintenance',
      'billboard_availability' => 'Billboard Availability',
      'other' => 'Other',
      default => Str::title(str_replace('_', ' ', $type)),
    };
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
