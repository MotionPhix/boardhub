<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Contract;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClientStatsOverview extends BaseWidget
{
  protected function getStats(): array
  {
    // Get total contract value for active contracts with in-use billboards
    $totalContractValue = Contract::query()
      ->where('agreement_status', 'active')
      ->whereHas('billboards', function ($query) {
        $query->whereHas('contracts', function ($subQuery) {
          $subQuery->where('billboard_contract.booking_status', 'in_use');
        });
      })
      ->sum('total_amount');

    // Count clients with active contracts and in-use billboards
    $clientsWithActiveContracts = Client::whereHas('contracts', function ($query) {
      $query->where('agreement_status', 'active')
        ->whereHas('billboards', function ($subQuery) {
          $subQuery->whereHas('contracts', function ($billboardQuery) {
            $billboardQuery->where('billboard_contract.booking_status', 'in_use');
          });
        });
    })->count();

    return [
      Stat::make('Total Clients', Client::count())
        ->description('Total number of registered clients')
        ->descriptionIcon('heroicon-m-users')
        ->color('primary')
        ->chart([
          Client::whereMonth('created_at', now()->subMonth(2))->count(),
          Client::whereMonth('created_at', now()->subMonth(1))->count(),
          Client::whereMonth('created_at', now()->month)->count(),
        ]),

      Stat::make('Active Clients', $clientsWithActiveContracts)
        ->description('Clients with active contracts')
        ->descriptionIcon('heroicon-m-document-check')
        ->color('success')
        ->chart([
          Client::whereHas('contracts', function ($query) {
            $query->where('agreement_status', 'active')
              ->whereHas('billboards', function ($subQuery) {
                $subQuery->whereHas('contracts', function ($billboardQuery) {
                  $billboardQuery->where('billboard_contract.booking_status', 'in_use');
                });
              });
          })->whereMonth('created_at', now()->subMonth(2))->count(),
          Client::whereHas('contracts', function ($query) {
            $query->where('agreement_status', 'active')
              ->whereHas('billboards', function ($subQuery) {
                $subQuery->whereHas('contracts', function ($billboardQuery) {
                  $billboardQuery->where('billboard_contract.booking_status', 'in_use');
                });
              });
          })->whereMonth('created_at', now()->subMonth(1))->count(),
          Client::whereHas('contracts', function ($query) {
            $query->where('agreement_status', 'active')
              ->whereHas('billboards', function ($subQuery) {
                $subQuery->whereHas('contracts', function ($billboardQuery) {
                  $billboardQuery->where('billboard_contract.booking_status', 'in_use');
                });
              });
          })->whereMonth('created_at', now()->month)->count(),
        ]),

      Stat::make('Total Contract Value', 'MK ' . number_format($totalContractValue, 2))
        ->description('Value of active contracts')
        ->descriptionIcon('heroicon-m-currency-dollar')
        ->color('success')
        ->chart([
          Contract::where('agreement_status', 'active')
            ->whereHas('billboards', function ($query) {
              $query->whereHas('contracts', function ($subQuery) {
                $subQuery->where('billboard_contract.booking_status', 'in_use');
              });
            })
            ->whereMonth('created_at', now()->subMonth(2))
            ->sum('total_amount'),
          Contract::where('agreement_status', 'active')
            ->whereHas('billboards', function ($query) {
              $query->whereHas('contracts', function ($subQuery) {
                $subQuery->where('billboard_contract.booking_status', 'in_use');
              });
            })
            ->whereMonth('created_at', now()->subMonth(1))
            ->sum('total_amount'),
          Contract::where('agreement_status', 'active')
            ->whereHas('billboards', function ($query) {
              $query->whereHas('contracts', function ($subQuery) {
                $subQuery->where('billboard_contract.booking_status', 'in_use');
              });
            })
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount'),
        ]),
    ];
  }
}
