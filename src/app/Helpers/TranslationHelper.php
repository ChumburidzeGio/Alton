<?php

namespace App\Helpers;

use App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class TranslationHelper {

    public static function getFieldTranslation($fieldValue, $fieldDescription = '?', $language = null)
    {
        $translations = json_decode($fieldValue, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $fieldValue;
        }

        if (is_null($language)) {
            $language = App::getLocale();
        }

        // For now, we only support only 2-char language
        // Todo: Limit this to the allowed languages for user/website via languages.general?
        $language = substr($language, 0, 2);

        // Handle fallback locale
        $fallbackLanguage = Config::get('app.fallback_locale');
        $fallbackTranslation = null;

        // Find translation
        foreach ($translations as $translation) {
            if (!isset($translation['@language'], $translation['@value'])) {
                continue;
            }

            if ($translation['@language'] === $language)
                return $translation['@value'];

            if ($translation['@language'] === $fallbackLanguage)
                $fallbackTranslation = $translation['@value'];
        }

        // No translation found at all? Do some error kinda thing
        return 'untranslated:'. $language .':'. ($fallbackTranslation ? $fallbackTranslation : $fieldDescription);
    }
}