@extends('contracts.templates.layouts.contract')

@section('content')
  {{-- Cover Page --}}
  <div class="cover-page">
    <img src="{{ public_path('images/logo.png') }}"
         alt="{{ $settings->getCompanyProfile()['name'] }}"
         style="max-height: 3cm; margin-bottom: 2cm;">

    <h1 class="mb-8">ADVERTISING RENTAL AGREEMENT</h1>

    <div class="mb-8">between</div>

    <h3 class="mb-4">{{ $settings->getCompanyProfile()['name'] }}</h3>

    <div class="mb-4">and</div>

    <h3 class="mb-8">{{ $contract->client->company ?: $contract->client->name }}</h3>

    <div class="text-sm" style="position: absolute; bottom: 3cm; left: 0; right: 0;">
      <div class="font-bold mb-1">Contract Reference</div>
      {{ $contract->contract_number }}
    </div>
  </div>

  {{-- Main Content --}}
  <div class="content">
    {{-- Agreement Section --}}
    <div class="section">
      <h2 class="section-title">1. THE AGREEMENT</h2>
      <p>
        This agreement is made on <strong>{{ $contract->updated_at->format('jS F Y') }}</strong>
        between <strong>{{ $settings->getCompanyProfile()['name'] }}</strong> (hereinafter referred to as
        <strong>"the Company"</strong>) and <strong>{{ $contract->client->company ?: $contract->client->name }}</strong>
        (hereinafter referred to as <strong>"the Client"</strong>) for the rental of advertising space as detailed below:
      </p>

      {{-- Billboards Table --}}
      <table>
        <thead>
        <tr>
          <th>Billboard Details</th>
          <th>Location</th>
          <th class="text-right">Monthly Rate</th>
        </tr>
        </thead>
        <tbody>
        @foreach($contract->billboards as $billboard)
          <tr>
            <td>
              <div class="font-bold">{{ $billboard->size }}</div>
              <div class="text-sm text-gray">{{ $billboard->code }}</div>
            </td>
            <td>
              <div>{{ $billboard->location->name }}, {{ $billboard->name }}</div>
              <div class="text-sm text-gray">
                {{ $billboard->location->city->name }},
                {{ $billboard->location->state->name }},
                {{ $billboard->location->country->name }}
              </div>
            </td>
            <td class="text-right">
              {{ $contract->currency->symbol }}
              {{ number_format($billboard->pivot->billboard_base_price, 2) }}
            </td>
          </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
          <td colspan="2" class="text-right"><strong>Total Amount:</strong></td>
          <td class="text-right">
            <strong>
              {{ $contract->currency->symbol }}
              {{ number_format($contract->contract_total, 2) }}
            </strong>
          </td>
        </tr>
        @if($contract->contract_discount > 0)
          <tr>
            <td colspan="2" class="text-right">Discount:</td>
            <td class="text-right">
              {{ $contract->currency->symbol }}
              {{ number_format($contract->contract_discount, 2) }}
            </td>
          </tr>
          <tr>
            <td colspan="2" class="text-right"><strong>Final Amount:</strong></td>
            <td class="text-right">
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
        The rental period shall commence on <strong>{{ $contract->start_date->format('jS F Y') }}</strong>
        and end on <strong>{{ $contract->end_date->format('jS F Y') }}</strong>, for a total duration of
        <strong>{{ $contract->start_date->diffInMonths($contract->end_date) }} months</strong>.
      </p>
    </div>

    {{-- Other Sections... --}}
    @include('contracts.templates.partials.terms')
    @include('contracts.templates.partials.maintenance')
    @include('contracts.templates.partials.disputes')

    {{-- Custom Terms if any --}}
    @if($settings->getDocumentSettings()['contract_terms'])
      <div class="section">
        <h2 class="section-title">ADDITIONAL TERMS AND CONDITIONS</h2>
        {!! $settings->getDocumentSettings()['contract_terms'] !!}
      </div>
    @endif

    {{-- Notes if any --}}
    @if($contract->notes)
      <div class="section">
        <h2 class="section-title">SPECIAL NOTES</h2>
        {!! $contract->notes !!}
      </div>
    @endif

    {{-- Signatures --}}
    <div class="signatures">
      <div class="signature-grid">
        {{-- Company Signature --}}
        <div class="signature-box">
          <h4>For and on behalf of:</h4>
          <div class="font-bold mb-2">{{ $settings->getCompanyProfile()['name'] }}</div>

          @if(isset($contract->signatures['company']))
            <img src="{{ $contract->signatures['company'] }}"
                 alt="Company Signature"
                 style="max-height: 2cm; margin: 1cm 0;">
          @else
            <div class="signature-line"></div>
          @endif

          <table class="text-sm" style="border: none;">
            <tr>
              <td style="border: none; padding: 0.25cm 0;">Name:</td>
              <td style="border: none; padding: 0.25cm 0;">_____________________</td>
            </tr>
            <tr>
              <td style="border: none; padding: 0.25cm 0;">Title:</td>
              <td style="border: none; padding: 0.25cm 0;">_____________________</td>
            </tr>
            <tr>
              <td style="border: none; padding: 0.25cm 0;">Date:</td>
              <td style="border: none; padding: 0.25cm 0;">_____________________</td>
            </tr>
          </table>

          <div class="company-details">
            {{ $settings->getCompanyProfile()['address']['street'] }}<br>
            {{ $settings->getCompanyProfile()['address']['city'] }},
            {{ $settings->getCompanyProfile()['address']['state'] }}<br>
            {{ $settings->getCompanyProfile()['address']['country'] }}<br>
            @if($settings->getCompanyProfile()['phone'])
              T: {{ $settings->getCompanyProfile()['phone'] }}<br>
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

        {{-- Client Signature --}}
        <div class="signature-box">
          <h4>For and on behalf of:</h4>
          <div class="font-bold mb-2">{{ $contract->client->company ?: $contract->client->name }}</div>

          @if(isset($contract->signatures['client']))
            <img src="{{ $contract->signatures['client'] }}"
                 alt="Client Signature"
                 style="max-height: 2cm; margin: 1cm 0;">
          @else
            <div class="signature-line"></div>
          @endif

          <table class="text-sm" style="border: none;">
            <tr>
              <td style="border: none; padding: 0.25cm 0;">Name:</td>
              <td style="border: none; padding: 0.25cm 0;">_____________________</td>
            </tr>
            <tr>
              <td style="border: none; padding: 0.25cm 0;">Title:</td>
              <td style="border: none; padding: 0.25cm 0;">_____________________</td>
            </tr>
            <tr>
              <td style="border: none; padding: 0.25cm 0;">Date:</td>
              <td style="border: none; padding: 0.25cm 0;">_____________________</td>
            </tr>
          </table>

          <div class="company-details">
            {{ $contract->client->street }}<br>
            {{ $contract->client->city }}, {{ $contract->client->state }}<br>
            {{ $contract->client->country }}<br>
            @if($contract->client->phone)
              T: {{ $contract->client->phone }}<br>
            @endif
            E: {{ $contract->client->email }}
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
