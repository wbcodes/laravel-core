<?php

use Illuminate\Support\Str;

/*---------------------------------------{</>}---------------------------------------*/
if (!function_exists('__sitecore_trans')) {

    /**
     * @param $keyword
     * @param  array  $replace
     * @param  null  $locale
     *
     * @return string
     */
    function __sitecore_trans($keyword, $replace = [], $locale = null)
    {
        $keyword = str_replace('_id', '', $keyword);

        $translated_keyword = __("site_core::locale.{$keyword}", $replace, $locale);
        if ($translated_keyword and !Str::contains($translated_keyword, 'site_core::locale.')) {
            $keyword = $translated_keyword;
        }

        if (Str::contains($translated_keyword, 'site_core::locale.')) {
            $keywordArray = explode('.', $translated_keyword);
            $keyword = $keywordArray[count($keywordArray) - 1];
        }

        return str_replace('_', ' ', $keyword);
    }
}

/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('getLocalizedURL')) {

    function getLocalizedURL($slug)
    {
//        return LaravelLocalization::getLocalizedURL(null, $slug);
    }
}
/*---------------------------------------{</>}---------------------------------------*/
if (!function_exists('getTransFileJson')) {

    function getTransFileJson($file = null)
    {
        $lang = $lang ?? app()->getLocale();
        $lang_files = File::files(resource_path("/lang/{$lang}"));
        $trans = [];
        foreach ($lang_files as $f) {
            $filename = pathinfo($f)['filename'];
            $trans[$filename] = trans($filename);
        }
        echo json_encode($trans);
    }
}
/*---------------------------------------{</>}---------------------------------------*/
if (!function_exists('langDir')) {

    function langDir($lang = null)
    {
        return isRTL($lang) ? 'rtl' : 'ltr';
    }
}
/*---------------------------------------{</>}---------------------------------------*/
if (!function_exists('formLang')) {

    function formLang()
    {
        return request()->lang ?? app()->getLocale();
    }
}
/*---------------------------------------{</>}---------------------------------------*/
if (!function_exists('LangRtlArray')) {

    function LangRtlArray()
    {
        return ['ar', 'fa'];
    }
}
/*---------------------------------------{</>}---------------------------------------*/
if (!function_exists('isRTL')) {
    /**
     * @param  null  $lang
     * @return string
     */
    function isRTL($lang = null)
    {
        $lang = $lang ?? app()->getLocale();

        return (in_array($lang, LangRtlArray()));
    }
}
/*---------------------------------------{</>}---------------------------------------*/
if (!function_exists('site_languages')) {

    function site_languages()
    {
        return [
            'ar' => [
                'native' => 'العربية',
                'name'   => 'arabic',
                'slug'   => 'ar',
                'dir'    => 'rtl',
                'flag'   => 'sa',
            ],
            'en' => [
                'native' => 'English',
                'name'   => 'english',
                'slug'   => 'en',
                'dir'    => 'ltr',
                'flag'   => 'us',
            ]
        ];
    }
}
/*---------------------------------------{</>}---------------------------------------*/
