<?php

namespace App\Http\Requests;

use App\Helpers\UserInformationTranslator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class updateUserInfoRegistrationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:60',
            'gender' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('genders')))],
            'sexual_orientation' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('orientations')))],
            'email' => 'sometimes|email|unique:users,email,' . $this->user()->id,
            'registration_screen' => 'sometimes|integer',
            'birth_date' => 'sometimes|date',
            'username' => 'sometimes|string|max:60|unique:users,username,' . $this->user()->id,
            'family_status' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('family_statuses')))],
            'relationship_preference_id' => 'sometimes|exists:relationship_preferences,id',
            'show_my_orientation' => 'sometimes|boolean',
            'show_my_gender' => 'sometimes|boolean',
            'show_me' => 'sometimes|array',
            'show_me.*' => ['string', Rule::in([
                UserInformationTranslator::GENDER_MALE,
                UserInformationTranslator::GENDER_FEMALE,
                UserInformationTranslator::GENDER_MF,
                UserInformationTranslator::GENDER_MM,
                UserInformationTranslator::GENDER_FF
            ])],
            'interests' => 'sometimes|array|min:3|max:5',
            'interests.*' => 'integer|exists:interests,id',
        ];
    }

    public function messages(): array
    {
        return [
            'birth_date.date' => 'Неверный формат даты рождения',
            'email.unique' => 'Этот email уже используется',
            'username.unique' => 'Это имя пользователя уже занято',
            'relationship_preference_id.exists' => 'Указанное предпочтение не существует',
            'interests.array' => 'Интересы должны быть переданы в виде массива',
            'interests.min' => 'Необходимо выбрать минимум 3 интереса',
            'interests.max' => 'Можно выбрать максимум 5 интересов',
            'interests.*.integer' => 'ID интереса должен быть числом',
            'interests.*.exists' => 'Выбранный интерес не существует',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
