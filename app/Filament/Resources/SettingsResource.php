<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingsResource\Pages;
use App\Models\Settings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SettingsResource extends Resource
{
  protected static ?string $model = Settings::class;

  protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

  protected static ?string $navigationGroup = 'System';

  protected static ?int $navigationSort = 100;

  protected static ?string $modelLabel = 'System Settings';

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Tabs::make('Settings')
          ->tabs([
            Forms\Components\Tabs\Tab::make('Company Profile')
              ->schema([
                Forms\Components\Section::make('Brand Identity')
                  ->schema([
                    Forms\Components\SpatieMediaLibraryFileUpload::make('logo')
                      ->collection('logo')
                      ->image()
                      ->imageEditor()
                      ->directory('logos')
                      ->maxSize(2048)
                      ->columnSpanFull(),

                    Forms\Components\SpatieMediaLibraryFileUpload::make('favicon')
                      ->collection('favicon')
                      ->image()
                      ->directory('favicons')
                      ->maxSize(1024)
                      ->columnSpanFull(),
                  ])
                  ->columns(2)
                  ->collapsed(),

                Forms\Components\Section::make('Company Information')
                  ->schema([
                    Forms\Components\TextInput::make('value.name')
                      ->label('Company Name')
                      ->required()
                      ->maxLength(255),
                    Forms\Components\TextInput::make('value.email')
                      ->label('Company Email')
                      ->email()
                      ->required()
                      ->maxLength(255),
                    Forms\Components\TextInput::make('value.phone')
                      ->label('Company Phone')
                      ->tel()
                      ->maxLength(255),
                    Forms\Components\Textarea::make('value.address')
                      ->label('Company Address')
                      ->maxLength(65535)
                      ->columnSpanFull(),
                    Forms\Components\TextInput::make('value.registration_number')
                      ->label('Registration Number')
                      ->maxLength(255),
                    Forms\Components\TextInput::make('value.tax_number')
                      ->label('Tax Number')
                      ->maxLength(255),
                  ])
                  ->columns(2),
              ])
              ->hidden(fn ($record) => $record?->key !== 'company_profile'),

            Forms\Components\Tabs\Tab::make('Localization')
              ->schema([
                Forms\Components\Section::make('Regional Settings')
                  ->schema([
                    Forms\Components\Select::make('value.timezone')
                      ->label('Timezone')
                      ->options(collect(timezone_identifiers_list())
                        ->mapWithKeys(fn ($tz) => [$tz => $tz]))
                      ->searchable()
                      ->required(),
                    Forms\Components\Select::make('value.locale')
                      ->label('Language')
                      ->options([
                        'en' => 'English',
                        // Add more languages as needed
                      ])
                      ->required(),
                    Forms\Components\Select::make('value.date_format')
                      ->label('Date Format')
                      ->options([
                        'Y-m-d' => 'YYYY-MM-DD',
                        'd/m/Y' => 'DD/MM/YYYY',
                        'm/d/Y' => 'MM/DD/YYYY',
                      ])
                      ->required(),
                    Forms\Components\Select::make('value.time_format')
                      ->label('Time Format')
                      ->options([
                        'H:i' => '24-hour',
                        'h:i A' => '12-hour',
                      ])
                      ->required(),
                  ])
                  ->columns(2),
              ])
              ->hidden(fn ($record) => $record?->key !== 'localization'),

            Forms\Components\Tabs\Tab::make('Currency')
              ->schema([
                Forms\Components\Section::make('Default Currency')
                  ->schema([
                    Forms\Components\Select::make('value.code')
                      ->label('Currency')
                      ->options([
                        'MWK' => 'Malawian Kwacha (MWK)',
                        'USD' => 'US Dollar (USD)',
                        'ZMW' => 'Zambian Kwacha (ZMW)',
                        // Add more currencies as needed
                      ])
                      ->required(),
                    Forms\Components\TextInput::make('value.symbol')
                      ->label('Symbol')
                      ->required()
                      ->maxLength(10),
                    Forms\Components\TextInput::make('value.name')
                      ->label('Currency Name')
                      ->required()
                      ->maxLength(255),
                  ])
                  ->columns(2),
              ])
              ->hidden(fn ($record) => $record?->key !== 'default_currency'),

            Forms\Components\Tabs\Tab::make('Document Settings')
              ->schema([
                Forms\Components\Section::make('Invoice Settings')
                  ->schema([
                    Forms\Components\TextInput::make('value.invoice_prefix')
                      ->label('Invoice Prefix')
                      ->maxLength(255),
                    Forms\Components\RichEditor::make('value.invoice_footer_text')
                      ->label('Invoice Footer Text')
                      ->maxLength(65535)
                      ->columnSpanFull(),
                  ]),

                Forms\Components\Section::make('Contract Settings')
                  ->schema([
                    Forms\Components\RichEditor::make('value.default_contract_terms')
                      ->label('Default Contract Terms')
                      ->maxLength(65535)
                      ->columnSpanFull(),
                    Forms\Components\RichEditor::make('value.contract_footer_text')
                      ->label('Contract Footer Text')
                      ->maxLength(65535)
                      ->columnSpanFull(),
                  ]),

                Forms\Components\Section::make('Payment Terms')
                  ->schema([
                    Forms\Components\Repeater::make('value.default_payment_terms')
                      ->label('Default Payment Terms')
                      ->schema([
                        Forms\Components\TextInput::make('days')
                          ->numeric()
                          ->required(),
                        Forms\Components\TextInput::make('description')
                          ->required()
                          ->maxLength(255),
                      ])
                      ->columns(2),
                  ]),
              ])
              ->hidden(fn ($record) => $record?->key !== 'document_settings'),
          ])
          ->columnSpanFull(),

        Forms\Components\Hidden::make('key'),
        Forms\Components\Hidden::make('group'),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('key')
          ->label('Setting')
          ->formatStateUsing(fn (string $state): string => str($state)->title()),
        Tables\Columns\TextColumn::make('group')
          ->formatStateUsing(fn (string $state): string => str($state)->title()),
        Tables\Columns\TextColumn::make('updated_at')
          ->dateTime()
          ->sortable(),
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
      ]);
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListSettings::route('/'),
      'edit' => Pages\EditSettings::route('/{record}/edit'),
    ];
  }
}
