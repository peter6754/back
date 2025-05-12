<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdvertisementRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'link' => 'nullable|url|max:500',
            'photos.*' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:10240', // 10MB
            'impressions_limit' => 'required|integer|min:0',
            'date_range' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
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
            'title' => 'название',
            'link' => 'ссылка',
            'photos' => 'изображения',
            'photos.*' => 'изображение',
            'impressions_limit' => 'лимит показов',
            'date_range' => 'период показа',
            'is_active' => 'статус активности',
            'order' => 'порядок',
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
            'title.required' => 'Название рекламы обязательно для заполнения.',
            'link.url' => 'Ссылка должна быть корректным URL адресом.',
            'photos.*.image' => 'Файл должен быть изображением.',
            'photos.*.mimes' => 'Изображение должно быть в формате: jpeg, jpg, png, gif или webp.',
            'photos.*.max' => 'Размер изображения не должен превышать 10MB.',
            'impressions_limit.min' => 'Лимит показов не может быть отрицательным.',
        ];
    }
}
