<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewUser extends ViewRecord
{
  protected static string $resource = UserResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make(),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Infolists\Components\Section::make('User Information')
          ->schema([
            Infolists\Components\ImageEntry::make('avatar')
              ->circular()
              ->size(100),

            Infolists\Components\TextEntry::make('name'),

            Infolists\Components\TextEntry::make('email'),

            Infolists\Components\IconEntry::make('email_verified_at')
              ->label('Email verified')
              ->boolean(),

            Infolists\Components\TextEntry::make('roles.name')
              ->badge()
              ->color('primary'),

            Infolists\Components\IconEntry::make('is_admin')
              ->label('Administrator')
              ->boolean(),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Profile Details')
          ->schema([
            Infolists\Components\TextEntry::make('phone'),
            Infolists\Components\TextEntry::make('bio'),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Timestamps')
          ->schema([
            Infolists\Components\TextEntry::make('created_at')
              ->dateTime(),
            Infolists\Components\TextEntry::make('updated_at')
              ->dateTime(),
          ])
          ->columns(2),
      ]);
  }
}
