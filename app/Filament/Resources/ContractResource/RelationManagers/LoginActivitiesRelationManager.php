<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Carbon\Carbon;

class LoginActivitiesRelationManager extends RelationManager
{
  protected static string $relationship = 'loginActivities';

  protected static ?string $recordTitleAttribute = 'login_at';

  protected static ?string $title = 'Login History';

  protected static ?string $icon = 'heroicon-m-finger-print';

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('login_at')
      ->heading('Login Activities')
      ->description('A record of user login attempts and sessions.')
      ->columns([
        IconColumn::make('login_successful')
          ->label('Status')
          ->boolean()
          ->trueColor('success')
          ->falseColor('danger')
          ->alignCenter(),

        TextColumn::make('ip_address')
          ->label('IP Address')
          ->searchable()
          ->sortable()
          ->toggleable(),

        TextColumn::make('location')
          ->searchable()
          ->sortable()
          ->toggleable(),

        TextColumn::make('browser')
          ->searchable()
          ->sortable()
          ->toggleable(),

        TextColumn::make('device')
          ->searchable()
          ->sortable()
          ->toggleable(),

        TextColumn::make('login_at')
          ->label('Login Time')
          ->dateTime()
          ->sortable()
          ->description(fn ($record) => $record->login_at->diffForHumans())
          ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('M d, Y H:i:s')),

        TextColumn::make('user_agent')
          ->label('User Agent')
          ->limit(50)
          ->searchable()
          ->toggleable()
          ->toggledHiddenByDefault(),
      ])
      ->defaultSort('login_at', 'desc')
      ->filters([
        Tables\Filters\Filter::make('successful_logins')
          ->label('Successful Logins')
          ->query(fn ($query) => $query->where('login_successful', true))
          ->toggle(),

        Tables\Filters\Filter::make('failed_logins')
          ->label('Failed Attempts')
          ->query(fn ($query) => $query->where('login_successful', false))
          ->toggle(),

        Tables\Filters\Filter::make('recent')
          ->label('Last 24 Hours')
          ->query(fn ($query) => $query->where('login_at', '>=', now()->subDay()))
          ->toggle(),
      ])
      ->bulkActions([])
      ->paginated([10, 25, 50, 100])
      ->defaultPaginationPageOption(25);
  }
}
