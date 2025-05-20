<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <style>
    @page {
      margin: 1.5cm;
      font-family: 'Lekton', monospace;
    }

    body {
      font-size: 12pt;
      line-height: 1.6;
      color: #1f2937;
    }

    .cover-page {
      padding-top: 1.5cm;
      text-align: center;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: center;
      page-break-after: always;
    }

    .cover-title {
      font-size: 36pt;
      font-weight: bold;
      margin-bottom: 2cm;
    }

    .party-name {
      font-size: 18pt;
      margin: 1cm 0;
      font-weight: bold;
    }

    .contract-number {
      margin-top: 4cm;
      font-size: 14pt;
    }

    .section {
      margin: 20px 0;
      page-break-inside: avoid;
    }

    .section-title {
      font-weight: bold;
      font-size: 14pt;
      margin: 15px 0;
      color: #374151;
    }

    .signature-section {
      page-break-inside: avoid;
    }

    .signature-box:not(:first-of-type) {
      border-top: 1px solid #9ca3af;
    }

    .company-info {
      color: #4b5563;
    }

    .footer {
      position: fixed;
      bottom: -0.5cm;
      left: 0;
      right: 0;
      text-align: center;
      border-top: 1px #f3f4f6 solid;
      font-size: 8pt;
      color: #6b7280;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
    }

    th, td {
      padding: 10px;
      border-top: 1px solid #d1d5db;
      text-align: left;
    }

    td {
      vertical-align: top;
    }

    th {
      background-color: #f3f4f6;
      font-weight: bold;
    }

    .no_border {
      border: none !important;
    }

    .amount {
      text-align: right;
    }

    .meta-info {
      border-top: 1px #f3f4f6 solid;
      font-size: 8pt;
      color: #6b7280;
      text-align: center;
      margin-top: 5px;
    }
  </style>
</head>
<body>
<!-- Cover Page -->
<div class="cover-page">
  <img
    src="{{ public_path('images/logo.png') }}"
    style="max-height: 100px; margin-bottom: 2rem"
    alt="{{ $settings->getCompanyProfile()['name'] }} Logo"><br>

  <div class="cover-title">
    ADVERTISING <br> RENTAL AGREEMENT <br> BETWEEN
  </div>

  <h3 class="party-name">
    {{ $settings->getCompanyProfile()['name'] }}
  </h3>

  <h3 class="party-name">AND</h3>

  <h3 class="party-name">
    {{ $contract->client->company ?: $contract->client->name }}
  </h3>

  <div class="contract-number">
    Contract Serial No: <strong>{{ $contract->contract_number }}</strong>
  </div>
</div>

<!-- Main Content -->
<div class="section">
  <h2 class="section-title">1. THE AGREEMENT</h2>
  <p>
    This agreement is made on <strong>{{ $contract->updated_at->format('jS F Y') }}</strong>
    between <strong>{{ $settings->getCompanyProfile()['name'] }}</strong> (hereinafter referred to as
    <strong>"the Company"</strong>) and <strong>{{ $contract->client->company ?: $contract->client->name }}</strong>
    (hereinafter referred to as <strong>"the Client"</strong>) for the rental of advertising space as detailed below:
  </p>

  <table>
    <thead>
    <tr>
      <th style="border-left: 1px solid #d1d5db;">Billboard</th>
      <th>Location</th>
      <th class="amount" style="border-right: 1px solid #d1d5db;">Monthly Rate</th>
    </tr>
    </thead>
    <tbody>
    @foreach($contract->billboards as $billboard)
      <tr>
        <td>
          <div style="display: flex; flex-direction: column;">
            <div>{{ $billboard->size }}</div>
            <div>
              <small style="color: #6B7280; font-size: 12px;">{{ $billboard->code }}</small>
            </div>
          </div>
        </td>

        <td>
          <div style="display: flex; flex-direction: column">
            <div>{{ $billboard->location->name }}, {{ $billboard->name }}</div>
            <div>
              <small style="color: #6B7280; font-size: 12px;">
                {{ $billboard->location->city->name }},
                {{ $billboard->location->state->name }},
                {{ $billboard->location->country->name }}
              </small>
            </div>
          </div>
        </td>

        <td class="amount">
          {{ $contract->currency->symbol }}
          {{ number_format($billboard->pivot->billboard_base_price, 2) }}
        </td>
      </tr>
    @endforeach
    </tbody>

    <tfoot>
    <tr>
      <td colspan="2" class="amount"><strong>Total Amount:</strong></td>
      <td class="amount">
        <strong>
          {{ $contract->currency->symbol }}
          {{ number_format($contract->contract_total, 2) }}
        </strong>
      </td>
    </tr>
    @if($contract->contract_discount > 0)
      <tr>
        <td colspan="2" class="amount">Discount:</td>
        <td class="amount">
          {{ $contract->currency->symbol }}
          {{ number_format($contract->contract_discount, 2) }}
        </td>
      </tr>
      <tr>
        <td colspan="2" class="amount"><strong>Final Amount:</strong></td>
        <td class="amount">
          <strong>
            {{ $contract->currency->symbol }}
            {{ number_format($contract->contract_final_amount, 2) }}
          </strong>
        </td>
      </tr>
    @endif
    </tfoot>
  </table>

  <p>
    <strong>**</strong> The rental period shall commence on <strong>{{ $contract->start_date->format('jS F Y') }}</strong>,
    and end on <strong>{{ $contract->end_date->format('jS F Y') }}</strong>. Thus the contract will be run for
    <strong>{{ $contract->start_date->diffInMonths($contract->end_date) }} months</strong>.
  </p>
</div>

<div class="section">
  <h2 class="section-title">2. MAINTENANCE</h2>
  <p>
    The Company shall be responsible for the maintenance and repair of any damaged billboards during the rental period.
    Any damage caused by natural wear and tear or weather conditions shall be repaired by the Company at no additional cost.
    However, any damage caused by the Client's negligence or misuse shall be repaired at the Client's expense.
  </p>
</div>

<div class="section">
  <h2 class="section-title">3. DISPUTES</h2>
  <p>
    Any disputes arising from this agreement shall be resolved through amicable negotiation between both parties.
    If no resolution can be reached, the matter shall be referred to arbitration in accordance with the laws of
    {{ $settings->getCompanyProfile()['address']['country'] }}.
  </p>
</div>

<div class="section">
  <h2 class="section-title">4. TERMINATION</h2>
  <p>
    Either party may terminate this agreement with a 30-day written notice. In case of early termination by the Client,
    a cancellation fee equivalent to one month's rental shall be payable. The Company reserves the right to terminate
    this agreement immediately in case of breach of contract terms by the Client.
  </p>
</div>

@if($settings->getDocumentSettings()['contract_terms'])
  <div class="section">
    <h2 class="section-title">5. TERMS AND CONDITIONS</h2>
    {!! $settings->getDocumentSettings()['contract_terms'] !!}
  </div>
@endif

@if($contract->notes)
  <div class="section">
    <h2 class="section-title">NOTES</h2>
    {!! $contract->notes !!}
  </div>
@endif

<div class="signature-section">
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="signature-box">
      <p>
        <strong>
          Signed on behalf of {{ $settings->getCompanyProfile()['name'] }} ("The Company")
        </strong>
      </p>

      @if(isset($contract->signatures['company']))
        <img src="{{ $contract->signatures['company'] }}" alt="Company Signature" style="max-height: 100px"><br>
      @endif

      <table>
        <tbody>
        <tr>
          <td class="no_border">Name</td>
          <td class="no_border">: __________________________________________</td>
        </tr>
        <tr>
          <td class="no_border">Title</td>
          <td class="no_border">: __________________________________________</td>
        </tr>
        <tr>
          <td class="no_border">Date</td>
          <td class="no_border">: __________________________________________</td>
        </tr>
        </tbody>
      </table>

      <div class="company-info">
        {{ $settings->getCompanyProfile()['address']['street'] }}<br>
        {{ $settings->getCompanyProfile()['address']['city'] }},
        {{ $settings->getCompanyProfile()['address']['state'] }}<br>
        {{ $settings->getCompanyProfile()['address']['country'] }}<br><br>

        @if($settings->getCompanyProfile()['phone'])
          P: {{ $settings->getCompanyProfile()['phone'] }}<br>
        @endif
        E: {{ $settings->getCompanyProfile()['email'] }}<br>

        @if($settings->getCompanyProfile()['registration_number'])
          Reg. No: {{ $settings->getCompanyProfile()['registration_number'] }}<br>
        @endif

        @if($settings->getCompanyProfile()['tax_number'])
          Tax No: {{ $settings->getCompanyProfile()['tax_number'] }}
        @endif
      </div>
    </div>

    <div class="signature-box" style="margin-top: 4rem; border-top: 1px solid #9ca3af; padding-top: 2.5rem">
      <p>
        <strong>
          Signed on behalf of {{ $contract->client->company ?: $contract->client->name }} ("The Client")
        </strong>
      </p>

      @if(isset($contract->signatures['client']))
        <img src="{{ $contract->signatures['client'] }}" alt="Client Signature" style="max-height: 100px"><br>
      @endif

      <table>
        <tbody>
        <tr>
          <td class="no_border">Name</td>
          <td class="no_border">: __________________________________________</td>
        </tr>
        <tr>
          <td class="no_border">Title</td>
          <td class="no_border">: __________________________________________</td>
        </tr>
        <tr>
          <td class="no_border">Date</td>
          <td class="no_border">: __________________________________________</td>
        </tr>
        </tbody>
      </table>

      <div class="company-info">
        {{ $contract->client->street }}<br>
        {{ $contract->client->city }}, {{ $contract->client->state }}<br>
        {{ $contract->client->country }}<br><br>

        @if($contract->client->phone)
          P: {{ $contract->client->phone }}<br>
        @endif
        E: {{ $contract->client->email }}
      </div>
    </div>
  </div>
</div>

<div class="footer">
  {!! $settings->getDocumentSettings()['contract_footer'] !!}
  <div class="meta-info">
    Generated on {{ $date }} by {{ $generatedBy }}
  </div>
</div>
</body>
</html>
