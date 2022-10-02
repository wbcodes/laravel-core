<?php
/*
 * General helper functions
 * --------------------------------------------------
 */

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

/*-----------------------------------------------------------------------------------*/

if (!function_exists('version')) {
    /**
     * get file version using last modified time
     * @param $file
     * @return int|string
     */
    function version($file)
    {
        return File::exists($file) ? File::lastModified($file) : '1.0.0';
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('asset_v')) {
    /**
     * get asset file path with version
     * @param $path
     * @param  string  $prefix
     * @return string
     */
    function asset_v($path, $prefix = 'v')
    {
        $url = asset($path);
        $version = version(public_path($path));

        return "{$url}?{$prefix}={$version}";
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('not_authorize')) {
    /**
     * @param  bool  $is_json
     * @return Application|Factory|View|JsonResponse
     */
    function not_authorize($is_json = true)
    {
        if ($is_json) {
            return response()->json(__('wbcore::locale.errors.messages.unauthorized'), 401);
        }

        return view('wbcore::errors.401');
    }
}
/*---------------------------------------{</>}---------------------------------------*/