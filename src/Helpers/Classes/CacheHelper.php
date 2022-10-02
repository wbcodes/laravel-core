<?php

namespace Wbcodes\Core\Helpers\Classes;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CacheHelper
{
    /**
     * @param $slug
     * @param $model
     * @return mixed
     */
    public static function getBySlug($slug, $model)
    {
        $key = self::getKey($slug, $model);

        // caching project if not existing
        if (!Cache::has($key)) {
            Cache::rememberForever($key, function () use ($model, $slug) {
                return $model::active()->where('slug', $slug)->firstOrFail();
            });
        }

        // get page from cache
        return Cache::get($key);
    }

    /**
     * @param $key
     * @param $callback
     * @return mixed
     */
    public static function Refresh($key, $callback)
    {
        // delete this query from cache
        if (Cache::has($key)) {

            // delete this query from cache
            Cache::forget($key);

            // caching again
            Cache::rememberForever($key, $callback);

            return true;
        }

        return false;
    }

    /**
     * @param $slug
     * @param $className
     * @return string
     */
    public static function getKey($slug, $className = null)
    {
        $name = Str::lower(class_basename($className));

        $prefix = config("wb_cache.prefix");

        return "{$prefix}:{$name}:{$slug}"; // laravel:page:slug
    }
}