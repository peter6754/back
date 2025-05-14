<?php

namespace App\Helpers;

/**
 *
 */
class TranslateResponseHelper
{
    /**
     * @param  array  $data
     * @return array
     */
    public static function response(array $data = []): array
    {
//        if (!request()->user()->language) {
            $data['translation_ru'] = $data['name'];
//            unset($data['name']);
//        }
        return $data;
    }
}
