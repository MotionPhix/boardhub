@extends('contracts.templates.layouts.contract')

@push('styles')
  <style>
    /* Premium template specific styles */
    .watermark {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-45deg);
      font-size: 100pt;
      color: rgba(200, 200, 200, 0.1);
      z-index: -1;
    }

    .premium-header {
      border-bottom: 3pt double #d1d5db;
      margin-bottom: 1cm;
      padding-bottom: 0.5cm;
    }

    .table-of-contents {
      page-break-after: always;
    }

    .table-of-contents ul {
      list-style-type: none;
      padding: 0;
    }

    .table-of-contents li {
      margin-bottom: 0.5cm;
      display: flex;
      align-items: center;
    }

    .table-of-contents .dots {
      flex: 1;
      border-bottom: 1pt dotted #d1d5db;
      margin: 0 0.5cm;
    }
  </style>
@endpush

@section('content')
  <div class="watermark">CONFIDENTIAL</div>

  {{-- Cover Page --}}
  <div class="cover-page">
    <img
      src="{{ public_path('images/logo.png') }}"
      alt="{{ $settings->getCompanyProfile()['name'] }}"
      style="max-height: 3cm; margin-bottom: 2cm;">

    <h1 class="mb-8" style="color: #1a56db; font-family: 'Geist Mono', monospace;">
      ADVERTISING <br> RENTAL AGREEMENT <br> BETWEEN
    </h1>

    <h3 class="mb-2">{{ $settings->getCompanyProfile()['name'] }}</h3>

    <div class="mb-2" style="font-size: 14pt;">AND</div>

    <h3 class="mb-8">{{ $contract->client->company ?: $contract->client->name }}</h3>

    <div style="text-align: center;">
      <div>Contract Reference Number</div>
      <div class="font-bold" style="font-size: 14pt;">{{ $contract->contract_number }}</div>
    </div>
  </div>

  {{-- Table of Contents --}}
{{--  <div class="table-of-contents">--}}
{{--    <h2 class="premium-header">TABLE OF CONTENTS</h2>--}}
{{--    <ul>--}}
{{--      <li>--}}
{{--        <span>1. Definitions and Interpretation</span>--}}
{{--        <span class="dots"></span>--}}
{{--        <span>1</span>--}}
{{--      </li>--}}
{{--      <li>--}}
{{--        <span>2. Agreement Terms</span>--}}
{{--        <span class="dots"></span>--}}
{{--        <span>2</span>--}}
{{--      </li>--}}
{{--      <li>--}}
{{--        <span>3. Financial Terms</span>--}}
{{--        <span class="dots"></span>--}}
{{--        <span>3</span>--}}
{{--      </li>--}}
{{--      <li>--}}
{{--        <span>4. Maintenance and Service Level</span>--}}
{{--        <span class="dots"></span>--}}
{{--        <span>4</span>--}}
{{--      </li>--}}
{{--      <li>--}}
{{--        <span>5. Intellectual Property Rights</span>--}}
{{--        <span class="dots"></span>--}}
{{--        <span>5</span>--}}
{{--      </li>--}}
{{--      <li>--}}
{{--        <span>6. Confidentiality</span>--}}
{{--        <span class="dots"></span>--}}
{{--        <span>6</span>--}}
{{--      </li>--}}
{{--      <li>--}}
{{--        <span>7. Liability and Indemnification</span>--}}
{{--        <span class="dots"></span>--}}
{{--        <span>7</span>--}}
{{--      </li>--}}
{{--      <li>--}}
{{--        <span>8. Terms and Termination</span>--}}
{{--        <span class="dots"></span>--}}
{{--        <span>8</span>--}}
{{--      </li>--}}
{{--      <li>--}}
{{--        <span>9. Force Majeure</span>--}}
{{--        <span class="dots"></span>--}}
{{--        <span>9</span>--}}
{{--      </li>--}}
{{--      <li>--}}
{{--        <span>10. Governing Law and Jurisdiction</span>--}}
{{--        <span class="dots"></span>--}}
{{--        <span>10</span>--}}
{{--      </li>--}}
{{--    </ul>--}}
{{--  </div>--}}

  {{-- Definitions Section --}}
{{--  @include('contracts.templates.premium.sections.definitions')--}}

  {{-- Agreement Terms --}}
  <div class="section">
    <h2 class="section-title">1. AGREEMENT TERMS</h2>

    <p>
      This <strong>Agreement</strong> (the "Agreement") is made and entered into on
      <strong>{{ $contract->updated_at->format('jS F Y') }}</strong> between:
    </p>

    <div class="party-details mb-4">
      <strong>{{ $settings->getCompanyProfile()['name'] }}</strong>,
      a company registered under the laws of {{ $settings->getCompanyProfile()['address']['country'] }},
      with registration number {{ $settings->getCompanyProfile()['registration_number'] }},
      having its registered office at {{ $settings->getCompanyProfile()['address']['street'] }},
      {{ $settings->getCompanyProfile()['address']['city'] }},
      {{ $settings->getCompanyProfile()['address']['state'] }},
      {{ $settings->getCompanyProfile()['address']['country'] }}
      (hereinafter referred to as <strong>"the Company"</strong>)
    </div>

    <div class="party-details mb-4">
      <strong>AND</strong>
    </div>

    <div class="party-details mb-4">
      <strong>{{ $contract->client->company ?: $contract->client->name }}</strong>,
      @if($contract->client->company)
        a company registered under the laws of {{ $contract->client->country }},
      @endif
      having its principal place of business at {{ $contract->client->street }},
      {{ $contract->client->city }}, {{ $contract->client->state }},
      {{ $contract->client->country }}
      (hereinafter referred to as <strong>"the Client"</strong>)
    </div>

    <div class="agreement-details">
      <p>
        WHEREAS, the Company owns and operates premium advertising spaces and billboards;
      </p>
      <p>
        AND WHEREAS, the Client wishes to rent such advertising spaces for the purpose of displaying their advertising content;
      </p>
      <p>
        NOW, THEREFORE, in consideration of the mutual covenants and agreements contained herein,
        the parties agree as follows:
      </p>
    </div>
  </div>

  {{-- Financial Terms with Enhanced Table --}}
  <div class="section">
    <h2 class="section-title">3. FINANCIAL TERMS</h2>

    <h3 class="mb-2">3.1 Advertising Space Details</h3>
    <table>
      <thead>
      <tr>
        <th style="width: 25%;">Billboard Code</th>
        <th style="width: 35%;">Location Details</th>
        <th style="width: 20%;" class="text-right">Monthly Rate</th>
      </tr>
      </thead>
      <tbody>
      @foreach($contract->billboards as $billboard)
        <tr>
          <td>
            <div class="font-bold">
              {{ $billboard->code }}
            </div>

            <div class="text-gray">{{ $billboard->size }}</div>
          </td>

          <td>
            <div>
              {{ $billboard->name }}, {{ $billboard->location->name }}
            </div>

            <div class="text-gray">
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
        <td colspan="2" class="text-right"><strong>Subtotal:</strong></td>
        <td class="text-right">
          <strong>
            {{ $contract->currency->symbol }}
            {{ number_format($contract->contract_total, 2) }}
          </strong>
        </td>
      </tr>
      @if($contract->contract_discount > 0)
        <tr>
          <td colspan="2" class="text-right">Volume Discount:</td>
          <td class="text-right">
            {{ $contract->currency->symbol }}
            {{ number_format($contract->contract_discount, 2) }}
          </td>
        </tr>
      @endif
{{--      @if($contract->tax_amount > 0)--}}
{{--        <tr>--}}
{{--          <td colspan="2" class="text-right">Tax ({{ $contract->tax_rate }}%):</td>--}}
{{--          <td class="text-right">--}}
{{--            {{ $contract->currency->symbol }}--}}
{{--            {{ number_format($contract->tax_amount, 2) }}--}}
{{--          </td>--}}
{{--        </tr>--}}
{{--      @endif--}}
      <tr>
        <td colspan="2" class="text-right"><strong>Total Amount:</strong></td>
        <td class="text-right">
          <strong>
            {{ $contract->currency->symbol }}
            {{ number_format($contract->contract_final_amount, 2) }}
          </strong>
        </td>
      </tr>
      </tfoot>
    </table>

    {{-- Payment Schedule --}}
    @include('contracts.templates.premium.sections.payment-schedule')
  </div>

  {{-- Include other premium sections --}}
  @include('contracts.templates.premium.sections.maintenance')
  @include('contracts.templates.premium.sections.intellectual-property')
  @include('contracts.templates.premium.sections.confidentiality')
  @include('contracts.templates.premium.sections.liability')
  @include('contracts.templates.premium.sections.termination')
  @include('contracts.templates.premium.sections.force-majeure')
{{--  @include('contracts.templates.premium.sections.governing-law')--}}

  {{-- Special Terms if any --}}
{{--  @if($contract->special_terms)--}}
{{--    <div class="section">--}}
{{--      <h2 class="section-title">SPECIAL TERMS AND CONDITIONS</h2>--}}
{{--      {!! $contract->special_terms !!}--}}
{{--    </div>--}}
{{--  @endif--}}

  {{-- Signature Section with Professional Layout --}}
  <div class="signatures">
    <h2 class="section-title">IN WITNESS WHEREOF</h2>
    <p class="mb-4">
      The parties hereto have executed this Premium Advertising Agreement as of the date first above written.
    </p>

    <div class="signature-grid">
      {{-- Company Signature Box --}}
      <div class="signature-box">
        <div class="mb-2">For and on behalf of:</div>
        <h4 class="font-bold mb-4">
          {{ $settings->getCompanyProfile()['name'] }} <strong>("the Company")</strong>
        </h4>

        @if(isset($contract->signatures['company']))
          <img src="{{ $contract->signatures['company'] }}"
               alt="Company Signature"
               style="max-height: 2cm; margin: 1cm 0;">
        @else
          <div class="signature-line"></div>
        @endif

        <table class="text-sm" style="border: none;">
          <tr>
            <td style="border: none; padding: 0.25cm 0; width: 30%;">Name:</td>
            <td style="border: none; padding: 0.25cm 0;">_____________________</td>
          </tr>
          <tr>
            <td style="border: none; padding: 0.25cm 0;">Position:</td>
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

      {{-- Client Signature Box --}}
      <div class="signature-box">
        <div class="mb-2">For and on behalf of:</div>
        <h4 class="font-bold mb-4">
          {{ $contract->client->company ?: $contract->client->name }} <strong>("the Client")</strong>
        </h4>

        @if(isset($contract->signatures['client']))
          <img src="{{ $contract->signatures['client'] }}"
               alt="Client Signature"
               style="max-height: 2cm; margin: 1cm 0;">
        @else
          <div class="signature-line"></div>
        @endif

        <table class="text-sm" style="border: none;">
          <tr>
            <td style="border: none; padding: 0.25cm 0; width: 30%;">Name:</td>
            <td style="border: none; padding: 0.25cm 0;">_____________________</td>
          </tr>
          <tr>
            <td style="border: none; padding: 0.25cm 0;">Position:</td>
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

  {{-- Witnesses Section --}}
  <div class="section mt-8">
    <div class="signature-grid">
      <div>
        <h4 class="mb-4">WITNESS 1</h4>
        <div class="signature-line"></div>
        <table class="text-sm" style="border: none;">
          <tr>
            <td style="border: none; padding: 0.25cm 0; width: 30%;">Name:</td>
            <td style="border: none; padding: 0.25cm 0;">_____________________</td>
          </tr>
          <tr>
            <td style="border: none; padding: 0.25cm 0;">ID/Passport:</td>
            <td style="border: none; padding: 0.25cm 0;">_____________________</td>
          </tr>
        </table>
      </div>

      <div>
        <h4 class="mb-4">WITNESS 2</h4>
        <div class="signature-line"></div>
        <table class="text-sm" style="border: none;">
          <tr>
            <td style="border: none; padding: 0.25cm 0; width: 30%;">Name:</td>
            <td style="border: none; padding: 0.25cm 0;">_____________________</td>
          </tr>
          <tr>
            <td style="border: none; padding: 0.25cm 0;">ID/Passport:</td>
            <td style="border: none; padding: 0.25cm 0;">_____________________</td>
          </tr>
        </table>
      </div>
    </div>
  </div>
@endsection
