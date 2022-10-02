<?php

use Wbcodes\Core\Helpers\Classes\PermissionHelper;
use Wbcodes\CPanel\Models\Permission;

/*
 * Permission helper functions
 * --------------------------------------------------
 */

if (!function_exists('is_can')) {

    /**
     * @param $modulePermissionName
     * @param  null  $permission_name
     * @return bool
     */
    function is_can($modulePermissionName, $permission_name = null)
    {
        return PermissionHelper::can($modulePermissionName, $permission_name);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('is_can_create')) {
    /**
     * @param $permissionName
     * @return bool
     */
    function is_can_create($permissionName = null)
    {
        return PermissionHelper::canCreate($permissionName);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('is_can_edit')) {
    /**
     * @param $permissionName
     * @return bool
     */
    function is_can_edit($permissionName)
    {
        return PermissionHelper::canEdit($permissionName);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('is_can_delete')) {
    /**
     * @param $permissionName
     * @return bool
     */
    function is_can_delete($permissionName)
    {
        return PermissionHelper::canDelete($permissionName);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('is_can_restore')) {
    /**
     * @param $permissionName
     * @return bool
     */
    function is_can_restore($permissionName)
    {
        return PermissionHelper::canRestore($permissionName);
    }
}

/*---------------------------------------{</>}---------------------------------------*/
if (!function_exists('is_can_force_delete')) {
    /**
     * @param $permissionName
     * @return bool
     */
    function is_can_force_delete($permissionName)
    {
        return PermissionHelper::canForceDelete($permissionName);
    }
}

/*---------------------------------------{</>}---------------------------------------*/
if (!function_exists('is_can_show')) {
    /**
     * @param $permissionName
     * @return bool
     */
    function is_can_show($permissionName)
    {
        return PermissionHelper::canView($permissionName);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('is_can_show_all')) {
    /**
     * @param $permissionName
     * @return bool
     */
    function is_can_show_all($permissionName)
    {
        return PermissionHelper::canViewAll($permissionName);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('is_can_mass_create')) {
    /**
     * @param $permissionName
     * @return bool
     */
    function is_can_mass_create($permissionName)
    {
        return PermissionHelper::canMassCreate($permissionName);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('is_can_mass_update')) {
    /**
     * @param $permissionName
     * @return bool
     */
    function is_can_mass_update($permissionName)
    {
        return PermissionHelper::canMassEdit($permissionName);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('is_can_mass_delete')) {
    /**
     * @param $permissionName
     * @return bool
     */
    function is_can_mass_delete($permissionName)
    {
        return PermissionHelper::canMassDelete($permissionName);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('is_can_mass_restore')) {
    /**
     * @param $permissionName
     * @return bool
     */
    function is_can_mass_restore($permissionName)
    {
        return PermissionHelper::canMassRestore($permissionName);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('is_can_mass_force_delete')) {
    /**
     * @param $permissionName
     * @return bool
     */
    function is_can_mass_force_delete($permissionName)
    {
        return PermissionHelper::canMassForceDelete($permissionName);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('is_can_export')) {
    /**
     * @param $permissionName
     * @return bool
     */
    function is_can_export($permissionName)
    {
        return PermissionHelper::canExport($permissionName);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('is_can_print')) {
    /**
     * @param $permissionName
     * @return bool
     */
    function is_can_print($permissionName)
    {
        return PermissionHelper::canPrint($permissionName);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('writable_field')) {
    /**
     * @param $moduleName
     * @param $column
     * @return bool
     */
    function writable_field($moduleName, $column)
    {
        $modulePermissionName = $moduleName.'.'.$column.'.'.Permission::WRITABLE_FIELD;

        return is_can($modulePermissionName);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('read_only_field')) {
    /**
     * @param $permissionName
     * @param $column
     * @return bool
     */
    function read_only_field($permissionName, $column)
    {
        $modulePermissionName = $permissionName.'.'.$column.'.'.Permission::READ_ONLY_FIELD;

        return is_can($modulePermissionName);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('dont_show_field')) {
    /**
     * @param $permissionName
     * @param $column
     * @return bool
     */
    function dont_show_field($permissionName, $column)
    {
        $modulePermissionName = $permissionName.'.'.$column.'.'.Permission::DONT_SHOW_FIELD;

        return is_can($modulePermissionName);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('check_user_authorize')) {
    /**
     * @param $permissionName
     * @param $trash
     * @return bool
     */
    function check_user_authorize($permissionName, $trash = null)
    {
        if ($trash and !is_can_restore($permissionName) and !is_can_force_delete($permissionName)) {
            return false;
        }

        if (!is_can_show($permissionName) and !is_can_show_all($permissionName)) {
            return false;
        }
        return true;
    }
}
/*---------------------------------------{</>}---------------------------------------*/
if (!function_exists('available_permission_middleware')) {
    /**
     * @return array
     */
    function available_permission_middleware(): array
    {
        return [
            'web',
            'admin',
            'api',
        ];
    }
}