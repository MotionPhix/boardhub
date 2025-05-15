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
  protected static ?string $navigationGroup = 'Settings';

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Card::make()
          ->schema([
            Forms\Components\TextInput::make('name')
              ->required()
              ->maxLength(255),

            Forms\Components\Textarea::make('description')
              ->maxLength(65535)
              ->columnSpanFull(),

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
              ->placeholder('Enter the contract template content here...')
              ->helperText('Use {{variable}} syntax for dynamic content')
              ->columnSpanFull(),

            Forms\Components\Toggle::make('is_default')
              ->label('Set as Default Template')
              ->helperText('Only one template can be set as default'),

            Forms\Components\KeyValue::make('variables')
              ->label('Template Variables')
              ->helperText('Define available variables and their descriptions')
              ->addButtonLabel('Add Variable')
              ->keyLabel('Variable Name')
              ->valueLabel('Description')
              ->columnSpanFull(),
          ])
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('name')
          ->searchable()
          ->sortable(),

        Tables\Columns\IconColumn::make('is_default')
          ->boolean()
          ->label('Default'),

        Tables\Columns\TextColumn::make('updated_at')
          ->dateTime()
          ->sortable(),
      ])
      ->filters([
        //
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

  public static function getRelations(): array
  {
    return [
      //
    ];
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
