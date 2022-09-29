<?php
/*
 * Cache helper functions
 * --------------------------------------------------
 */

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/*-----------------------------------------------------------------------------------*/

if (!function_exists('cache_get_key')) {
    function cache_get_key($module, $slug, $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $module = class_basename(Str::singular($module));
        $slug = Str::slug($slug);

        return "{$locale}:{$module}:{$slug}"; // en:page:slug
    }
}
/*--------------------------------------{</>}----------------------------------------*/

if (!function_exists('cache_get_data')) {
    /**
     * @param $key
     * @param $query
     * @param  null  $ttl
     * @return mixed
     */
    function cache_get_data($key, $query, $ttl = null)
    {
        if (!Cache::has($key)) {
            if ($ttl) {
                // set cache for ttl time
                Cache::remember($key, $ttl, $query);
            } else {
                // set cache forever
                Cache::rememberForever($key, $query);
            }
        }

        // get page from cache
        return Cache::get($key);
    }
}
/*--------------------------------------{</>}----------------------------------------*/

if (!function_exists('cache_forget')) {
    function cache_forget($key)
    {
        // delete this query from cache
        Cache::forget($key);
    }
}
/*----------------------------------------------------------------------------------*/

if (!function_exists('cache_refresh')) {
    /**
     * @param $key
     * @param $query
     * @param  null  $ttl
     * @return mixed
     */
    function cache_refresh($key, $query, $ttl = null)
    {
        // delete this query from cache
        Cache::forget($key);

        // caching again
        return cache_get_data($key, $query, $ttl);
    }
}
/*----------------------------------------------------------------------------------*/

if (!function_exists('cache_get_data_by_slug')) {
    /**
     * @param  null  $module
     * @param        $slug
     * @param  null  $with
     * @param  null  $ttl
     * @return mixed
     */
    function cache_get_data_by_slug($module, $slug, $with = null, $ttl = null)
    {
        $key = cache_get_key($module, $slug);

        // caching project if not existing
        return cache_get_data($key, function () use ($module, $slug, $with) {

            $modelClass = get_cpanel_module($module, 'model');

            $rows = $modelClass::active()->where('slug', $slug);

            if ($with) {
                $rows = $rows->with($with);
            }

            return $rows->firstOrFail();
        }, $ttl);
    }
}
/*--------------------------------------{</>}----------------------------------------*/

if (!function_exists('cache_get_from_sitemap')) {
    function cache_get_from_sitemap($slug, $locale = null)
    {
        $module = "App\\Models\\Sitemap";
        $key = cache_get_key($module, $slug, $locale); // sitemap:tr:news

        // caching project if not existing
        return cache_get_data($key, function () use ($module) {
            $moduleClass = get_cpanel_module($module, 'model');

            return $moduleClass::ofLang()->active()->orderBy('published_at', 'desc')->get();
        }, Carbon\Carbon::now()->addHour());
    }
}
/*--------------------------------------{</>}----------------------------------------*/
