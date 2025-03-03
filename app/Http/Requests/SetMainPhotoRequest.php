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
                'regex:/^\d+,[a-zA-Z0-9]+$/', // Валидируем формат FID SeaweedFS
                function ($attribute, $value, $fail) {

                    $exists = UserImage::where('user_id', auth()->id())
                        ->where('image', $value)
                        ->exists();

                    if (!$exists) {
                        $fail('Указанное фото не найдено среди ваших изображений.');
                    }
                },
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
