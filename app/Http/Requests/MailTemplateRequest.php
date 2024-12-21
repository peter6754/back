<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MailTemplateRequest extends FormRequest
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
                'name' => 'required|string|max:255|unique:mail_templates,name,' . $this->id,
                'subject' => 'required|string|max:255',
                'html_body' => 'required|string',
                'text_body' => 'nullable|string',
                'variables' => 'nullable|json',
                'is_active' => 'boolean'
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
            'name.required' => 'Название обязательно для заполнения',
            'name.unique' => 'Шаблон с таким названием уже существует',
            'subject.required' => 'Тема письма обязательна для заполнения',
            'html_body.required' => 'HTML тело письма обязательно для заполнения',
            'variables.json' => 'Переменные должны быть в формате JSON'
        ];
    }
}
