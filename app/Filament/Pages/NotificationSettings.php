<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class NotificationSettings extends Page
{
  use InteractsWithForms;

  protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

  protected static ?string $navigationGroup = 'Settings';

  protected static ?int $navigationSort = 2;

  protected static string $view = 'filament.pages.notification-settings';

  public ?array $data = [];

  public function mount(): void
  {
    $this->form->fill([
      'contract_expiry' => auth()->user()->notification_preferences['contract_expiry'] ?? true,
      'contract_renewal' => auth()->user()->notification_preferences['contract_renewal'] ?? true,
      'new_contract' => auth()->user()->notification_preferences['new_contract'] ?? true,
      'payment_due' => auth()->user()->notification_preferences['payment_due'] ?? true,
      'payment_overdue' => auth()->user()->notification_preferences['payment_overdue'] ?? true,
      'billboard_maintenance' => auth()->user()->notification_preferences['billboard_maintenance'] ?? true,
      'billboard_availability' => auth()->user()->notification_preferences['billboard_availability'] ?? true,
      'email_notifications' => auth()->user()->notification_preferences['email_notifications'] ?? true,
      'in_app_notifications' => auth()->user()->notification_preferences['in_app_notifications'] ?? true,
      'push_notifications' => auth()->user()->notification_preferences['push_notifications'] ?? true,
    ]);
  }

  protected function getNotificationToggles(): array
  {
    $user = auth()->user();
    $toggles = [];

    // Billboard-related notifications (available to all users)
    $toggles[] = Toggle::make('billboard_availability')
      ->label('Billboard Availability')
      ->helperText('Get notified when billboards become available')
      ->default(true);

    // Only show maintenance notifications to users who can manage billboards
    if ($user->hasAnyRole(['super_admin', 'admin', 'manager'])) {
      $toggles[] = Toggle::make('billboard_maintenance')
        ->label('Billboard Maintenance')
        ->helperText('Get notified about billboard maintenance schedules')
        ->default(true);
    }

    // Contract-related notifications (for users who can manage contracts)
    if ($user->hasPermissionTo('view_contract') || $user->hasAnyRole(['super_admin', 'admin', 'manager'])) {
      $toggles[] = Toggle::make('contract_expiry')
        ->label('Contract Expiry')
        ->helperText('Get notified when contracts are about to expire')
        ->default(true);

      $toggles[] = Toggle::make('contract_renewal')
        ->label('Contract Renewal')
        ->helperText('Get notified when contracts are up for renewal')
        ->default(true);

      $toggles[] = Toggle::make('new_contract')
        ->label('New Contracts')
        ->helperText('Get notified when new contracts are created')
        ->default(true);
    }

    // Payment-related notifications (for admin and managers only)
    if ($user->hasAnyRole(['super_admin', 'admin', 'manager'])) {
      $toggles[] = Toggle::make('payment_due')
        ->label('Payment Due')
        ->helperText('Get notified when payments are due')
        ->default(true);

      $toggles[] = Toggle::make('payment_overdue')
        ->label('Payment Overdue')
        ->helperText('Get notified when payments are overdue')
        ->default(true);
    }

    return $toggles;
  }

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Section::make('Notification Types')
          ->description('Choose which types of notifications you want to receive')
          ->schema([
            Grid::make(2)
              ->schema($this->getNotificationToggles()),
          ]),

        Section::make('Notification Channels')
          ->description('Choose how you want to receive notifications')
          ->schema([
            Grid::make(3)
              ->schema([
                Toggle::make('email_notifications')
                  ->label('Email Notifications')
                  ->helperText('Receive notifications via email')
                  ->default(true),

                Toggle::make('in_app_notifications')
                  ->label('In-App Notifications')
                  ->helperText('Receive notifications within the application')
                  ->default(true),

                Toggle::make('push_notifications')
                  ->label('Push Notifications')
                  ->helperText('Receive push notifications in your browser')
                  ->default(true),
              ]),
          ]),
      ]);
  }

  public function submit(): void
  {
    $data = $this->form->getState();

    $user = auth()->user();

    $user->update([
      'notification_preferences' => $data
    ]);

    Notification::make()
      ->title('Notification settings updated successfully')
      ->success()
      ->send();
  }

  public static function shouldRegister(): bool
  {
    return auth()->check() &&
      auth()->user()->can('manage_notification_settings');
  }

  protected function getHeaderActions(): array
  {
    return [];
  }

  public static function getNavigationLabel(): string
  {
    return 'Notification Settings';
  }

  public static function getNavigationGroup(): ?string
  {
    return 'Settings';
  }

  public function getTitle(): string
  {
    return 'Notification Preferences';
  }
}
