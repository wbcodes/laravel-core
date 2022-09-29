<?php

namespace Wbcodes\Core\Helpers\Classes;

use Illuminate\Support\Facades\Auth;

class PermissionHelper
{
    const CREATE = "create";
    const EDIT = "edit";
    const VIEW = "view";
    const VIEW_ALL = "view_all";
    const DELETE = "delete";
    const RESTORE = "restore";
    const FORCE_DELETE = "force_delete";

    const MASS_CREATE = "mass_create";
    const MASS_EDIT = "mass_edit";
    const MASS_DELETE = "mass_delete";
    const MASS_RESTORE = "mass_restore";
    const MASS_FORCE_DELETE = "mass_force_delete";

    const Export = "export";
    const Print = "print";

    protected $permissionName;

    /**
     * allows the user to make some things by set permission modules
     * @param  null  $user
     * @return array
     */
    public static function userPermissions($user = null): array
    {
        $cacheKey = CacheKey::userPermissions($user);
        if (Auth::check()) {
            cache_get_data($cacheKey, function () use ($user) {
                $user = $user ?? Auth::user();
                return $user->getAllPermissions()->pluck('name')->toArray();
            });
        }

        return [];
    }

    /**
     * allows the user to make some things by set permission modules
     * @param $moduleName
     * @param $permission_name
     * @return bool
     */
    public static function can($moduleName, $permission_name = null)
    {
        $moduleName = basename($moduleName);
        $modulePermissionName = $moduleName;
        if ($permission_name) {
            $modulePermissionName = self::setModulePermissionName($moduleName, $permission_name);
        }

        if (in_array($modulePermissionName, self::userPermissions())) {
            return true;
        }

        return false;
    }

    /**
     * allows the user to create a new item from the module
     * @param $moduleName
     * @return bool
     */
    public static function canCreate($moduleName)
    {
        return self::can($moduleName, self::CREATE);
    }

    /**
     * allows the user to edit an exist item from the module
     * @param $moduleName
     * @return bool
     */
    public static function canEdit($moduleName)
    {
        return self::can($moduleName, self::EDIT);
    }

    /**
     * allows the user to view items from the module
     * @param $moduleName
     * @return bool
     */
    public static function canView($moduleName)
    {
        return self::can($moduleName, self::VIEW);
    }

    /**
     * allows the user to view all items from the module
     * @param $moduleName
     * @return bool
     */
    public static function canViewAll($moduleName)
    {
        return self::can($moduleName, self::VIEW_ALL);
    }

    /**
     * allows the user to delete an exist item from the module
     * @param $moduleName
     * @return bool
     */
    public static function canDelete($moduleName)
    {
        return self::can($moduleName, self::DELETE);
    }

    /**
     * allows the user to restore an exist item from the module
     * @param $moduleName
     * @return bool
     */
    public static function canRestore($moduleName)
    {
        return self::can($moduleName, self::RESTORE);
    }

    /**
     * allows the user to destroy an exist item from the module
     * @param $moduleName
     * @return bool
     */
    public static function canForceDelete($moduleName)
    {
        return self::can($moduleName, self::FORCE_DELETE);
    }

    /**
     * allows the user to create more than one item from the  module
     * @param $moduleName
     * @return bool
     */
    public static function canMassCreate($moduleName)
    {
        return self::can($moduleName, self::MASS_CREATE);
    }

    /**
     * allows the user to edit more than one item from the  module
     * @param $moduleName
     * @return bool
     */
    public static function canMassEdit($moduleName)
    {
        return self::can($moduleName, self::MASS_EDIT);
    }

    /**
     * allows the user to delete more than one item from the  module
     * @param $moduleName
     * @return bool
     */
    public static function canMassDelete($moduleName)
    {
        return self::can($moduleName, self::MASS_DELETE);
    }

    /**
     * allows the user to restore more than one item from the module
     * @param $moduleName
     * @return bool
     */
    public static function canMassRestore($moduleName)
    {
        return self::can($moduleName, self::MASS_RESTORE);
    }

    /**
     * allows the user to destroy more than one item from the module
     * @param $moduleName
     * @return bool
     */
    public static function canMassForceDelete($moduleName)
    {
        return self::can($moduleName, self::MASS_FORCE_DELETE);
    }

    /**
     * allows the user to export items from the module
     * @param $moduleName
     * @return bool
     */
    public static function canExport($moduleName)
    {
        return self::can($moduleName, self::Export);
    }

    /**
     * allows the user to print items from the module
     * @param $moduleName
     * @return bool
     */
    public static function canPrint($moduleName)
    {
        return self::can($moduleName, self::Print);
    }

    /**
     * get permission name
     * @param $moduleName
     * @param $permission_name
     * @param  string  $prefix
     * @return bool
     */
    private static function setModulePermissionName($moduleName, $permission_name, $prefix = ".")
    {
        // User.Create, Post.Edit
        return $moduleName.$prefix.$permission_name;
    }

    /**
     * @return mixed
     */
    public function getPermissionName()
    {
        return $this->permissionName;
    }

    /**
     * @param  mixed  $permissionName
     */
    public function setPermissionName($permissionName)
    {
        $this->permissionName = $permissionName;
    }
}