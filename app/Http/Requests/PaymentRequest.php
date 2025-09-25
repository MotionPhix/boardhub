<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'provider' => 'required|string|in:airtel_money,tnm_mpamba,card,bank_transfer',
            'amount' => 'required|numeric|min:100|max:1000000',
            'phone_number' => 'nullable|string|regex:/^(\+265|265|0)?(77|88|99)\d{7}$/|required_if:provider,airtel_money,tnm_mpamba',
            'booking_id' => 'nullable|integer|exists:bookings,id',
            'client_id' => 'nullable|integer|exists:clients,id',
            'reference' => 'nullable|string|max:100|unique:payments,reference',
            'description' => 'nullable|string|max:255',
            'callback_url' => 'nullable|url',
            'return_url' => 'nullable|url',
            'email' => 'nullable|email|required_if:provider,card,bank_transfer',
            'first_name' => 'nullable|string|max:255|required_if:provider,card,bank_transfer',
            'last_name' => 'nullable|string|max:255|required_if:provider,card,bank_transfer',
            'title' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'phone_number' => 'phone number',
            'booking_id' => 'booking',
            'client_id' => 'client',
            'callback_url' => 'callback URL',
            'return_url' => 'return URL',
            'first_name' => 'first name',
            'last_name' => 'last name',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'provider.in' => 'The selected payment provider is not supported. Available options: Airtel Money, TNM Mpamba, Card, Bank Transfer.',
            'amount.min' => 'Minimum payment amount is MWK 100.',
            'amount.max' => 'Maximum payment amount is MWK 1,000,000.',
            'phone_number.regex' => 'Please enter a valid Malawian phone number (e.g., 0991234567 or +265991234567).',
            'phone_number.required_if' => 'Phone number is required for mobile money payments.',
            'email.required_if' => 'Email is required for card and bank transfer payments.',
            'first_name.required_if' => 'First name is required for card and bank transfer payments.',
            'last_name.required_if' => 'Last name is required for card and bank transfer payments.',
            'reference.unique' => 'This payment reference has already been used.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate phone number based on provider (only for mobile money)
            if (in_array($this->provider, ['airtel_money', 'tnm_mpamba']) && $this->phone_number) {
                $this->validateProviderPhoneNumber($validator);
            }

            // Validate booking belongs to tenant
            if ($this->booking_id) {
                $this->validateBookingTenant($validator);
            }

            // Validate client belongs to tenant
            if ($this->client_id) {
                $this->validateClientTenant($validator);
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Normalize phone number
        if ($this->phone_number) {
            $phone = preg_replace('/[\s\-\+]/', '', $this->phone_number);

            // Add country code if missing
            if (str_starts_with($phone, '0')) {
                $phone = '265' . substr($phone, 1);
            } elseif (!str_starts_with($phone, '265')) {
                $phone = '265' . $phone;
            }

            $this->merge(['phone_number' => $phone]);
        }

        // Generate reference if not provided
        if (!$this->reference) {
            $this->merge(['reference' => 'ADPRO_' . strtoupper(uniqid())]);
        }
    }

    private function validateProviderPhoneNumber($validator)
    {
        $phone = $this->phone_number;
        $provider = $this->provider;

        $validPrefixes = [
            'airtel_money' => ['26599', '26588'],
            'tnm_mpamba' => ['26577'],
        ];

        $prefixes = $validPrefixes[$provider] ?? [];
        $isValid = false;

        foreach ($prefixes as $prefix) {
            if (str_starts_with($phone, $prefix)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            $providerName = $provider === 'airtel_money' ? 'Airtel Money' : 'TNM Mpamba';
            $expectedPrefixes = $provider === 'airtel_money' ? '099 or 088' : '077';

            $validator->errors()->add(
                'phone_number',
                "Phone number must be a valid {$providerName} number starting with {$expectedPrefixes}."
            );
        }
    }

    private function validateBookingTenant($validator)
    {
        if (!app()->bound('tenant')) {
            return;
        }

        $tenant = app('tenant');
        $booking = $tenant->bookings()->find($this->booking_id);

        if (!$booking) {
            $validator->errors()->add('booking_id', 'The selected booking does not exist or does not belong to your account.');
        }
    }

    private function validateClientTenant($validator)
    {
        if (!app()->bound('tenant')) {
            return;
        }

        $tenant = app('tenant');
        $client = $tenant->clients()->find($this->client_id);

        if (!$client) {
            $validator->errors()->add('client_id', 'The selected client does not exist or does not belong to your account.');
        }
    }
}