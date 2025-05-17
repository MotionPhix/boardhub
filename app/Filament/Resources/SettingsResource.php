<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingsResource\Pages;
use App\Models\Settings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class SettingsResource extends Resource
{
  protected static ?string $model = Settings::class;

  protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
  protected static ?string $navigationGroup = 'System';
  protected static ?string $navigationLabel = 'Settings';
  protected static ?int $navigationSort = 100;

  public static function form(Form $form): Form
  {
    return $form->schema([
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
                    ->directory('logos'),

                  Forms\Components\SpatieMediaLibraryFileUpload::make('favicon')
                    ->collection('favicon')
                    ->image()
                    ->directory('favicons'),
                ])
                ->columns(2),

              Forms\Components\Section::make('Company Information')
                ->schema([
                  Forms\Components\TextInput::make('company_name')
                    ->label('Company Name')
                    ->required(),

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
        ->columnSpanFull(),
    ]);
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\EditSettings::route('/'),
    ];
  }
}
