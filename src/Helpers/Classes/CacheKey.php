<?php


namespace Wbcodes\Core\Helpers\Classes;


use Illuminate\Support\Facades\Auth;

class CacheKey
{

    /**
     * @param  null  $authId
     * @return string
     */
    public static function userPermissions($authId = null): string
    {
        $authId = self::getAuthId($authId);

        return "users:{$authId}:permissions";
    }

    /**
     * @param  null  $authId
     */
    private static function getAuthId($authId = null)
    {
        $authId = $authId ?? Auth::id();

        return is_numeric($authId) ? $authId : optional($authId)->id;
    }
}