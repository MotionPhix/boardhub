<?php

namespace App\Filament\Resources\SettingsResource\Pages;

use App\Filament\Resources\SettingsResource;
use App\Models\Settings;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class EditSettings extends Page
{
  protected static string $resource = SettingsResource::class;

  protected static string $view = 'filament.pages.settings.edit-settings';

  public ?array $data = [];

  public function mount(): void
  {
    $settings = Settings::firstOrCreate();
    $this->form->fill($settings->attributesToArray());
  }

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        // Move the form schema directly here
        Forms\Components\Tabs::make('Settings')
          ->tabs([
            // Company Profile Tab
            Forms\Components\Tabs\Tab::make('Company Profile')
              ->schema([
                Forms\Components\Section::make('Brand Identity')
                  ->schema([
                    Forms\Components\SpatieMediaLibraryFileUpload::make('logo')
                      ->collection('logo')
                      ->image()
                      ->imageEditor()
                      ->imageEditorAspectRatios([
                        '16:9',
                        '4:3',
                        '1:1',
                      ])
                      ->directory('logos')
                      ->preserveFilenames()
                      ->maxSize(2048)
                      ->downloadable()
                      ->openable()
                      ->label('Company Logo')
                      ->helperText('Recommended size: 200x200px. Supported formats: JPG, PNG, SVG, WebP'),

                    Forms\Components\SpatieMediaLibraryFileUpload::make('favicon')
                      ->collection('favicon')
                      ->image()
                      ->imageEditor()
                      ->imageEditorAspectRatios([
                        '1:1',
                      ])
                      ->directory('favicons')
                      ->preserveFilenames()
                      ->maxSize(512)
                      ->downloadable()
                      ->openable()
                      ->label('Favicon')
                      ->helperText('Recommended size: 32x32px. Supported formats: ICO, PNG, SVG'),
                  ])
                  ->columns(2),

                Forms\Components\Section::make('Company Information')
                  ->schema([
                    Forms\Components\TextInput::make('company_name')
                      ->label('Company Name')
                      ->required()
                      ->columnSpanFull(),

                    Forms\Components\TextInput::make('company_email')
                      ->label('Email')
                      ->email()
                      ->required(),

                    Forms\Components\TextInput::make('company_phone')
                      ->label('Phone')
                      ->tel(),

                    Forms\Components\Section::make('Address')
                      ->schema([
                        Forms\Components\TextInput::make('company_address_street')
                          ->label('Street'),

                        Forms\Components\TextInput::make('company_address_city')
                          ->label('City'),

                        Forms\Components\TextInput::make('company_address_state')
                          ->label('State/Region'),

                        Forms\Components\TextInput::make('company_address_country')
                          ->label('Country'),
                      ])
                      ->columns(2),

                    Forms\Components\TextInput::make('company_registration_number')
                      ->label('Registration Number'),

                    Forms\Components\TextInput::make('company_tax_number')
                      ->label('Tax Number'),
                  ])
                  ->columns(2),
              ]),

            // Regional Settings Tab
            Forms\Components\Tabs\Tab::make('Regional Settings')
              ->schema([
                Forms\Components\Section::make('Localization')
                  ->schema([
                    Forms\Components\Select::make('timezone')
                      ->options(array_combine(
                        timezone_identifiers_list(),
                        timezone_identifiers_list()
                      ))
                      ->searchable()
                      ->required(),

                    Forms\Components\Select::make('locale')
                      ->options([
                        'en' => 'English',
                        // Add more languages as needed
                      ])
                      ->required(),

                    Forms\Components\Select::make('date_format')
                      ->options([
                        'd M, Y' => 'D M, Y',
                        'Y-m-d' => 'YYYY-MM-DD',
                        'd/m/Y' => 'DD/MM/YYYY',
                        'm/d/Y' => 'MM/DD/YYYY',
                      ])
                      ->required(),

                    Forms\Components\Select::make('time_format')
                      ->options([
                        'H:i:s' => '24 Hour (HH:MM:SS)',
                        'h:i A' => '12 Hour (HH:MM AM/PM)',
                      ])
                      ->required(),
                  ])
                  ->columns(2),
              ]),

            // Document Settings Tab
            Forms\Components\Tabs\Tab::make('Document Settings')
              ->schema([
                Forms\Components\Section::make('Contract Settings')
                  ->schema([
                    Forms\Components\RichEditor::make('default_contract_terms')
                      ->label('Default Contract Terms')
                      ->columnSpanFull(),

                    Forms\Components\Textarea::make('contract_footer_text')
                      ->label('Contract Footer Text')
                      ->columnSpanFull(),
                  ]),
              ]),

            // Billboard Settings Tab
            Forms\Components\Tabs\Tab::make('Billboard Settings')
              ->schema([
                Forms\Components\Section::make('Code Format')
                  ->schema([
                    Forms\Components\TextInput::make('billboard_code_prefix')
                      ->label('Code Prefix')
                      ->required(),

                    Forms\Components\TextInput::make('billboard_code_separator')
                      ->label('Separator')
                      ->required(),

                    Forms\Components\TextInput::make('billboard_code_counter_length')
                      ->label('Counter Length')
                      ->numeric()
                      ->required()
                      ->minValue(1)
                      ->maxValue(10),
                  ])
                  ->columns(3),
              ]),
          ])
          ->persistTabInQueryString('tab')
          ->columnSpanFull(),
      ])
      ->statePath('data');
  }

  protected function getHeaderActions(): array
  {
    return [
      Actions\Action::make('save')
        ->label('Save changes')
        ->action('save')
        ->color('primary')
        ->icon('heroicon-m-check')
        ->keyBindings(['mod+s'])
    ];
  }

  public function save(): void
  {
    $settings = Settings::firstOrCreate();

    $data = $this->form->getState();

    $settings->fill($data);
    $settings->save();

    // Handle media uploads if present
    if (isset($data['logo'])) {
      $settings->clearMediaCollection('logo');
      $settings->addMediaFromDisk($data['logo'], 'public')
        ->toMediaCollection('logo');
    }

    if (isset($data['favicon'])) {
      $settings->clearMediaCollection('favicon');
      $settings->addMediaFromDisk($data['favicon'], 'public')
        ->toMediaCollection('favicon');
    }

    Notification::make()
      ->success()
      ->title('Settings updated')
      ->body('Your settings have been saved successfully.')
      ->send();

    // Clear any cached settings
    Cache::forget('settings');
  }

  protected function getFormActions(): array
  {
    return [
      Actions\Action::make('save')
        ->label('Save changes')
        ->submit('save')
        ->keyBindings(['mod+s'])
    ];
  }
}
