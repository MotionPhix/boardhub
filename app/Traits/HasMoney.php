<?php

namespace App\Traits;

use App\Models\Settings;

trait HasMoney
{
  public function formatMoney(float $amount, ?string $currencyCode = null): string
  {
    $currencyCode = $currencyCode ?? $this->currency_code ?? Settings::getDefaultCurrency()['code'];
    $currencies = Settings::getAvailableCurrencies();
    $currency = $currencies[$currencyCode] ?? $currencies['MWK'];

    return "{$currency['symbol']} " . number_format($amount, 2);
  }
}
