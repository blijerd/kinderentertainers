<?php

namespace App\Http\Requests;

use App\Models\Entertainer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'request_type' => ['required', Rule::in(['specific', 'general'])],
            'skill_id' => [
                'nullable',
                'required_if:request_type,general',
                Rule::exists('skills', 'id')->where('active', true),
            ],
            'entertainer_id' => [
                'nullable',
                'prohibited_if:request_type,general',
                Rule::exists('entertainers', 'id')->where('active', true),
            ],
            'customer_type' => ['required', 'in:consument,b2b'],
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'required_if:customer_type,b2b', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'event_date' => ['required', 'date', 'after:today'],
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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $entertainer = $this->route('entertainer');

                if ($this->input('request_type') === 'specific' && ! $entertainer instanceof Entertainer) {
                    $validator->errors()->add('entertainer', 'Kies een entertainer voor een gerichte aanvraag.');

                    return;
                }

                if ($entertainer instanceof Entertainer && ! $entertainer->active) {
                    $validator->errors()->add('entertainer', 'Deze entertainer is niet actief.');
                }

                $desiredSkills = $this->input('desired_skills', []);

                if (! is_array($desiredSkills) || $desiredSkills === []) {
                    return;
                }

                if (! $entertainer instanceof Entertainer) {
                    return;
                }

                $allowedSkills = collect($entertainer->skills()->pluck('name'))->all();
                $invalidSkills = array_diff($desiredSkills, $allowedSkills);

                if ($invalidSkills !== []) {
                    $validator->errors()->add('desired_skills', 'Kies alleen skills die bij deze entertainer horen.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_type' => 'klanttype',
            'request_type' => 'aanvraagtype',
            'skill_id' => 'skill',
            'entertainer_id' => 'entertainer',
            'company_name' => 'bedrijfsnaam',
            'event_date' => 'evenementdatum',
            'children_count' => 'aantal kinderen',
            'children_ages' => 'leeftijden',
            'desired_skills' => 'gewenste skills',
            'end_time' => 'eindtijd',
            'start_time' => 'starttijd',
        ];
    }
}
