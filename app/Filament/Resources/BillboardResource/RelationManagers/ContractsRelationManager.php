<?php

namespace App\Filament\Resources\BillboardResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContractsRelationManager extends RelationManager
{
  protected static string $relationship = 'contracts';

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Select::make('client_id')
          ->relationship('client', 'name')
          ->required()
          ->searchable()
          ->preload(),
        Forms\Components\TextInput::make('contract_number')
          ->required()
          ->unique(ignoreRecord: true),
        Forms\Components\Grid::make(2)
          ->schema([
            Forms\Components\DateTimePicker::make('start_date')
              ->required(),
            Forms\Components\DateTimePicker::make('end_date')
              ->required(),
          ]),
        Forms\Components\TextInput::make('total_amount')
          ->numeric()
          ->prefix('$')
          ->required(),
        Forms\Components\Select::make('status')
          ->options([
            'draft' => 'Draft',
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
          ])
          ->required(),
        Forms\Components\Textarea::make('notes')
          ->maxLength(65535)
          ->columnSpanFull(),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('contract_number')
      ->columns([
        Tables\Columns\TextColumn::make('contract_number')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('client.name')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('start_date')
          ->dateTime()
          ->sortable(),
        Tables\Columns\TextColumn::make('end_date')
          ->dateTime()
          ->sortable(),
        Tables\Columns\TextColumn::make('total_amount')
          ->money()
          ->sortable(),
        Tables\Columns\BadgeColumn::make('status')
          ->colors([
            'danger' => 'cancelled',
            'warning' => 'draft',
            'success' => 'active',
            'gray' => 'completed',
          ]),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('status')
          ->options([
            'draft' => 'Draft',
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
          ]),
        Tables\Filters\Filter::make('active')
          ->query(fn (Builder $query): Builder => $query
            ->where('status', 'active')
            ->where('end_date', '>=', now())
          )
          ->toggle(),
      ])
      ->headerActions([
        Tables\Actions\CreateAction::make(),
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
}
