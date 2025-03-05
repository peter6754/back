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
            'photo' => 'required|array',
            'photo.*' => [
                'required',
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
            'photo.required' => 'Необходимо загрузить фото',
            'photo.array' => 'Неверный формат данных',
            'photo.*.required' => 'Фото обязательно для загрузки',
            'photo.*.file' => 'Файл должен быть файлом',
            'photo.*.image' => 'Файл должен быть изображением',
            'photo.*.mimes' => 'Поддерживаемые форматы: jpeg, jpg, png, gif, webp',
            'photo.*.max' => 'Размер файла не должен превышать 10MB',
        ];
    }

}
