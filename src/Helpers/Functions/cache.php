<?php
/*
 * Cache helper functions
 * --------------------------------------------------
 */

use Illuminate\Support\Facades\Cache;
/*-----------------------------------------------------------------------------------*/

if (!function_exists('cache_get_key')) {
    function cache_get_key($module, $slug, $locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        return "{$locale}:{$module}:{$slug}"; // en:page:slug
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

if (!function_exists('cache_refresh_forever')) {
    function cache_refresh_forever($key, $query)
    {
        // delete this query from cache
        Cache::forget($key);

        // caching again
        Cache::rememberForever($key, function () use ($query) {
            return $query;
        });

        return true;
    }
}
/*----------------------------------------------------------------------------------*/

if (!function_exists('cache_refresh')) {
    function cache_refresh($key, $time, $query)
    {
        // delete this query from cache
        Cache::forget($key);

        // caching again
        Cache::remember($key, $time, function () use ($query) {
            return $query;
        });

        return true;
    }
}
/*----------------------------------------------------------------------------------*/

if (!function_exists('cache_get')) {
    /**
     * @param $key
     * @return mixed
     */
    function cache_get($key)
    {
        return Cache::get($key);
    }
}
/*----------------------------------------------------------------------------------*/

if (!function_exists('cache_get_by_slug')) {
    /**
     * @param        $slug
     * @param  null  $module
     * @param  null  $with
     * @return mixed
     */
    function cache_get_by_slug($module, $slug, $with = null)
    {
        $key = cache_get_key($slug, $module);
        // caching project if not existing
        if (!Cache::has($key)) {
            Cache::rememberForever($key, function () use ($module, $slug, $with) {
                $modelClass = get_cpanel_module($module, 'model');
                if ($with) {
                    return $modelClass::with($with)->active()->where('slug', $slug)->firstOrFail();
                }

                return $modelClass::active()->where('slug', $slug)->firstOrFail();
            });
        }

        // get item from cache by cache key
        return Cache::get($key);
    }
}
/*--------------------------------------{</>}----------------------------------------*/

if (!function_exists('cache_get_from_sitemap')) {
    function cache_get_from_sitemap($module, $slug, $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $slug = "{$locale}:$slug";
        $key = cache_get_key($slug, 'App\\Sitemap'); // sitemap:tr:news
        // caching project if not existing
        if (!Cache::has($key)) {
            Cache::remember($key, Carbon\Carbon::now()->addHour(), function ($module) {
                $moduleClass = get_cpanel_module($module, 'model');

                return $moduleClass::ofLang()->active()->orderBy('published_at', 'desc')->get();
            });
        }

        // get page from cache
        return Cache::get($key);
    }
}
/*--------------------------------------{</>}----------------------------------------*/

if (!function_exists('cache_data_get')) {
    function cache_data_get($key, $time, $query)
    {
        if (!Cache::has($key)) {
            Cache::remember($key, $time, $query);
        }

        // get page from cache
        return Cache::get($key);
    }
}
/*--------------------------------------{</>}----------------------------------------*/
