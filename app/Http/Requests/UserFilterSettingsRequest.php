<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserFilterSettingsRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'age_range' => [
                'sometimes',
                'string',
                'regex:/^(?:1[89]|[2-9][0-9]|100)-(?:1[89]|[2-9][0-9]|100)$/'
            ],
            'is_global_search' => 'sometimes|boolean',
            'search_radius' => 'sometimes|integer|min:1|max:1000',
            'show_me' => 'sometimes|array',
            'show_me.*' => [
                'string',
                Rule::in(['male', 'female', 'm_f', 'm_m', 'f_f'])
            ],
            'cities' => 'string'
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'age_range.regex' => 'Age range must be in format "18-35" with values between 18-100',
            'show_me.*.in' => 'Invalid gender value. Allowed values: male, female, m_f, m_m, f_f',
        ];
    }
}
