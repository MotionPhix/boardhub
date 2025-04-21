<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class NotificationSettings extends Page
{
  protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
  protected static ?string $navigationGroup = 'Settings';
  protected static ?string $title = 'Notification Preferences';

  public ?array $data = [];

  public function mount(): void
  {
    $settings = Auth::user()->notificationSettings;
    $this->form->fill($settings ? $settings->toArray() : [
      'email_notifications' => true,
      'database_notifications' => true,
      'notification_thresholds' => [30, 14, 7, 3, 1],
    ]);
  }

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('Contract Expiration Notifications')
          ->description('Configure how you want to be notified about expiring contracts')
          ->schema([
            Forms\Components\Toggle::make('email_notifications')
              ->label('Email Notifications')
              ->helperText('Receive notifications via email'),

            Forms\Components\Toggle::make('database_notifications')
              ->label('In-App Notifications')
              ->helperText('Receive notifications within the application'),

            Forms\Components\CheckboxList::make('notification_thresholds')
              ->label('Notification Schedule')
              ->helperText('Select when you want to be notified before contract expiration')
              ->options([
                30 => '30 days before',
                14 => '14 days before',
                7 => '7 days before',
                3 => '3 days before',
                1 => '1 day before',
              ])
              ->columns(3),
          ]),
      ]);
  }

  public function save(): void
  {
    $data = $this->form->getState();

    Auth::user()->notificationSettings()->updateOrCreate(
      ['user_id' => Auth::id()],
      $data
    );

    $this->notify('success', 'Notification preferences updated successfully.');
  }

  protected static string $view = 'filament.pages.notification-settings';
}
