<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequestRequest extends FormRequest
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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_type' => ['required', 'in:consument,b2b'],
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'required_if:customer_type,b2b', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'address' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:16'],
            'city' => ['required', 'string', 'max:255'],
            'children_count' => ['nullable', 'integer', 'min:0', 'max:999'],
            'children_ages' => ['nullable', 'string', 'max:255'],
            'desired_skills' => ['nullable', 'array'],
            'desired_skills.*' => ['string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_type' => 'klanttype',
            'company_name' => 'bedrijfsnaam',
            'event_date' => 'evenementdatum',
            'children_count' => 'aantal kinderen',
            'children_ages' => 'leeftijden',
            'desired_skills' => 'gewenste skills',
        ];
    }
}
