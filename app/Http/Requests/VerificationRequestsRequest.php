<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerificationRequestsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // 'name' => 'required|min:5|max:255'
            'preset_reason' => 'nullable|string',
            'custom_reason' => 'nullable|string|min:5|max:255',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            //
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            //
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $preset = trim($this->input('preset_reason'));
            $custom = trim($this->input('custom_reason'));

            if ($preset && $custom) {
                $validator->errors()->add('preset_reason', 'Укажите только одну причину: из списка или свою.');
                $validator->errors()->add('custom_reason', 'Укажите только одну причину: из списка или свою.');
            }
        });
    }
}
