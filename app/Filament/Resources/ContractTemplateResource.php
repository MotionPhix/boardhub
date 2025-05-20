<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractTemplateResource\Pages;
use App\Models\ContractTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                  ->maxLength(255),

                Forms\Components\Textarea::make('description')
                  ->maxLength(65535)
                  ->columnSpanFull(),

                Forms\Components\Select::make('template_type')
                  ->options([
                    'standard' => 'Standard Contract',
                    'premium' => 'Premium Contract',
                    'simple' => 'Simple Contract',
                  ])
                  ->required(),

                Forms\Components\Toggle::make('is_default')
                  ->label('Set as Default Template')
                  ->helperText('Only one template can be set as default')
                  ->reactive()
                  ->afterStateUpdated(function ($state, callable $set) {
                    if ($state) {
                      // Remove default from other templates
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

            Forms\Components\Section::make('Template Content')
              ->schema([
                Forms\Components\RichEditor::make('content')
                  ->required()
                  ->toolbarButtons([
                    'bold',
                    'italic',
                    'underline',
                    'strike',
                    'link',
                    'orderedList',
                    'unorderedList',
                    'h2',
                    'h3',
                  ])
                  ->columnSpanFull(),
              ]),
          ])
          ->columnSpan(['lg' => 2]),

        Forms\Components\Group::make()
          ->schema([
            Forms\Components\Section::make('Preview & Variables')
              ->schema([
                Forms\Components\FileUpload::make('preview_image')
                  ->image()
                  ->directory('contract-templates')
                  ->visibility('public')
                  ->imagePreviewHeight('256')
                  ->columnSpanFull(),

                Forms\Components\Repeater::make('variables')
                  ->schema([
                    Forms\Components\TextInput::make('name')
                      ->required(),
                    Forms\Components\TextInput::make('description')
                      ->required(),
                  ])
                  ->columnSpanFull(),
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
        Tables\Columns\TextColumn::make('name')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('template_type')
          ->badge(),

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
            'simple' => 'Simple Contract',
          ]),
        Tables\Filters\TernaryFilter::make('is_active'),
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
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
      'edit' => Pages\EditContractTemplate::route('/{record}/edit'),
    ];
  }
}
