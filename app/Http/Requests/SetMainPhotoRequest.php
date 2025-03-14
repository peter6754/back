<?php

namespace App\Http\Requests;

use App\Models\UserImage;
use Illuminate\Foundation\Http\FormRequest;

class SetMainPhotoRequest extends FormRequest
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
            'fid' => [
                'required',
                'string',
                'regex:/^\d+,[a-fA-F0-9]{8,32}$/',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'fid.required' => 'Необходимо указать FID фотографии',
            'fid.string' => 'FID должен быть строкой',
            'fid.regex' => 'Неверный формат FID',
        ];
    }

}
