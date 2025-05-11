<?php

namespace App\Filament\Pages;

use App\Models\NotificationSettings as NotificationSettingsModel;
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

  // protected static ?string $navigationGroup = 'Settings';

  protected static ?int $navigationSort = 2;

  protected static string $view = 'filament.pages.notification-settings';

  public ?array $data = [];

  public function mount(): void
  {
    // Get user's notification settings
    $settings = Auth::user()
      ->notificationSettings()
      ->get()
      ->groupBy('type')
      ->map(function ($typeSettings) {
        return $typeSettings->pluck('is_enabled', 'channel')->toArray();
      })
      ->toArray();

    // Transform settings to match the form structure
    $formData = [];
    foreach (NotificationSettingsModel::getNotificationTypes() as $type => $label) {
      foreach (NotificationSettingsModel::getNotificationChannels() as $channel => $channelLabel) {
        $formData["{$type}_{$channel}"] = $settings[$type][$channel] ?? true;
      }
    }

    $this->form->fill($formData);
  }

  protected function getNotificationToggles(): array
  {
    $user = auth()->user();
    $toggles = [];
    $types = NotificationSettingsModel::getNotificationTypes();
    $channels = NotificationSettingsModel::getNotificationChannels();

    // Billboard-related notifications (available to all users)
    $this->addToggleGroup($toggles, 'billboard_availability', $channels);

    // Only show maintenance notifications to users who can manage billboards
    if ($user->hasAnyRole(['super_admin', 'admin', 'manager'])) {
      $this->addToggleGroup($toggles, 'billboard_maintenance', $channels);
    }

    // Contract-related notifications (for users who can manage contracts)
    if ($user->hasPermissionTo('view_contract') || $user->hasAnyRole(['super_admin', 'admin', 'manager'])) {
      $this->addToggleGroup($toggles, 'contract_expiry', $channels);
      $this->addToggleGroup($toggles, 'contract_renewal', $channels);
      $this->addToggleGroup($toggles, 'new_contract', $channels);
    }

    // Payment-related notifications (for admin and managers only)
    if ($user->hasAnyRole(['super_admin', 'admin', 'manager'])) {
      $this->addToggleGroup($toggles, 'payment_due', $channels);
      $this->addToggleGroup($toggles, 'payment_overdue', $channels);
    }

    return $toggles;
  }

  protected function addToggleGroup(array &$toggles, string $type, array $channels): void
  {
    $types = NotificationSettingsModel::getNotificationTypes();

    foreach ($channels as $channel => $channelLabel) {
      $toggles[] = Toggle::make("{$type}_{$channel}")
        ->label($types[$type] . ' - ' . $channelLabel)
        ->helperText($this->getHelperText($type, $channel))
        ->default(true);
    }
  }

  protected function getHelperText(string $type, string $channel): string
  {
    return match($type) {
        'billboard_availability' => 'Get notified when billboards become available',
        'billboard_maintenance' => 'Get notified about billboard maintenance schedules',
        'contract_expiry' => 'Get notified when contracts are about to expire',
        'contract_renewal' => 'Get notified when contracts are up for renewal',
        'new_contract' => 'Get notified when new contracts are created',
        'payment_due' => 'Get notified when payments are due',
        'payment_overdue' => 'Get notified when payments are overdue',
        default => '',
      } . " via $channel";
  }

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Section::make('Notification Preferences')
          ->description('Choose which notifications you want to receive and how')
          ->schema([
            Grid::make(2)
              ->schema($this->getNotificationToggles()),
          ]),
      ]);
  }

  public function submit(): void
  {
    $data = $this->form->getState();
    $user = auth()->user();

    // Transform form data back to notification settings structure
    foreach ($data as $key => $isEnabled) {
      [$type, $channel] = explode('_', $key, 2);

      $user->notificationSettings()->updateOrCreate(
        [
          'type' => $type,
          'channel' => $channel,
        ],
        [
          'is_enabled' => $isEnabled,
        ]
      );
    }

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

  public static function getNavigationLabel(): string
  {
    return 'Notifications';
  }

  public static function getNavigationGroup(): ?string
  {
    return 'System';
  }

  public function getTitle(): string
  {
    return 'Notification Preferences';
  }
}
