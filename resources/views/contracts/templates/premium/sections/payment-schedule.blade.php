<div class="subsection mt-4">
  <h3>3.2 Payment Terms</h3>

  <div class="payment-details">
    <h4 class="mb-2">3.2.1 Payment Schedule</h4>
    <table class="payment-schedule-table">
      <thead>
      <tr>
        <th>Payment Period</th>
        <th>Due Date</th>
        <th class="text-right">Amount</th>
        <th>Payment Method</th>
      </tr>
      </thead>
      <tbody>
      @php
        $startDate = $contract->start_date->copy();
        $endDate = $contract->end_date->copy();
        $currentDate = $startDate->copy();
      @endphp

      @while($currentDate->lte($endDate))
        <tr>
          <td>{{ $currentDate->format('F Y') }}</td>
          <td>{{ $currentDate->copy()->subDays(5)->format('jS F Y') }}</td>
          <td class="text-right">
            {{ $contract->currency->symbol }}
            {{ number_format($contract->contract_final_amount, 2) }}
          </td>
          <td>Bank Transfer</td>
        </tr>
        @php
          $currentDate->addMonth();
        @endphp
      @endwhile
      </tbody>
    </table>

    <div class="payment-notes mt-4">
      <h4 class="mb-2">3.2.2 Payment Terms and Conditions</h4>
      <ol type="a" class="ml-4">
        <li>All payments shall be made in {{ $contract->currency->name }} ({{ $contract->currency->code }});</li>
        <li>Payments are due five (5) business days before the start of each month;</li>
        <li>Late payments shall incur interest at 2% per month on the outstanding amount;</li>
        <li>Bank charges and transfer fees shall be borne by the Client;</li>
        <li>The Company reserves the right to suspend services if payment is overdue by more than 15 days;</li>
        <li>Annual rate reviews may apply, with any increases limited to the official inflation rate + 2%.</li>
      </ol>
    </div>

    <div class="banking-details mt-4">
      <h4 class="mb-2">3.2.3 Banking Details</h4>
      <table class="banking-info-table">
        <tbody>
        <tr>
          <td style="width: 30%;"><strong>Bank Name:</strong></td>
          <td>{{ $settings->getBankingDetails()['bank_name'] }}</td>
        </tr>
        <tr>
          <td><strong>Account Name:</strong></td>
          <td>{{ $settings->getBankingDetails()['account_name'] }}</td>
        </tr>
        <tr>
          <td><strong>Account Number:</strong></td>
          <td>{{ $settings->getBankingDetails()['account_number'] }}</td>
        </tr>
        <tr>
          <td><strong>Branch Code:</strong></td>
          <td>{{ $settings->getBankingDetails()['branch_code'] }}</td>
        </tr>
        <tr>
          <td><strong>Swift Code:</strong></td>
          <td>{{ $settings->getBankingDetails()['swift_code'] }}</td>
        </tr>
        <tr>
          <td><strong>Reference:</strong></td>
          <td>{{ $contract->contract_number }}</td>
        </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
