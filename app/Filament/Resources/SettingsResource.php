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
                      ->columnSpanFull()
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

                    Forms\Components\Section::make('Company Address')
                      ->schema([

                        Forms\Components\TextInput::make('value.address.street')
                          ->label('Street Name')
                          ->maxLength(255),

                        Forms\Components\TextInput::make('value.address.city')
                          ->label('City')
                          ->placeholder('Where the branch is located')
                          ->maxLength(255),

                        Forms\Components\TextInput::make('value.address.state')
                          ->label('State/Region/Province')
                          ->maxLength(255),

                        Forms\Components\TextInput::make('value.address.country')
                          ->label('Country')
                          ->maxLength(255),

                      ])
                      ->columns(2),

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
                Forms\Components\Section::make('Currency Settings')
                  ->schema([
                    Forms\Components\Select::make('value.code')
                      ->label('Currency')
                      ->options([
                        'MWK' => 'Malawian Kwacha (MWK)',
                        'USD' => 'US Dollar (USD)',
                        'ZMW' => 'Zambian Kwacha (ZMW)',
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

                    Forms\Components\Radio::make('value.is_default')
                      ->label('Make Default')
                      ->default(false)
                      ->required()
                  ])
                  ->columns(2),
              ])
              ->hidden(fn ($record) => $record?->key !== 'currency_settings'),

            Forms\Components\Tabs\Tab::make('Document Settings')
              ->schema([
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
              ])
              ->hidden(fn ($record) => $record?->key !== 'document_settings'),

            Forms\Components\Tabs\Tab::make('Billboard Settings')
              ->schema([
                Forms\Components\Section::make('Billboard Code Format')
                  ->schema([
                    Forms\Components\TextInput::make('value.prefix')
                      ->label('Billboard Code Prefix')
                      ->required()
                      ->maxLength(10)
                      ->helperText('Company initials or short code (e.g., BH, FM)'),

                    Forms\Components\TextInput::make('value.separator')
                      ->label('Code Separator')
                      ->required()
                      ->default('-')
                      ->maxLength(5)
                      ->helperText('Character to separate code parts (e.g., -)'),

                    Forms\Components\TextInput::make('value.counter_length')
                      ->label('Counter Length')
                      ->required()
                      ->numeric()
                      ->default(5)
                      ->minValue(1)
                      ->maxValue(10)
                      ->helperText('Number of digits for the sequence number (e.g., 5 for 00001)'),
                  ])
                  ->columns(2)
                  ->description('Billboard codes will be generated in the format: PREFIX-CITYCODE-SEQUENCE
                For example: BH-BT-00001 (for a billboard in Blantyre)'),
              ])
              ->hidden(fn ($record) => $record?->key !== 'billboard_code_format'),
          ])
          ->columnSpanFull(),

        Forms\Components\Hidden::make('key'),
        Forms\Components\Hidden::make('group'),
      ]);
  }

  /*public static function table(Table $table): Table
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
  }*/

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('key')
          ->label('Setting')
          ->formatStateUsing(function (string $state): string {
            return match($state) {
              'company_profile' => 'Company Profile',
              'default_currency' => 'Currency Settings',
              'localization' => 'Regional Settings',
              'document_settings' => 'Document Settings',
              'billboard_code_format' => 'Billboard Code Format',
              default => str($state)->title(),
            };
          })
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('value')
          ->label('Configuration')
          ->formatStateUsing(function ($record): string {
            return match($record->key) {
              'company_profile' => $record->value['name'] ?? 'Not configured',
              'default_currency' => ($record->value['symbol'] ?? '') . ' ' . ($record->value['code'] ?? 'Not set'),
              'localization' => ($record->value['timezone'] ?? 'Not set') . ' | ' . strtoupper($record->value['locale'] ?? ''),
              'billboard_code_format' => ($record->value['prefix'] ?? '') . ($record->value['separator'] ?? '-') . 'XX' . ($record->value['separator'] ?? '-') . str_pad('1', $record->value['counter_length'] ?? 5, '0', STR_PAD_LEFT),
              default => 'Configured',
            };
          })
          ->wrap()
          ->searchable(),

        Tables\Columns\TextColumn::make('group')
          ->badge()
          ->color(fn (string $state): string => match ($state) {
            'company' => 'success',
            'system' => 'danger',
            'documents' => 'warning',
            'billboards' => 'info',
            default => 'gray',
          })
          ->formatStateUsing(fn (string $state): string => str($state)->title()),

        Tables\Columns\TextColumn::make('updated_at')
          ->label('Last Updated')
          ->dateTime()
          ->sortable()
          ->description(fn ($record) => $record->updated_at->diffForHumans()),
      ])
      ->defaultSort('updated_at', 'desc')
      ->actions([
        Tables\Actions\EditAction::make()
          ->button()
          ->label('Configure'),
      ])
      ->striped();
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListSettings::route('/'),
      'edit' => Pages\EditSettings::route('/{record}/edit'),
    ];
  }
}
