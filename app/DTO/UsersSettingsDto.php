<?php

namespace App\DTO;

use Spatie\LaravelData\Data;

class UsersSettingsDto extends Data
{
    public static function tokenRequest($request, array $validator = []): array
    {
        // Validator rules build
        $requestParams = array_merge([
            'token' => 'string|required'
        ], $validator);

        // Validate
        $request->validate($requestParams);

        $result = [];
        foreach ($requestParams as $key => $value) {
            $inputValue = $request->input($key);
            if ($inputValue !== null) {
                $result[$key] = $inputValue;
            }
        }

        return $result;
    }
}
