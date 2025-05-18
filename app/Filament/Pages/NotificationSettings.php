<?php

namespace App\Filament\Pages;

use App\Models\NotificationSettings as NotificationSettingsModel;
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
  protected static ?string $navigationGroup = 'System';
  protected static ?int $navigationSort = 4; // After Notifications page
  protected static string $view = 'filament.pages.notification-settings';

  public ?array $data = [];

  public function mount(): void
  {
    $this->fillForm();
  }

  protected function fillForm(): void
  {
    // Get user's current notification settings
    $settings = Auth::user()
      ->notificationSettings()
      ->get()
      ->groupBy('type')
      ->map(fn ($typeSettings) => $typeSettings->pluck('is_enabled', 'channel')->toArray())
      ->toArray();

    // Initialize form data with default values
    $formData = [];
    foreach ($this->getNotificationCategories() as $category => $types) {
      foreach ($types as $type => $config) {
        foreach (NotificationSettingsModel::getNotificationChannels() as $channel => $channelLabel) {
          $formData["{$type}_{$channel}"] = $settings[$type][$channel] ?? true;
        }
      }
    }

    $this->form->fill($formData);
  }

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Grid::make()
          ->columns(1)
          ->schema($this->buildFormSchema())
      ]);
  }

  protected function getNotificationCategories(): array
  {
    return [
      'Contracts' => [
        'contract_expiry' => [
          'label' => 'Contract Expiration',
          'description' => 'Notifications about contracts nearing their end date',
          'icon' => 'heroicon-o-clock',
          'roles' => ['super_admin', 'admin', 'manager', 'client'],
        ],
        'contract_renewal' => [
          'label' => 'Contract Renewal',
          'description' => 'Notifications when contracts are ready for renewal',
          'icon' => 'heroicon-o-arrow-path',
          'roles' => ['super_admin', 'admin', 'manager'],
        ],
        'new_contract' => [
          'label' => 'New Contracts',
          'description' => 'Notifications when new contracts are created',
          'icon' => 'heroicon-o-document-plus',
          'roles' => ['super_admin', 'admin', 'manager'],
        ],
      ],
      'Billboards' => [
        'billboard_availability' => [
          'label' => 'Billboard Availability',
          'description' => 'Notifications when billboards become available',
          'icon' => 'heroicon-o-eye',
          'roles' => ['*'], // Available to all users
        ],
        'billboard_maintenance' => [
          'label' => 'Billboard Maintenance',
          'description' => 'Updates about billboard maintenance schedules',
          'icon' => 'heroicon-o-wrench-screwdriver',
          'roles' => ['super_admin', 'admin', 'manager'],
        ],
      ],
      'Payments' => [
        'payment_due' => [
          'label' => 'Payment Due',
          'description' => 'Reminders about upcoming payment deadlines',
          'icon' => 'heroicon-o-currency-dollar',
          'roles' => ['super_admin', 'admin', 'manager', 'client'],
        ],
        'payment_overdue' => [
          'label' => 'Payment Overdue',
          'description' => 'Alerts about overdue payments',
          'icon' => 'heroicon-o-exclamation-triangle',
          'roles' => ['super_admin', 'admin', 'manager', 'client'],
        ],
      ],
    ];
  }

  protected function buildFormSchema(): array
  {
    $schema = [];
    $user = auth()->user();
    $channels = NotificationSettingsModel::getNotificationChannels();

    foreach ($this->getNotificationCategories() as $category => $types) {
      $categorySchema = [];

      foreach ($types as $type => $config) {
        // Check if user has permission for this notification type
        if (!$this->userCanAccessNotificationType($user, $config['roles'])) {
          continue;
        }

        $toggles = [];
        foreach ($channels as $channel => $channelLabel) {
          $toggles[] = Toggle::make("{$type}_{$channel}")
            ->label($channelLabel)
            ->helperText($this->getChannelHelperText($channel))
            ->inline()
            ->default(true);
        }

        $categorySchema[] = Section::make($config['label'])
          ->description($config['description'])
          ->icon($config['icon'])
          ->compact()
          ->columns(count($channels))
          ->schema($toggles);
      }

      if (!empty($categorySchema)) {
        $schema[] = Section::make($category)
          ->description("Manage your {$category} related notifications")
          ->collapsible()
          ->schema($categorySchema);
      }
    }

    return $schema;
  }

  protected function userCanAccessNotificationType($user, array $roles): bool
  {
    if (in_array('*', $roles)) {
      return true;
    }

    return $user->hasAnyRole($roles);
  }

  protected function getChannelHelperText(string $channel): string
  {
    return match($channel) {
      'email' => 'Receive notifications via email',
      'database' => 'See notifications in the app',
      'broadcast' => 'Get real-time push notifications',
      default => '',
    };
  }

  public function submit(): void
  {
    try {
      $data = $this->form->getState();
      $user = auth()->user();

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
        ->title('Notification preferences updated')
        ->success()
        ->send();

    } catch (\Exception $e) {
      Notification::make()
        ->title('Error updating preferences')
        ->danger()
        ->body('Please try again or contact support if the problem persists.')
        ->send();

      \Log::error('Error updating notification settings', [
        'user_id' => auth()->id(),
        'error' => $e->getMessage(),
      ]);
    }
  }

  public static function shouldRegister(): bool
  {
    return auth()->check();
  }

  public function getTitle(): string
  {
    return 'Notification Preferences';
  }
}
