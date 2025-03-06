<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoordinatesRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'lat' => 'required|numeric|between:-90,90',
            'long' => 'required|numeric|between:-180,180',
            'formatted_address' => 'nullable|string|max:255'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lat.required' => 'Latitude is required',
            'lat.numeric' => 'Latitude must be a valid number',
            'lat.between' => 'Latitude must be between -90 and 90',
            'long.required' => 'Longitude is required',
            'long.numeric' => 'Longitude must be a valid number',
            'long.between' => 'Longitude must be between -180 and 180',
            'formatted_address.string' => 'Formatted address must be a string',
            'formatted_address.max' => 'Formatted address must not exceed 255 characters'
        ];
    }
}