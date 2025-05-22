<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractTemplateResource\Pages;
use App\Models\ContractTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Jobs\GenerateContractTemplatePreview;
use Illuminate\Support\Str;

class ContractTemplateResource extends Resource
{
  protected static ?string $model = ContractTemplate::class;

  protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

  protected static ?string $navigationGroup = 'Management';

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Template Details')
              ->schema([
                Forms\Components\TextInput::make('name')
                  ->required()
                  ->maxLength(255)
                  ->reactive()
                  ->afterStateUpdated(function ($state, callable $set) {
                    if (!$state) return;
                    $set('content', Str::slug($state));
                  }),

                Forms\Components\Textarea::make('description')
                  ->maxLength(65535)
                  ->columnSpanFull(),

                Forms\Components\Select::make('template_type')
                  ->options([
                    'standard' => 'Standard Contract',
                    'premium' => 'Premium Contract',
                    'executive' => 'Executive Contract',
                    'corporate' => 'Corporate Contract',
                  ])
                  ->required(),

                Forms\Components\TextInput::make('content')
                  ->required()
                  ->helperText('Template path relative to contracts.templates directory')
                  ->placeholder('e.g., standard/advertising-agreement'),

                Forms\Components\Toggle::make('is_default')
                  ->label('Set as Default Template')
                  ->helperText('Only one template can be set as default')
                  ->reactive()
                  ->afterStateUpdated(function ($state, callable $set) {
                    if ($state) {
                      ContractTemplate::where('is_default', true)
                        ->where('id', '!=', $this->record?->id)
                        ->update(['is_default' => false]);
                    }
                  }),

                Forms\Components\Toggle::make('is_active')
                  ->label('Active')
                  ->default(true),
              ])
              ->columns(2),

            Forms\Components\Section::make('Template Settings')
              ->schema([
                Forms\Components\Repeater::make('variables')
                  ->schema([
                    Forms\Components\TextInput::make('name')
                      ->required(),
                    Forms\Components\TextInput::make('description')
                      ->required(),
                  ])
                  ->columns(2)
                  ->collapsible(),

                Forms\Components\CheckboxList::make('settings.terms_sections')
                  ->label('Included Sections')
                  ->options([
                    'payment' => 'Payment Terms',
                    'maintenance' => 'Maintenance',
                    'liability' => 'Liability',
                    'termination' => 'Termination',
                    'disputes' => 'Disputes',
                    'confidentiality' => 'Confidentiality',
                    'intellectual_property' => 'Intellectual Property',
                    'force_majeure' => 'Force Majeure',
                    'insurance' => 'Insurance',
                  ])
                  ->columns(2),

                Forms\Components\Grid::make()
                  ->schema([
                    Forms\Components\Toggle::make('settings.header_enabled')
                      ->label('Show Header')
                      ->default(true),

                    Forms\Components\Toggle::make('settings.footer_enabled')
                      ->label('Show Footer')
                      ->default(true),

                    Forms\Components\Toggle::make('settings.page_numbering')
                      ->label('Page Numbers')
                      ->default(true),

                    Forms\Components\Toggle::make('settings.table_of_contents')
                      ->label('Table of Contents')
                      ->default(true),
                  ])
                  ->columns(2),
              ]),
          ])
          ->columnSpan(['lg' => 2]),

        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Preview')
              ->schema([
                Forms\Components\View::make('filament.resources.contract-template-resource.preview'),
              ]),

            Forms\Components\Section::make('Actions')
              ->schema([
                Forms\Components\Actions::make([
                  Forms\Components\Actions\Action::make('regenerate_preview')
                    ->label('Regenerate Preview')
                    ->action(function (ContractTemplate $record) {
                      GenerateContractTemplatePreview::dispatch($record);
                      Notification::make()
                        ->title('Preview generation started')
                        ->success()
                        ->send();
                    })
                    ->visible(fn ($record) => $record !== null),
                ]),
              ]),
          ])
          ->columnSpan(['lg' => 1]),
      ])
      ->columns(3);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\ImageColumn::make('preview_image_url')
          ->label('Preview')
          ->square(),

        Tables\Columns\TextColumn::make('name')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('template_type')
          ->badge()
          ->colors([
            'primary' => 'standard',
            'success' => 'premium',
            'warning' => 'executive',
            'danger' => 'corporate',
          ]),

        Tables\Columns\IconColumn::make('is_default')
          ->boolean(),

        Tables\Columns\IconColumn::make('is_active')
          ->boolean(),

        Tables\Columns\TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('template_type')
          ->options([
            'standard' => 'Standard Contract',
            'premium' => 'Premium Contract',
            'executive' => 'Executive Contract',
            'corporate' => 'Corporate Contract',
          ]),
        Tables\Filters\TernaryFilter::make('is_active'),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
        Tables\Actions\Action::make('preview')
          ->label('Generate Preview')
          ->icon('heroicon-o-eye')
          ->action(function (ContractTemplate $record) {
            GenerateContractTemplatePreview::dispatch($record);
          }),
        Tables\Actions\DeleteAction::make(),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
        ]),
      ]);
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListContractTemplates::route('/'),
      'create' => Pages\CreateContractTemplate::route('/create'),
      'view' => Pages\ViewContractTemplate::route('/{record}'),
      'edit' => Pages\EditContractTemplate::route('/{record}/edit'),
    ];
  }
}
