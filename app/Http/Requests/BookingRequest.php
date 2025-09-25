<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'billboard_id' => ['required', 'exists:billboards,id'],
            'start_date' => ['required', 'date', 'after:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'requested_price' => ['nullable', 'numeric', 'min:0'],
            'campaign_details' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'billboard_id.required' => 'Please select a billboard to book.',
            'billboard_id.exists' => 'The selected billboard is not available.',
            'start_date.required' => 'Please specify a start date for your campaign.',
            'start_date.after' => 'Start date must be in the future.',
            'end_date.required' => 'Please specify an end date for your campaign.',
            'end_date.after' => 'End date must be after the start date.',
            'requested_price.numeric' => 'Price must be a valid number.',
            'requested_price.min' => 'Price cannot be negative.',
            'campaign_details.max' => 'Campaign details cannot exceed 1000 characters.',
        ];
    }
}
