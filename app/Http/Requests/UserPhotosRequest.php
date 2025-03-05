<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserPhotosRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => 'required|array|min:1',
            'photo.*' => [
                'file',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:10240',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'photo.required' => 'Необходимо загрузить хотя бы одно фото',
            'photo.array' => 'Необходимо загрузить хотя бы одно фото',
            'photo.min' => 'Необходимо загрузить хотя бы одно фото',
            'photo.*.file' => 'Каждый файл должен быть файлом',
            'photo.*.image' => 'Каждый файл должен быть изображением',
            'photo.*.mimes' => 'Поддерживаемые форматы: jpeg, jpg, png, gif, webp',
            'photo.*.max' => 'Размер файла не должен превышать 10MB',
        ];
    }

}
