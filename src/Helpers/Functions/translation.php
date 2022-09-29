<?php

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;

if (!function_exists('getLocalizedURL')) {

    /**
     * @param $slug
     * @return Application|UrlGenerator|string
     */
    function getLocalizedURL($slug)
    {
        return url($slug);
//        return LaravelLocalization::getLocalizedURL(null, $slug);
    }
}
/*---------------------------------------{</>}---------------------------------------*/
if (!function_exists('get_trans_file_as_json')) {

    /**
     * @param  null  $lang
     */
    function get_trans_file_as_json($lang = null)
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
if (!function_exists('lang_dir')) {

    /**
     * @param  null  $lang
     * @return string
     */
    function lang_dir($lang = null)
    {
        return is_rtl($lang) ? 'rtl' : 'ltr';
    }
}
/*---------------------------------------{</>}---------------------------------------*/
if (!function_exists('form_panel_lang')) {

    /**
     * @return mixed|string
     */
    function form_panel_lang()
    {
        return request()->lang ?? app()->getLocale();
    }
}
/*---------------------------------------{</>}---------------------------------------*/
if (!function_exists('rtl_language_array')) {

    /**
     * @return string[]
     */
    function rtl_language_array()
    {
        return ['ar', 'fa'];
    }
}
/*---------------------------------------{</>}---------------------------------------*/
if (!function_exists('is_rtl')) {
    /**
     * @param  null  $lang
     * @return string
     */
    function is_rtl($lang = null)
    {
        $lang = $lang ?? app()->getLocale();

        return (in_array($lang, rtl_language_array()));
    }
}
/*---------------------------------------{</>}---------------------------------------*/
if (!function_exists('site_languages')) {

    /**
     * @return string[][]
     */
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
