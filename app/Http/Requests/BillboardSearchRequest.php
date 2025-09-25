<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BillboardSearchRequest extends FormRequest
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
            // Basic search criteria
            'query' => 'nullable|string|max:255',
            'locations' => 'nullable|array',
            'locations.*' => 'string|max:255',
            'sizes' => 'nullable|array',
            'sizes.*' => 'string|in:small,medium,large,extra_large',

            // Price filters
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0|gte:price_min',
            'budget' => 'nullable|numeric|min:0',
            'duration' => 'nullable|integer|min:1|max:365',

            // Date filters
            'available_from' => 'nullable|date|after_or_equal:today',
            'available_until' => 'nullable|date|after:available_from',

            // Geographic filters
            'coordinates' => 'nullable|array',
            'coordinates.lat' => 'required_with:coordinates|numeric|between:-90,90',
            'coordinates.lng' => 'required_with:coordinates|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100',

            // Area preferences
            'area_types' => 'nullable|array',
            'area_types.*' => 'string|in:commercial,residential,highway,mixed,industrial',
            'preferred_area_types' => 'nullable|array',
            'preferred_area_types.*' => 'string|in:commercial,residential,highway,mixed,industrial',

            // Performance filters
            'min_occupancy_rate' => 'nullable|numeric|min:0|max:100',
            'min_performance_score' => 'nullable|numeric|min:0|max:100',
            'exclude_low_performers' => 'nullable|boolean',

            // Demographic targeting
            'target_demographics' => 'nullable|array',
            'target_demographics.age_groups' => 'nullable|array',
            'target_demographics.age_groups.*' => 'string|in:18-24,25-34,35-44,45-54,55-64,65+',
            'target_demographics.income_levels' => 'nullable|array',
            'target_demographics.income_levels.*' => 'string|in:low,medium,high',
            'target_demographics.interests' => 'nullable|array',
            'target_demographics.interests.*' => 'string|max:100',

            // Campaign preferences
            'campaign_type' => 'nullable|string|in:brand_awareness,product_launch,event_promotion,seasonal',
            'campaign_industry' => 'nullable|string|max:100',
            'campaign_goals' => 'nullable|array',
            'campaign_goals.*' => 'string|in:reach,engagement,conversions,brand_recall',

            // AI preferences
            'use_ai_recommendations' => 'nullable|boolean',
            'ai_weight_performance' => 'nullable|numeric|min:0|max:1',
            'ai_weight_location' => 'nullable|numeric|min:0|max:1',
            'ai_weight_value' => 'nullable|numeric|min:0|max:1',
            'ai_weight_availability' => 'nullable|numeric|min:0|max:1',

            // Sorting and pagination
            'sort_by' => 'nullable|string|in:price,performance,ai_score,popularity,availability,distance',
            'sort_order' => 'nullable|string|in:asc,desc',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',

            // Advanced options
            'include_alternatives' => 'nullable|boolean',
            'include_market_insights' => 'nullable|boolean',
            'include_pricing_suggestions' => 'nullable|boolean',
            'exclude_billboard_ids' => 'nullable|array',
            'exclude_billboard_ids.*' => 'integer',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'coordinates.lat' => 'latitude',
            'coordinates.lng' => 'longitude',
            'target_demographics.age_groups' => 'target age groups',
            'target_demographics.income_levels' => 'target income levels',
            'target_demographics.interests' => 'target interests',
            'ai_weight_performance' => 'AI performance weight',
            'ai_weight_location' => 'AI location weight',
            'ai_weight_value' => 'AI value weight',
            'ai_weight_availability' => 'AI availability weight',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'price_max.gte' => 'Maximum price must be greater than or equal to minimum price.',
            'available_until.after' => 'End date must be after start date.',
            'coordinates.lat.between' => 'Latitude must be between -90 and 90.',
            'coordinates.lng.between' => 'Longitude must be between -180 and 180.',
            'radius.max' => 'Search radius cannot exceed 100 kilometers.',
            'duration.max' => 'Campaign duration cannot exceed 365 days.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate AI weights sum to reasonable total
            $aiWeights = array_filter([
                $this->ai_weight_performance,
                $this->ai_weight_location,
                $this->ai_weight_value,
                $this->ai_weight_availability,
            ]);

            if (!empty($aiWeights) && array_sum($aiWeights) > 1.0) {
                $validator->errors()->add('ai_weights', 'AI weights cannot sum to more than 1.0');
            }

            // Validate budget vs duration makes sense
            if ($this->budget && $this->duration && $this->price_min) {
                $minTotalCost = $this->price_min * $this->duration;
                if ($this->budget < $minTotalCost) {
                    $validator->errors()->add('budget', 'Budget is insufficient for the minimum price and duration specified.');
                }
            }
        });
    }

    /**
     * Get validated data with intelligent defaults
     */
    public function getSearchCriteria(): array
    {
        $validated = $this->validated();

        // Apply intelligent defaults
        $validated['use_ai_recommendations'] = $validated['use_ai_recommendations'] ?? true;
        $validated['sort_by'] = $validated['sort_by'] ?? 'ai_score';
        $validated['sort_order'] = $validated['sort_order'] ?? 'desc';
        $validated['per_page'] = $validated['per_page'] ?? 20;
        $validated['page'] = $validated['page'] ?? 1;

        // Set default AI weights if not provided
        if ($validated['use_ai_recommendations'] && !isset($validated['ai_weight_performance'])) {
            $validated['ai_weight_performance'] = 0.4;
            $validated['ai_weight_location'] = 0.3;
            $validated['ai_weight_value'] = 0.2;
            $validated['ai_weight_availability'] = 0.1;
        }

        // Default radius for geographic search
        if (isset($validated['coordinates']) && !isset($validated['radius'])) {
            $validated['radius'] = 10; // 10km default radius
        }

        return $validated;
    }
}