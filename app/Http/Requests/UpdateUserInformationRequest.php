<?php

namespace App\Http\Requests;

use App\Helpers\UserInformationTranslator;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserInformationRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'interests' => 'sometimes|array|min:3|max:5',
            'interests.*' => 'integer|exists:interests,id',
            'relationship_preference_id' => 'sometimes|integer|exists:relationship_preferences,id',
            'family_status' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('family_statuses')))],
            'gender' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('genders')))],
            'sexual_orientation' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('orientations')))],
            'bio' => 'sometimes|string|max:500',
            'zodiac_sign' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('zodiac_signs')))],
            'education' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('education')))],
            'family' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('family')))],
            'communication' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('communication')))],
            'love_language' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('love_language')))],
            'alcohole' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('alcohol')))],
            'smoking' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('smoking')))],
            'sport' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('sport')))],
            'food' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('food')))],
            'social_network' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('social_network')))],
            'sleep' => ['sometimes', Rule::in(array_keys(UserInformationTranslator::getTranslationsForCategory('sleep')))],
            'educational_institution' => 'sometimes|string|max:100',
            'role' => 'sometimes|string|max:50',
            'company' => 'sometimes|string|max:50',
            'show_my_gender' => 'sometimes|boolean',
            'show_my_orientation' => 'sometimes|boolean',
            'registration_screen' => 'sometimes|string|nullable|max:50',
            'birth_date' => 'sometimes|date|before:today',
            'show_me' => 'sometimes|array',
            'show_me.*' => ['string', Rule::in([
                UserInformationTranslator::GENDER_MALE,
                UserInformationTranslator::GENDER_FEMALE,
                UserInformationTranslator::GENDER_MF,
                UserInformationTranslator::GENDER_MM,
                UserInformationTranslator::GENDER_FF
            ])],
            'email' => 'sometimes|email|max:100|unique:users,email,' . $this->user->id,
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [

            'interests.array' => 'Интересы должны быть переданы в виде массива',
            'interests.min' => 'Необходимо выбрать минимум 3 интереса',
            'interests.max' => 'Можно выбрать максимум 5 интересов',
            'interests.*.integer' => 'ID интереса должен быть числом',
            'interests.*.exists' => 'Выбранный интерес не существует',

            'relationship_preference_id.integer' => 'ID предпочтения должен быть числом',
            'relationship_preference_id.exists' => 'Выбранное предпочтение не существует',

            'family_status.in' => 'Выбранный семейный статус недопустим',

            'gender.in' => 'Выбранный пол недопустим',

            'sexual_orientation.in' => 'Выбранная сексуальная ориентация недопустима',

            'bio.string' => 'Биография должна быть текстом',
            'bio.max' => 'Биография не должна превышать 500 символов',

            'zodiac_sign.in' => 'Выбранный знак зодиака недопустим',

            'education.in' => 'Выбранный уровень образования недопустим',

            'family.in' => 'Выбранное отношение к семье недопустимо',

            'communication.in' => 'Выбранный стиль общения недопустим',

            'love_language.in' => 'Выбранный язык любви недопустим',

            'alcohole.in' => 'Выбранное отношение к алкоголю недопустимо',

            'smoking.in' => 'Выбранное отношение к курению недопустимо',

            'sport.in' => 'Выбранное отношение к спорту недопустимо',

            'food.in' => 'Выбранные пищевые предпочтения недопустимы',

            'social_network.in' => 'Выбранное отношение к социальным сетям недопустимо',

            'sleep.in' => 'Выбранный режим сна недопустим',

            'educational_institution.string' => 'Название учебного заведения должно быть текстом',
            'educational_institution.max' => 'Название учебного заведения не должно превышать 100 символов',

            'role.string' => 'Должность должна быть текстом',
            'role.max' => 'Должность не должна превышать 50 символов',

            'company.string' => 'Название компании должно быть текстом',
            'company.max' => 'Название компании не должно превышать 50 символов',

            'show_my_gender.boolean' => 'Показ пола должен быть true или false',
            'show_my_orientation.boolean' => 'Показ ориентации должен быть true или false',

            'registration_screen.string' => 'Экран регистрации должен быть текстом',
            'registration_screen.max' => 'Экран регистрации не должен превышать 50 символов',

            'birth_date.date' => 'Дата рождения должна быть корректной датой',
            'birth_date.before' => 'Дата рождения должна быть в прошлом',

            'show_me.array' => 'Предпочтения должны быть переданы в виде массива',
            'show_me.*.string' => 'Предпочтение должно быть текстом',
            'show_me.*.in' => 'Выбранное предпочтение недопустимо',

            'email.email' => 'Email должен быть корректным адресом электронной почты',
            'email.max' => 'Email не должен превышать 100 символов',
            'email.unique' => 'Этот email уже используется другим пользователем',
        ];
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return [
            'interests' => 'интересы',
            'interests.*' => 'интерес',
            'relationship_preference_id' => 'предпочтение в отношениях',
            'family_status' => 'семейный статус',
            'gender' => 'пол',
            'sexual_orientation' => 'сексуальная ориентация',
            'bio' => 'биография',
            'zodiac_sign' => 'знак зодиака',
            'education' => 'образование',
            'family' => 'отношение к семье',
            'communication' => 'стиль общения',
            'love_language' => 'язык любви',
            'alcohole' => 'отношение к алкоголю',
            'smoking' => 'отношение к курению',
            'sport' => 'отношение к спорту',
            'food' => 'пищевые предпочтения',
            'social_network' => 'отношение к социальным сетям',
            'sleep' => 'режим сна',
            'educational_institution' => 'учебное заведение',
            'role' => 'должность',
            'company' => 'компания',
            'show_my_gender' => 'показ пола',
            'show_my_orientation' => 'показ ориентации',
            'registration_screen' => 'экран регистрации',
            'birth_date' => 'дата рождения',
            'show_me' => 'кого показывать',
            'show_me.*' => 'предпочтение',
            'email' => 'электронная почта',
        ];
    }

    /**
     * @param Validator|\Illuminate\Contracts\Validation\Validator $validator
     * @return mixed
     */
    protected function failedValidation(Validator|\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Ошибка валидации данных',
                'errors' => $validator->errors()
            ], 422)
        );
    }

}
