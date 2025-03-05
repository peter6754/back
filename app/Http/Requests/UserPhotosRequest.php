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
            'photo' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:10240',
            ],
            'photo.*' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:10240',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Проверяем, есть ли в запросе поле 'photo' и является ли оно массивом
            // или одиночным файлом. Если нет, добавляем ошибку.
            if (! $this->hasFile('photo') || (is_array($this->file('photo')) && count($this->file('photo')) === 0)) {
                $validator->errors()->add('photo', 'Необходимо загрузить фото');
            }
        });
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'photo.file' => 'Файл должен быть файлом',
            'photo.image' => 'Файл должен быть изображением',
            'photo.mimes' => 'Поддерживаемые форматы: jpeg, jpg, png, gif, webp',
            'photo.max' => 'Размер файла не должен превышать 10MB',

            'photo.*.file' => 'Каждый файл должен быть файлом',
            'photo.*.image' => 'Каждый файл должен быть изображением',
            'photo.*.mimes' => 'Поддерживаемые форматы: jpeg, jpg, png, gif, webp',
            'photo.*.max' => 'Размер файла не должен превышать 10MB',
        ];
    }

}
