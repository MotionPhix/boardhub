<x-mail::message>
  # Contract Agreement: {{ $contract->contract_number }}

  Dear {{ $contract->client->name }},

  Please find attached the contract agreement for your review.

  Contract Details:
  - Contract Number: {{ $contract->contract_number }}
  - Start Date: {{ $contract->start_date->format('F j, Y') }}
  - End Date: {{ $contract->end_date->format('F j, Y') }}
  - Total Amount: {{ $contract->currency_code }} {{ number_format($contract->contract_final_amount, 2) }}

  <x-mail::button :url="$viewUrl">
    View Contract Online
  </x-mail::button>

  If you have any questions or concerns, please don't hesitate to contact us.

  Thanks,<br>
  {{ config('app.name') }}
</x-mail::message>
