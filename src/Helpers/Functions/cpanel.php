<?php
/*
 * Cpanel helper functions
 * --------------------------------------------------
 */

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/*-----------------------------------------------------------------------------------*/
if (!function_exists('set_request_attr_value')) {

    function set_request_attr_value($request, $attr, $field_type)
    {
        switch ($field_type) {
            case'multi_select':
                $request_attr = $request->{$attr} ?? [];
                break;
            case 'checkbox': // This is for single checkbox
                $request_attr = $request->has($attr) ? 1 : 0;
                break;
            case 'phone':
            case 'mobile':
                $request_attr = get_phone_number_format($request->{$attr});
                break;
            default :
                $request_attr = $request->{$attr};
                break;
        }

        return $request_attr;
    }

}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('set_row_attr_value')) {

    function set_row_attr_value($request, $attr, $field_type)
    {
        switch ($field_type) {
            case'multi_select':
                $request_attr = $request->{$attr} ?? [];
                if (is_array($request_attr)) {  // is_array
                    $request_attr = collect($request_attr)->unique()->toArray();
                    $request_attr = implode(',', $request_attr);
                    $request_attr = trim($request_attr, ',');
                }
                break;
            case 'date':
            case 'datetime':
            case 'time':
                $request_attr = get_request_value_parsed_as_date_format($field_type, $request->{$attr});
                break;
            case 'checkbox': // This is for single checkbox
                $request_attr = $request->has($attr) ? 1 : 0;
                break;
            case 'phone':
            case 'mobile':
                $request_attr = get_phone_number_format($request->{$attr});
                break;
            default :
                $request_attr = $request->{$attr};
                break;
        }

        return $request_attr;
    }

}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('row_data_store')) {

    function row_data_store($moduleClass, $available_columns, $request, $prefix = null)
    {
        $row = new $moduleClass();
        $moduleColsClass = $moduleClass::$columns;
        $cols = new $moduleColsClass;
        foreach ($available_columns as $column) {
            $attr = $column['name'];
            $can = $column['model'];

            if (in_array($can, ['Task', 'Call', 'Meeting'])) {
                $can = "Activity";
            }

            $requestAttr = $prefix ? "{$prefix}_{$attr}" : $attr;
            $field_type = $column['field_type'];
            if ($field_type == 'morphMany' or in_array($attr, $cols->getIgnoreCreateOrUpdateColumnsArray() ?? [])) {
                continue;
            }
            switch ($attr) {
                case 'image':
                case 'photo':
                case 'avatar':
                    if ($request->hasFile($requestAttr)) {
                        $row->{$attr} = upload_image($request, $requestAttr, $row->folderName);
                    }
                    break;
                case 'password':
                    if ($request->{$requestAttr}) {
                        $row->{$attr} = bcrypt($request->{$requestAttr});
                    }
                    break;
                default:
                    $row->{$attr} = set_row_attr_value($request, $requestAttr, $field_type);
                    break;
            }
        }
        // calculate columns and save it
        $calculate_columns = $cols->getCalculateColumns();
        foreach ($calculate_columns as $column) {
            $row = calculate_field($moduleClass, $column, $row);
        }

        return $row;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_phone_number_format')) {
    function get_phone_number_format($phone)
    {
        if (!is_null($phone)) {
            do {
                $phone = str_replace("++", "+", $phone); // replace ++ to 00
                $phone = str_replace(' ', '', $phone); // fix white space
            } while (Str::contains($phone, "++") or Str::contains($phone, ' '));

            $phone = str_replace("+", "00", $phone); // replace + to 00
        }

        return $phone;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('row_data_update')) {

    function row_data_update($moduleClass, $id, $available_columns, $request, $ignore = [], $prefix = null)
    {
        $row = $moduleClass::find($id);
        $moduleColsClass = $moduleClass::$columns;
        $cols = new $moduleColsClass;
        // CHECK BOX COLUMNS
        $get_checkbox_columns = $cols->columns->where('field_type', 'checkbox')->pluck('name')->toArray();
        foreach ($get_checkbox_columns as $checkbox_column) {
            if (in_array($checkbox_column, array_keys($request->all()))) {
                $row->{$checkbox_column} = 1;
            } else {
                $row->{$checkbox_column} = 0;
            }
        }

        foreach ($available_columns as $column) {
            $attr = $column['name'];
            $requestAttr = $prefix ? "{$prefix}_{$attr}" : $attr;
            $field_type = $column['field_type'];
            if ($field_type == 'morphMany' or $attr == 'id') {
                continue;
            }
            if (!in_array($attr, $ignore)) {
                switch ($attr) {
                    case 'image':
                    case 'photo':
                    case 'avatar':
                        if ($request->hasFile($attr)) {
                            $row->{$attr} = upload_image($request, $attr, $row->folderName, $row->{$attr});
                        }
                        break;
                    case 'password':
                        if ($request->{$attr}) {
                            $row->{$attr} = bcrypt($request->{$attr});
                        }
                        break;
                    default:
                        $value = set_row_attr_value($request, $requestAttr, $field_type);
                        $row->{$attr} = $value;
                        break;
                }
                // calculate columns and save it
                if ($cols->isInCalculateRelations(null, $attr)) {
                    $column = $cols->findColumn($attr);
                    $row = calculate_relation_fields($moduleClass, $column, $row);
                }
            }
        }

        return $row;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('execute_delete')) {

    /**
     * @param $item
     * @param $permissionName
     * @return JsonResponse
     */
    function execute_delete($item, $permissionName = null)
    {
        if ($permissionName and !is_can_delete($permissionName)) {
            return response()->json(__sitecore_trans('error_authorize.delete'), 401);
        }

        if ($item) {
            $item->delete();

            return response()->json(__sitecore_trans('alert_msg.delete'), 200);
        }

        return response()->json(__sitecore_trans('alert_msg.not_found'), 404);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('execute_restore')) {

    /**
     * @param $item
     * @param $permissionName
     * @return JsonResponse
     */
    function execute_restore($item, $permissionName = null)
    {
        if ($permissionName and !is_can_restore($permissionName)) {
            return response()->json(__sitecore_trans('error_authorize.restore'), 401);
        }

        if ($item) {
            $item->restore();

            return response()->json(__sitecore_trans('alert_msg.restore'), 200);
        }

        return response()->json(__sitecore_trans('alert_msg.not_found'), 404);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('execute_force_delete')) {

    /**
     * @param $item
     * @param $permissionName
     * @return JsonResponse
     */
    function execute_force_delete($item, $permissionName = null)
    {
        if ($permissionName and !is_can_force_delete($permissionName)) {
            return response()->json(__sitecore_trans('error_authorize.destroy'), 401);
        }

        if ($item) {
            $item->forceDelete();

            return response()->json(__sitecore_trans('alert_msg.destroy'), 200);
        }

        return response()->json(__sitecore_trans('alert_msg.not_found'), 404);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_auth_user')) {

    /**
     * @return Authenticatable|null
     */
    function get_auth_user()
    {
        $key = "get_auth_user";
        if (!Session::has($key)) {
            // 1-session again
            Session::put($key, auth()->user());
        }

        return session($key);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('show_with_tooltip')) {
    /**
     * @param $title
     * @return string
     */
    function show_with_tooltip($title)
    {
        $title = strip_tags($title);
        $show_title = Str::limit($title, 50);
        $tooltip_title = Str::limit($title, 400);

        return "<span data-toggle='tooltip' title='{$tooltip_title}'>{$show_title}</span><br/>";
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('titleLink')) {
    /**
     * @param        $prefix
     * @param        $row
     * @param        $user_can_edit
     * @param  string  $attr
     * @param  null  $_type
     * @return string
     */
    function titleLink($prefix, $row, $user_can_edit, $attr = null, $_type = null)
    {
        $attr = $attr ?? 'model_title';
        $_type = $_type ?? 'show';
        $_title = $row->{$attr};

        if ($_title) {
            $str_title = Str::limit($_title, 50);
            if ($_type == 'edit') {
                $href = getLocalizedURL("/{$prefix}/{$row->id}/edit");
            } else {
                $href = getLocalizedURL("/{$prefix}/{$row->id}");
            }
            if (!$prefix) {
                $href = "javascript:void(0)";
            }
            if ($user_can_edit and !getUrlTrashParams()) {
                return "<p><a href='{$href}' data-toggle='tooltip' title='{$_title}' class='btn-{$_type}' id='{$row->id}'>{$str_title}</a></p>";
            }

            return "<p><span data-toggle='tooltip' title='{$_title}' class='btn-{$_type}' id='{$row->id}'>{$str_title}</span></p>";
        }
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('getTransValues')) {
    /**
     * @param        $row
     * @param        $attr
     * @return string
     */
    function getTransValues($row, $attr)
    {
        $_title = $row->{$attr};

        return show_with_tooltip($_title);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('dropdown_item')) {

    /**
     * @param        $name
     * @param  null  $row
     * @param        $is_can
     * @param        $prefix
     * @param  null  $title
     * @param  null  $moduleClass
     * @param  null  $icon
     * @param  null  $url
     * @return string
     */
    function dropdown_item($name, $row = null, $is_can, $prefix = null, $title = null, $moduleClass = null, $icon = null, $url = null)
    {
        if (!$is_can) {
            return '';
        }

        if (!$icon) {
            $icon = get_drop_down_item_icon($name);
        }

        if (!$url) {
            $url = get_drop_down_item_url($name, $prefix, $row);
        }
        $_name_as_title = str_replace('-', '_', $name);
        $title = $title ?? __sitecore_trans("buttons.{$_name_as_title}");
        $rowId = $row ? $row->id : '';
        $slugName = str_replace('_', '-', $name);
        $class = "btn-{$slugName}-table-item btn-{$slugName}";
        $singularName = Str::singular($moduleClass);
        $moduleClass = str_replace('_', '-', $moduleClass);
        $class = $moduleClass ? "{$class}-{$moduleClass}" : $class; // btn-delete-products or btn-delete

        return "<a class='dropdown-item $class' href='{$url}' id='{$rowId}' data-id='{$rowId}' data-modal-name='{$moduleClass}' data-singular-name='{$singularName}'> <i class='{$icon}'></i> <span class='px-1'> $title </span> </a>";
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('close_task_button')) {

    function close_task_button($name, $row, $is_can, $moduleClass = null, $icon = null, $url = null)
    {
        $rowId = $row ? $row->id : '';
        $slugName = str_replace('_', '-', $name);
        $class = "btn-{$slugName}-table-item btn-{$slugName}";
        $singularName = Str::singular($moduleClass);
        $moduleClass = str_replace('_', '-', $moduleClass);
        $class = $moduleClass ? "{$class}-{$moduleClass}" : $class; // btn-delete-products or btn-delete

        $output = "";
        if ($is_can) {
            $title = hidden_sm_text(__sitecore_trans('buttons.close_task'));
            $output .= "<a href='javascript:void(0)'  class='btn btn-xs btn-outline-success btn-close-task-table-item {$class}' id='{$rowId}' data-id='{$rowId}' data-content='{$row->content}' data-modal-name='{$moduleClass}' data-singular-name='{$singularName}' title='".__sitecore_trans('buttons.close_task')."' ><i class='bx bx-check'></i></a>";
        }

        return $output;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_drop_down_item_icon')) {

    function get_drop_down_item_icon($name)
    {
        switch ($name) {
            case 'show':
                $icon = 'bx bx-show-alt';
                break;
            case 'edit':
                $icon = 'bx bx-pencil';
                break;
            case 'create':
                $icon = 'bx bx-plus-circle';
                break;
            case 'delete':
                $icon = 'bx bx-trash';
                break;
            case 'restore':
                $icon = 'bx bx-reset';
                break;
            case 'list':
                $icon = 'bx bx-list-check';
                break;
            case 'force_delete':
                $icon = 'bx bx-trash-alt';
                break;
            case 'history':
                $icon = 'bx bx-history';
                break;
            case 'active':
                $icon = 'bx bx-check-double';
                break;
            case 'cancel':
                $icon = 'bx bxs-x-circle';
                break;
            default:
                $icon = 'bx bx-circle';
                break;

        }

        return $icon;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_item_icon')) {

    function get_item_icon($name)
    {
        switch ($name) {
            case 'owners':
                $name = 'users';
                break;
        }
        $modules = collect(config('cpanel.modules', []))->pluck('icon', 'name');
        $iconClass = $modules[$name] ?? null;
        if ($iconClass) {
            return "<i class='$iconClass'></i>";
        }

        switch ($name) {
            case 'phone':
            case 'fax':
            case 'mobile':
                $icon = '<i class="bx bx-phone-call"></i>';
                break;
            case 'email':
                $icon = '<i class="bx bx-envelope"></i>';
                break;
            case 'website':
                $icon = '<i class="bx bx-globe"></i>';
                break;
            case 'flag':
                $icon = '<i class="bx bxs-flag-alt"></i>';
                break;
            case 'user':
                $icon = '<i class="bx bx-user-circle"></i>';
                break;
            case 'call_center_user':
                $icon = '<i class="bx bx-user-voice"></i>';
                break;
            case 'quality_user':
                $icon = '<i class="bx bx-user-check"></i>';
                break;
            default:
                $icon = null;
                break;

        }

        return $icon;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_drop_down_item_url')) {

    function get_drop_down_item_url($name, $prefix, $row = null)
    {
        if (!$prefix) {
            return "javascript:void(0)";
        }

        switch ($name) {
            case 'show':
                $href = getLocalizedURL("{$prefix}/{$row->id}");
                break;
            case 'edit':
                $href = getLocalizedURL("{$prefix}/{$row->id}/edit");
                break;
            case 'create':
                $href = getLocalizedURL("{$prefix}/create");
                break;
            case 'history':
                $href = getLocalizedURL("{$prefix}/{$row->id}/history");
                break;
            default:
                $href = "javascript:void(0)";
                break;
        }

        return $href;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('actionLinks')) {

    /**
     * @param      $row
     * @param  null  $prefix
     * @param  bool  $can_edit
     * @param  bool  $can_delete
     * @param  bool  $can_show
     * @param  null  $moduleClass
     * @param  null  $addNewOutput
     * @return string
     */
    function actionLinks($row, $prefix = null, $can_edit = true, $can_delete = true, $can_show = true, $moduleClass = null, $addNewOutput = null)
    {
        if ((!$can_show) and (!$can_edit) and (!$can_delete)) {
            return '';
        }
        $output = "<button class='btn btn-xs dropdown-toggle' type='button' id='actionDropdown' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'><i class='bx bx-dialpad'></i></button>
                    <div class='dropdown-menu dropdown-menu-right' aria-labelledby='actionDropdown'>";
        $output .= $addNewOutput;
        if (auth()->check()) {
            $output .= dropdown_item('show', $row, $can_show, $prefix, null, $moduleClass);
            $output .= dropdown_item('edit', $row, $can_edit, $prefix, null, $moduleClass);
            $output .= dropdown_item('delete', $row, $can_delete, $prefix, null, $moduleClass);

        }
        $output .= `</div></div>`;

        return $output;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('actionDropDownLinks')) {

    /**
     * @param      $row
     * @param  null  $prefix
     * @param  bool  $can_edit
     * @param  bool  $can_delete
     * @param  bool  $can_show
     * @param  null  $moduleClass
     * @param  null  $addNewOutput
     * @return string
     */
    function actionDropDownLinks($row, $prefix = null, $can_edit = true, $can_delete = true, $can_show = true, $moduleClass = null, $addNewOutput = null)
    {
        if ((!$can_show) and (!$can_edit) and (!$can_delete)) {
            return '';
        }
        $output = "<div class='btn-group'>
                <button type='button' class='btn btn-xs btn-dark dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'><i class='bx bx-spreadsheet'></i></button>
              <div class='dropdown-menu'>";

        $output .= $addNewOutput;
        if (auth()->check()) {
            /******** DROP DOWN LIST ********/
            $output .= dropdown_item('show', $row, $can_show, $prefix, null, $moduleClass);
            $output .= dropdown_item('edit', $row, $can_edit, $prefix, null, $moduleClass);
            $output .= dropdown_item('delete', $row, $can_delete, $prefix, null, $moduleClass);

        }
        $output .= `</div></div>`;

        return $output;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('actionButtonLinks')) {

    /**
     * @param      $row
     * @param  null  $edit_prefix
     * @param  null  $show_prefix
     * @param  bool  $can_edit
     * @param  bool  $can_delete
     * @param  bool  $can_show
     * @param  null  $addNewOutput
     * @param  bool  $showTitles
     * @return string
     */
    function actionButtonLinks($row, $edit_prefix = null, $show_prefix = null, $can_edit = true, $can_delete = true, $can_show = true, $addNewOutput = null, $showTitles = true)
    {
        if ((!$can_show) and (!$can_edit) and (!$can_delete)) {
            return '';
        }
        $output = '';
        if (auth()->check()) {
            /******** BUTTONS ********/
            $output .= show_button($row, $can_show, $show_prefix, $showTitles);
            $output .= edit_button($row, $can_edit, $edit_prefix, $showTitles);
            $output .= delete_action_button($row, $can_delete, $showTitles);
            $output .= $addNewOutput;
//            $output .= active_button($row, $user_can_edit);
        }

        return $output;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('actionModuleLinks')) {
    function actionModuleLinks($row, $edit_prefix = null, $show_prefix = null, $can_edit = true, $can_delete = true, $can_show = true, $moduleClass = null, $addNewOutput = null)
    {
        if ((!$can_show) and (!$can_edit) and (!$can_delete)) {
            return '';
        }
        $output = "<button class='btn btn-xs dropdown-toggle' type='button' id='actionDropdown' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'><i class='bx bx-dialpad'></i></button>
                    <div class='dropdown-menu dropdown-menu-right' aria-labelledby='actionDropdown'>";

        if (auth()->check()) {
            $output .= dropdown_item('show', $row, $can_show, $show_prefix, null, $moduleClass);
            $output .= dropdown_item('edit', $row, $can_edit, $edit_prefix, null, $moduleClass);
            $output .= $addNewOutput;
        }
        $output .= `</div></div>`;

        return $output;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('editorInfo')) {

    /**
     * @param $page
     * @return string
     */
    function editorInfo($page)
    {
        $output = '';
        $creator = $page->createdBy ? $page->createdBy->model_title : ' System ';
        $output .= "<p class='editor-info'>";
        $creatorTitle = __sitecore_trans('editor_created_by', ['name' => $creator]);
        $createdAtShowTitle = __sitecore_trans('editor_created_at', ['date' => optional($page->created_at)->format(('Y-m-d H:i:s A'))]);
        $output .= " <span class='text-capitalize' data-toggle='tooltip' title='$creatorTitle'> <i class='bx bx-plus-circle'></i> ".hidden_sm_text($creator)." </span>";
        $output .= " - <span data-toggle='tooltip' title='$createdAtShowTitle'> <i class='bx bx-calendar-plus'></i> ".hidden_sm_text(optional($page->created_at)->format(('Y-m-d')))." </span>";

        $editor = optional($page->modifiedBy)->model_title;
        if ($editor != null) {
            $editorTitle = __sitecore_trans('editor_modified_by', ['name' => $editor]);
            $updatedAtShowTitle = __sitecore_trans('editor_modified_at', ['date' => optional($page->updated_at)->format(('Y-m-d H:i:s A'))]);
            $output .= "<br> <span class='text-capitalize' data-toggle='tooltip' title='$editorTitle'>  <i class='bx bx-edit'></i> ".hidden_sm_text($editor)." </span>";
            $output .= " - <span data-toggle='tooltip' title='$updatedAtShowTitle'> <i class='bx bx-calendar'></i> ".hidden_sm_text(optional($page->updated_at)->format(('Y-m-d')))." </span>";
        }
        $output .= '</p>';

        return $output;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('trashInfo')) {

    /**
     * @param $page
     * @return string
     */
    function trashInfo($page)
    {
        $output = '';
        $deletedBy = $page->deletedBy ? $page->deletedBy->model_title : ' System ';
        $output .= "<p class='editor-info'>";
        $creatorTitle = __sitecore_trans('editor_deleted_by', ['name' => $deletedBy]);
        $createdAtTitle = __sitecore_trans('editor_deleted_at', ['date' => optional($page->deleted_at)->format('Y-m-d')]);
        $output .= " <span class='text-capitalize' data-toggle='tooltip' title='$creatorTitle'> <i class='bx bx-plus-circle'></i> ".hidden_sm_text($creatorTitle)." </span>";
        $output .= " -  <span data-toggle='tooltip' title='$createdAtTitle'> <i class='bx bx-calendar-plus'></i> ".hidden_sm_text($createdAtTitle)." </span>";
        $output .= '</p>';

        return $output;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('trashActionLinks')) {

    /**
     * @param $row
     * @param $user_can_restore
     * @param $user_can_force_delete
     * @return string
     */
    function trashActionLinks($row, $user_can_restore = true, $user_can_force_delete = true)
    {
        $output = "<div class='btn-group'>
                <button type='button' class='btn btn-xs btn-dark dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'><i class='bx bx-spreadsheet'></i></button>
              <div class='dropdown-menu'>";
        if (auth()->check()) {
            /******** DROP DOWN LIST ********/
            $output .= dropdown_item('restore', $row, $user_can_restore);
            $output .= dropdown_item('force_delete', $row, $user_can_force_delete);
            /******** BUTTONS ********/
            //$output .= restore_button($row, $user_can_restore);
            //$output .= force_delete_button($row, $user_can_force_delete);
        }
        $output .= `</div></div>`;

        return $output;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('hidden_sm_text')) {
    /**
     * @param      $data
     * @param  null  $className
     * @return string
     */
    function hidden_sm_text($data, $className = null)
    {
        $className = isset($className) ? $className : "d-xl-inline-block d-lg-inline-block d-md-inline-block d-none";

        return "<span class='{$className}'> {$data} </span>";
    }
}
/*---------------------------------------{</>}---------------------------------------*/

// TRASH
if (!function_exists('getUrlTrashParams')) {

    /**
     * // This function to check url contains trash or not
     * @return string
     */
    function getUrlTrashParams()
    {
        if (strpos($_SERVER['REQUEST_URI'], 'trash')) {
            return '/trash';
        }

        return '';
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('addTrashButton')) {

    function addTrashButton($permissionName, $title = null, $href = null, $params = null)
    {
        $request_has_trashed = getUrlTrashParams() ? false : true;
        if ($request_has_trashed) {
            if (!is_can_restore($permissionName) and !is_can_force_delete($permissionName)) {
                return '';
            }
            $href = getLocalizedURL("/trash/$href");
            if ($params) {
                $href .= $params;
            }
            $title = $title ?? __sitecore_trans('records.deleted');
            $id = $id ?? "trash_data";
            $icon = $icon ?? "bx-trash";
            $title = hidden_sm_text($title);

            return "<a href='{$href}' class='btn btn-xs btn-secondary mx-2' id='{$id}'> <i class='bx {$icon}'></i> $title </a>";
        }

        if (!is_can_show($permissionName)) {
            return '';
        }
        $href = getLocalizedURL("/$href");
        if ($params) {
            $href .= $params;
        }
        $title = $title ?? __sitecore_trans('records.active');
        $id = $id ?? "all_data";

        $icon = $icon ?? "bx-list-check";
        $title = hidden_sm_text($title);

        return "<a href='{$href}' class='btn btn-xs float-left btn-primary mx-2' id='{$id}'> <i class='bx {$icon}'></i> $title  </a>";
    }
}
/*---------------------------------------{</>}---------------------------------------*/

// BUTTONS
if (!function_exists('addTableButton')) {

    function addTableButton($permissionName, $title = null, $href = null, $name = null, $id = null, $icon = null)
    {
        if (!is_can_create($permissionName)) {
            return '';
        }

        if (getUrlTrashParams()) {
            return '';
        }
        $name = $name ?? "add";
        $id = $id ?? "add_data";
        $icon = $icon ?? "bx-plus";
        $title = hidden_sm_text($title);
        if (!$href) {
            $output = "<button  class='btn btn-xs btn-secondary create-btn' name='{$name}' id='{$id}'>
                    <i class='bx {$icon}'></i>
                    $title
                </button>";
        } else {
            $output = "<a href='{$href}' class='btn btn-xs btn-secondary create-btn' id='{$id}'>
                    <i class='bx {$icon}'></i>
                    $title
                </a>";
        }

        return $output;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('active_button')) {

    function active_button($row = null, $can_edit = false, $withTitle = true)
    {
        if (!$can_edit or !isset($row->active)) {
            return '';
        }
        $href = "javascript:void(0)";
        if ($row->active) {
            $title = __sitecore_trans('toggle.is.active');
            $text = $withTitle ? hidden_sm_text($title) : '';
            $class = 'btn-success';
            $icon = 'bx bx-show-alt';
        } else {
            $title = __sitecore_trans('toggle.is_not.active');
            $text = $withTitle ? hidden_sm_text($title) : '';
            $class = 'btn-outline-secondary';
            $icon = 'fas fa-eye-slash';
        }

        return "<a href='{$href}' class='btn btn-xs {$class} btn-activate' id='{$row->id}' data-value='{$row->active}' title='{$title}' ><i class='{$icon}'></i> {$text} </a>";
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('edit_button')) {

    function edit_button($row, $can_edit = false, $prefix = null, $withTitle = true)
    {
        $title = __sitecore_trans('buttons.edit');
        $text = $withTitle ? hidden_sm_text($title) : '';
        if ($prefix) {
            $href = "{$prefix}/{$row->id}/edit";
        } else {
            $href = "javascript:void(0)";
        }

        if ($can_edit) {
            return "<a href='{$href}'  class='btn btn-xs btn-primary edit' id='{$row->id}' title='{$title}' ><i class='bx bx-pencil'></i> {$text} </a>";
        }

        return "";
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('show_button')) {

    function show_button($row, $can_show = false, $prefix = null, $withTitle = true)
    {
        $title = __sitecore_trans('buttons.show');
        $text = $withTitle ? hidden_sm_text($title) : '';
        if ($prefix) {
            $href = "{$prefix}/{$row->id}";
        } else {
            $href = "javascript:void(0)";
        }

        if ($can_show) {
            return "<a href='{$href}'  class='btn btn-xs btn-primary btn-show' id='{$row->id}' title='{$title}' ><i class='bx bx-show-alt'></i> {$text} </a>";
        }

        return "";
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('delete_action_button')) {

    function delete_action_button($row, $can_delete = false, $withTitle = true)
    {
        $output = "";
        if ($can_delete) {
            $title = __sitecore_trans('buttons.delete');
            $text = $withTitle ? hidden_sm_text($title) : '';
            $output .= "<button class='btn btn-xs btn-danger btn-delete' id='{$row->id}' data-id='{$row->id}' title='{$title}' ><i class='bx bx-trash'></i> {$text} </button>";
        }

        return $output;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('delete_button')) {

    function delete_button($row, $can_delete = false, $withTitle = true)
    {
        $output = "";
        if ($can_delete) {
            $title = __sitecore_trans('buttons.delete');
            $text = $withTitle ? hidden_sm_text($title) : '';
            $output .= "<button class='btn btn-xs btn-secondary btn-block delete' id='$row->id' title='{$title}' ><i class='bx bx-trash'></i> {$text} </button>";
        }

        return $output;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('restore_button')) {

    function restore_button($row, $can_restore = false, $withTitle = true)
    {
        $output = "";
        if ($can_restore) {
            $title = __sitecore_trans('buttons.restore');
            $text = $withTitle ? hidden_sm_text($title) : '';
            $output .= "<a href='javascript:void(0)'  class='btn btn-xs btn-warning btn-restore' id='{$row->id}' title='{$title}' ><i class='bx bx-reset'></i> {$text} </a>";
        }

        return $output;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('force_delete_button')) {

    function force_delete_button($row, $can_force_delete = false, $withTitle = true)
    {
        $output = "";
        if ($can_force_delete) {
            $title = __sitecore_trans('buttons.force_delete');
            $text = $withTitle ? hidden_sm_text($title) : '';
            $output .= "<button class='btn btn-xs btn-dark btn-force-delete' id='{$row->id}' title='{$title}' ><i class='bx bxs-trash-alt'></i> {$text} </button>";
        }

        return $output;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('detail_button')) {

    function detail_button($url, $row, $title = null, $count = null, $icon = null, $class = null, $with_icon = false, $show_title = true, $tooltip = true)
    {
        $class = $class ?? 'btn-dark';
        $_title = $title ?? __sitecore_trans('buttons.details');
        $title = hidden_sm_text($_title);
        if (!$show_title) {
            $title = $_title = '';
        }
        $icon = $icon ?? 'bx bx-list-check';
        $count_title = '';
        if ($count !== null) {
            $count_title = "<span class='badge badge-light'>$count</span>";
        }

        if (!$url) {
            $url = "javascript:void(0)";
        }

        if ($with_icon) {
            $with_icon = "<i class='$icon'></i>";
        }
        $tooltip = $tooltip ? 'tooltip' : '';

        return "<a href='$url' class='btn btn-xs $class show_details' id='$row->id' data-toggle='{$tooltip}' title='{$_title}'>  {$with_icon} {$title}  {$count_title}</a>";
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('fancyImageLink')) {

    /**
     * @param      $prefix
     * @param      $imageName
     * @param  int  $width
     * @param  null  $alt
     * @param  null  $className
     * @return string
     */
    function fancyImageLink($prefix, $imageName, $width = 100, $alt = null, $className = null)
    {
        $className = $className != null ? $className : 'img-thumbnail';
        $height = $className == 'img-circle' ? $width : 'auto';
        $url = asset("storage/{$prefix}/{$imageName}");

        if (!Storage::exists("public/{$prefix}/{$imageName}")) {
            return '';
        }
        $output = "<a class='grouped_elements' data-fancybox='group' data-caption='{$imageName}' href='{$url}'>";
        $output .= "<img src='{$url}' class='{$className}' width='{$width}' height='{$height}' alt='{$alt}'/>";
        $output .= "</a>";

        return $output;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('getMenuItem')) {

    function getMenuItem($name)
    {
        $horizontalMenuJson = file_get_contents(public_path('assets/data/menus/horizontal-menu.json'));
        $horizontalMenuData = json_decode($horizontalMenuJson);
        foreach ($horizontalMenuData->menu as $item) {
            if ($item->name == $name) {
                return $item;
            }
            if (isset($item->submenu)) {
                foreach ($item->submenu as $submenu) {
                    if ($submenu->name == $name) {
                        return $submenu;
                    }
                }
            }
        }

        return null;
    }
}
if (!function_exists('show_date')) {

    /**
     * @param $date  string or date in database
     * @return false|string
     */
    function show_date($date)
    {
        return $date ? Carbon::parse($date)->format('d-m-Y h:i:s A') : '';
    }
}
/*---------------------------------------{</>}---------------------------------------*/


if (!function_exists('jsonOutput')) {
    /**
     * @param $error_array
     * @param $success_output
     * @return array
     */
    function jsonOutput($error_array, $success_output)
    {
        return array(
            'error'   => $error_array,
            'success' => $success_output
        );
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('not_found_item')) {
    function not_found_item()
    {
        return response()->json(__sitecore_trans('alert_msg.not_found'), 404);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('generate_hash_file_name')) {

    /**
     * Generate filename to store
     * @return string
     */
    function generate_hash_file_name()
    {
        return md5(time()).Str::random(3).'_'.rand(100, 999).Str::random(3);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('unlinkOldFile')) {

    /**
     * for delete file from directory
     * @param $fieldName  ( obj->image )
     * @param $public_file_dir  ('storage/FOLDERNAME')
     */
    function unlinkOldFile($fieldName, $public_file_dir)
    {
        // get file source
        if ($fieldName && $fieldName != '') {
            $oldPath = $public_file_dir.'/'.$fieldName;
            if (Storage::disk('public')->exists($oldPath)) {
                // delete old file from storage
                Storage::disk('public')->delete($oldPath);
            }
        }
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('AcceptMiddleware')) {

    /**
     * @return array
     */
    function AcceptMiddleware()
    {
        return [
            'web',
            'admin',
            'api'
        ];
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('getArrayValidationErrors')) {

    /**
     * @param $validation
     * @return array
     */
    function getArrayValidationErrors($validation)
    {
        $error_array = [];
        if ($validation) {
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
        }

        return $error_array;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('showItem')) {
    /**
     * @param      $request
     * @param      $moduleClass
     * @param      $id
     * @param      $permissionName  // permission Model Name
     * @param  null  $with
     * @return Application|Factory|JsonResponse|View|void
     */
    function showItem($request, $moduleClass, $id, $permissionName, $with = null)
    {
        if ($request->ajax()) {
            if (!is_can_show($permissionName)) {
                return not_authorize(true);
            }
            if (!is_numeric($id)) {
                return not_found_item();
            }
            $ClassName = get_class_name($moduleClass);
            if ($with) {
                $item = $ClassName::with($with)->find($id);
            } else {
                $item = $ClassName::find($id);
            }
            if (!$item) {
                return not_found_item();
            }

            return response()->json($item, 200);
        }
        // if Request not Ajax
        if (!is_can_show($permissionName)) {
            return not_authorize();
        }
        abort(404);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('owner_info_card')) {

    function owner_info_card($owner = null, $type = null, $prefix = null, $can_show = true)
    {
        if (!$owner) {
            return '';
        }

        switch ($type) {
            case "contact":
                $icon = "bx bx-user";
                $prefix = $prefix ?? "contacts";
                $permission_name = "Contact";
                break;
            case "deal":
                $icon = "bx bx-box";
                $prefix = $prefix ?? "deals";
                $permission_name = "Deal";
                break;
            case "account":
                $icon = "bx bxs-user-rectangle";
                $prefix = $prefix ?? "accounts";
                $permission_name = "Account";
                break;
            default:
                $icon = "bx bx-user-circle";
                $prefix = $prefix ?? "users";
                $permission_name = "";
                break;
        }
        $url = "javascript:void(0)";
        $target = "_self";
        if ($can_show) {
            $url = getLocalizedURL("{$prefix}/{$owner->id}");
            $target = "_blank";
        }
        $title = $owner->modelTitle;
        $owner_title = $title ? "<span class='text-info text-capitalize'> $title <br> </span>" : '';
        if (is_can_show($permission_name)) {
            $owner_title = $title ? "<a class='text-warning text-capitalize' href='{$url}' target='{$target}'><i class='{$icon}'></i> {$title}</a><br>" : '';
        }
        $avatar = get_avatar_image($title);
        $owner_mobile = phone_link(optional($owner)->mobile, 'bx bx-phone');
        $owner_phone = phone_link(optional($owner)->phone);
        $owner_secondary_phone = phone_link(optional($owner)->secondary_phone);
        $owner_email = mail_link(optional($owner)->email);

        $content = "<span class='tooltip-content clearfix'>
                    <span>{$avatar}</span>
                    <span class='tooltip-text'>
                        {$owner_title}
                        {$owner_mobile}
                        {$owner_phone}
                        {$owner_secondary_phone}
                        {$owner_email}
                    </span>";

        return "<span class='mytooltip tooltip-effect-1'><span class='tooltip-item'>{$title}</span> {$content}</span>";
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('related_info_card')) {

    function related_info_card($related = null, $type = null, $prefix = null, $can_show = true)
    {
        if (!$related) {
            return '';
        }

        switch ($type) {
            case "contact":
                $icon = "bx bx-user";
                $prefix = $prefix ?? "contacts";
                $permission_name = "Contact";
                break;
            case "deal":
                $icon = "bx bx-box";
                $prefix = $prefix ?? "deals";
                $permission_name = "Deal";
                break;
            case "account":
                $icon = "bx bxs-user-rectangle";
                $prefix = $prefix ?? "accounts";
                $permission_name = "Account";
                break;
            default:
                $icon = "bx bx-user-circle";
                $prefix = $prefix ?? "users";
                $permission_name = "";
                break;
        }
        $url = "javascript:void(0)";
        $target = "_self";
        if ($can_show) {
            $url = getLocalizedURL("{$prefix}/{$related->id}");
            $target = "_blank";
        }
        $title = $related->modelTitle;
        $avatar = get_avatar_image($title, 25);
        $related_title = $title ?? '';
        if (is_can_show($permission_name)) {
            $related_title = $title ? "<a class='text-warning text-capitalize' href='{$url}' target='{$target}'><i class='{$icon}'></i> {$title} </a> <br>" : '';
        }
        $related_mobile = phone_link(optional($related)->mobile, 'bx bx-phone', null, __sitecore_trans('mobile'));
        $related_phone = phone_link(optional($related)->phone, null, null, __sitecore_trans('phone'));
        $related_secondary_phone = phone_link(optional($related)->secondary_phone, null, null, __sitecore_trans('secondary_phone'));
        $related_email = mail_link(optional($related)->email, null, null, __sitecore_trans('email'));

//        <span>{$avatar}</span>
        return "<span class='related-info-card-details'>
                      <div class='mb-1'>
                        <span>{$avatar}</span>
                        <span class='text-capitalize'> {$related_title} </span>
                      </div>
                      <div class='pl-2'>
                        {$related_mobile}
                        {$related_phone}
                        {$related_secondary_phone}
                        {$related_email}
                      </div>
              </span>";
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_avatar_image')) {

    function get_avatar_image($name, $size = null, $rounded = null, $background = null, $color = null)
    {
        $rounded = $rounded ?? 'true';
        $background = $background ?? '727E8C';
        $color = $color ?? 'fff';
        $size = $size ?? '64';

        return "<img src='https://ui-avatars.com/api/?name={$name}&rounded={$rounded}&background={$background}&color={$color}&size={$size}' alt='{$name}' />";
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_avatar_link')) {

    function get_avatar_link($name, $rounded = null, $background = null, $color = null)
    {
        $rounded = $rounded ?? 'true';
        $background = $background ?? '727E8C';
        $color = $color ?? 'fff';

        return "https://ui-avatars.com/api/?name={$name}&rounded={$rounded}&background={$background}&color={$color}";
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_roles')) {

    function get_roles($user = null)
    {
        if ($user) {
            return $user->roles;
        }

        if (auth()->user()->hasRole('developer')) {
            return Role::latest('id')->get();
        }

        return Role::where('name', '!=', 'developer')->orderBy('id')->get();
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('relatedCard')) {

    /**
     * @param        $related
     * @param  string  $id_href
     * @return string
     */
    function relatedCard($related, $id_href = '#')
    {
        if (!$related) {
            return '';
        }
        $title = $related->model_title;
        $avatar = get_avatar_image($title);
        $model_name = class_basename($related);
        $plural_name = Str::plural(Str::lower($model_name));
        $link = getLocalizedURL("/{$plural_name}/{$related->id}{$id_href}");

        $tooltip = '<div class="tooltip-content clearfix"><div class="tooltip-text">';
        $tooltip .= $avatar;
        $tooltip .= '<span>';
        $tooltip .= "{$model_name}<br/><a class='text-warning font-weight-bold' style='padding-left:10px' href='$link'>{$title}</a>";

        if ($related->email) {
            $tooltip .= mail_link($related->email).'<br/>';
        }

        if ($related->mobile) {
            $tooltip .= phone_link($related->mobile).'<br/>';
        }

        if ($related->phone) {
            $tooltip .= phone_link($related->phone).'<br/>';
        }

        if ($related->phone1) {
            $tooltip .= phone_link($related->phone1).'<br/>';
        }

        if ($related->website) {
            $tooltip .= get_link($related->website).'<br/>';
        }
        $tooltip .= '</span></div></div>';

        return "<div class='mytooltip tooltip-effect-1'><div class='tooltip-item'>{$title}</div>{$tooltip}";
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('mail_link')) {
    function mail_link($email, $icon = null, $prefixLink = null, $title = null)
    {
        $icon = $icon ?? 'bx bx-envelope';
        $prefixLink = $prefixLink ?? 'mailto:';
        if ($prefixLink and $email) {
            return "<a href='{$prefixLink}{$email}' data-toggle='tooltip' data-placement='right' title='{$title}' ><i class='{$icon}'></i> {$email}</a><br/>";
        }
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('phone_link')) {
    function phone_link($phone, $icon = null, $prefixLink = null, $title = null)
    {
        $icon = $icon ?? 'bx bx-phone-call';
        $prefixLink = $prefixLink ?? 'tel:';
        if ($prefixLink and $phone) {
            return "<a href='{$prefixLink}{$phone}' data-toggle='tooltip' data-placement='right' title='{$title}' ><i class='{$icon}'></i> {$phone}</a><br/>";
        }
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_date_format')) {
    function get_date_format($datetime, $isValueFormat = false, $inline = true)
    {
        $output = show_date($datetime, $isValueFormat);
        $output .= $inline ? " " : "<br/>";
        $output .= show_time($datetime, $isValueFormat);

        return $output;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('show_date')) {

    /**
     * @param $date  string or date in database
     * @param  bool  $isValueFormat
     * @return false|string
     */
    function show_date($date, $isValueFormat = false)
    {
        $format = $isValueFormat ? config('cpanel.format.value.date', 'Y-m-d') : config('cpanel.format.date', 'd/m/Y');
        $nodata = $isValueFormat ? null : '-';
        try {
            return $date ? Carbon::parse($date)->format($format) : $nodata;
        } catch (Exception $exp) {
            return $nodata;
        }
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('show_time')) {

    /**
     * @param $date  string or date in database
     * @param  bool  $isValueFormat
     * @return false|string
     */
    function show_time($date, $isValueFormat = false)
    {
        $format = $isValueFormat ? config('cpanel.format.value.time', 'h:i A') : config('cpanel.format.time', 'h:i A');
        $nodata = $isValueFormat ? null : '-';
        try {
            return $date ? Carbon::parse($date)->format($format) : $nodata;
        } catch (Exception $exp) {
            return $nodata;
        }
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('show_datetime')) {
    function show_datetime($datetime, $isValueFormat = false)
    {
        $format = $isValueFormat ?
            config('cpanel.format.value.datetime', 'Y-m-d H:i A') :
            config('cpanel.format.datetime', 'd/m/Y h:i A');
        $nodata = $isValueFormat ? null : '-';

        try {
            return $datetime ? Carbon::parse($datetime)->format($format) : $nodata;
        } catch (Exception $exp) {
            return $nodata;
        }
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_request_value_parsed_as_date_format')) {
    function get_request_value_parsed_as_date_format($type, $date_string)
    {
        $value = $date_string;
        switch ($type) {
            case 'date':
                if ($date_string) {
                    $value = Carbon::createFromFormat(config("cpanel.format.date", 'd/m/Y'), $date_string)->toDateString();
                    $value = Carbon::parse($value);
                }
                break;
            case 'datetime':
                if ($date_string) {
                    $value = Carbon::createFromFormat(config("cpanel.format.datetime", 'd/m/Y H:i A'), $date_string)->toDateTimeLocalString();
                    $value = Carbon::parse($value);
                }
                break;
            default:
                $value = $date_string;
                break;
        }

        return $value;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_audits')) {
    function get_audits($row)
    {
        if (!$row->audits) {
            return array();
        }

        return $row->audits()->with('user')->latest('id')->take(null)->get();
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_related_class')) {
    function get_related_class($attribute, $row = null)
    {
        $related_class = null;
        $related = get_related($attribute);
        switch ($attribute) {
            case 'category_id':
                $related_class = 'Wbcodes\SiteCore\Category';
                break;
            case 'brand_id':
                $related_class = 'Wbcodes\SiteCore\Brand';
                break;
            case 'status_id':
                $related_class = 'Wbcodes\SiteCore\Status';
                break;
            case 'inventory_id':
                $related_class = 'Wbcodes\SiteCore\Inventory';
                break;
            case 'office_id':
                $related_class = 'Wbcodes\SiteCore\Office';
                break;
            case 'currency_id':
                $related_class = 'Wbcodes\SiteCore\Currency';
                break;
            case 'user_id':
            case 'created_by':
            case 'modified_by':
            case 'deleted_by':
                $related_class = 'App\Models\User';
                break;
            case 'owner_id':
                $related_class = (isset($row) and $row->{$related}) ? get_class($row->{$related}) : null;
                break;
            default:
                break;
        }

        return $related_class;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_related_module_name')) {
    function get_related_module_name($module_name)
    {
        switch ($module_name) {
            case 'open_activities':
            case 'closed_activities':
                $module_name = 'activities';
                break;
            default:
                break;
        }

        return $module_name;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_related')) {
    function get_related($attribute)
    {
        if (strpos($attribute, '_id')) {
            return str_replace('_id', '', $attribute);
        }

        return null;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_request_term')) {
    function get_request_term($request)
    {
        if ($request->has('query') and isset($request->get('query')['term'])) {
            return $request->get('query')['term'];
        }

        return '';
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_class_name')) {
//    add statistics partials and use it in dashboard
    function get_class_name($moduleClass)
    {
        if (strpos($moduleClass, "Wbcodes\\SiteCore\\Models\\") !== false) {
            return $moduleClass;
        }
        $moduleClass = ucfirst($moduleClass);

        if (preg_match_all("/[A-Z]/", $moduleClass) <= 1) {
            $moduleClass = Str::lower($moduleClass);
        }
        $moduleClass = fixModuleClass($moduleClass);

        return "Wbcodes\\SiteCore\\Models\\{$moduleClass}";
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('fixModuleClass')) {
    function fixModuleClass($name)
    {
        $name = str_replace('-', '_', $name);
        $arr = explode('_', $name);
        $newName = '';
        foreach ($arr as $part) {
            $newName .= ucfirst($part);
        }
        $name = str_replace('App\\', 'Wbcodes\\SiteCore\\Models\\', $newName);
        $name = str_replace('App\[]', 'Wbcodes\\SiteCore\\Models\\', $name);

        return $name;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('show_tooltip_datatable_text')) {
    function show_tooltip_datatable_text($text, $textLimit)
    {
        $show_text = Str::limit($text, $textLimit);
        if (Str::length($text) > $textLimit) {
            return '<span data-toggle="tooltip" title="'.$text.'">'.$show_text.'</span><br/>';
        }

        return $show_text;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('show_tooltip_datatable_link')) {
    function show_tooltip_datatable_link($url, $text, $textLimit, $title = null)
    {
        $show_text = Str::limit($text, $textLimit);
        if (Str::length($text) > $textLimit) {
            return "<span data-toggle='tooltip' title='{$text}'><a href='{$url}' title='{$title}'>$show_text</a></span><br/>";
        }

        return "<a href='{$url}' title='{$title}'>$show_text</a>";

    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('datatable_column')) {
    function datatable_column($col, $row, $textLimit = null, $withTrashed = false, $colName = null, $datatable_type = null)
    {
        if (!$row) {
            return '';
        }

        if ($datatable_type == 'report') {
            $colName = $colName ?? $col['slug'];
        } else {
            $colName = $col['name'] ?? $col['slug'];
        }
        $textLimit = $textLimit ?? 40;

        $row_value = strip_tags($row->{$colName});
        $row_text = Str::limit($row_value, $textLimit);
        $relationName = $col['relation_name'];
        $field_type = $col['field_type'];
        $table = explode('.', $col['slug'])[0] ?? null;

        if ($withTrashed and $relationName and in_array($field_type, ['related_to', 'nested', 'nested_with_url', 'morphMany'])) {
            try {
                $_item = $row->{$relationName}()->withTrashed()->first();
            } catch (Exception $exp) {
                $_item = $row->{$relationName}->first();
            }
        } else {
            $_item = $row->{$relationName};
        }

        if ($table == 'leads') {
            $relatedModel = $col['model'] == 'Contact' ? 'contacts' : 'leads';
            $url_prefix = get_cpanel_module($relatedModel, 'url');
        } else {
            $url_prefix = get_cpanel_module($table, 'url');
        }

        switch ($col['show_in_index_type']) {
            case 'url':
                $row_id = $row->id ?? $row->{$table.".id"};
                $url = url($url_prefix ?? $row->url_prefix).'/'.$row_id;
                $column = show_tooltip_datatable_link($url, $row_value, $textLimit, "click to open page");
                break;

            case 'email':
                $column = '<a href="mailto:'.$row_value.'">'.$row_text.'</a>';
                break;

            case 'phone':
                $column = '<a href="tel:'.$row_value.'">'.$row_text.'</a>';
                break;

            case 'file_url':
                $column = '<a href="'.$row->FilePath.'" target="_blank">'.$row_text.'</a>';
                break;

            case 'external_link':
                $column = '<a href="'.$row_value.'" target="_blank">'.$row_text.'</a>';
                break;

            case 'related_to':
                $column = show_tooltip_datatable_link(optional($_item)->url, optional($_item)->modelTitle, $textLimit, "click to open page");
                break;

            case 'nested':
                if ($datatable_type == 'report') {
                    $list = collect($col['related_list']);
                    $relationClass = $col['relation_name_class'];
                    $classReportTitle = $relationClass::$reportTitle ?? 'title';
                    $list_item = $list->where('id', '=', $row_value)->first();
                    if ($list_item) {
                        $row_text = $list_item[$classReportTitle];
                    }
                } else {
                    $row_text = optional($_item)->modelTitle;
                }
                $column = show_tooltip_datatable_text($row_text, $textLimit);
                break;

            case 'nested_with_url':
                $linkName = str_replace('_', '-', $relationName);
                $linkTitle = str_replace('_', ' ', $relationName);
                $url = Str::plural(Str::lower($linkName)).'/'.optional($_item)->id;
                $column = show_tooltip_datatable_link(url($url), optional($_item)->modelTitle, $textLimit, "click to open {$linkTitle} page");
                break;

            case 'morphMany':
                if (!$withTrashed) {
                    $_item = optional($_item)->first();
                }
                $row_text = optional($_item)->title ?? (optional($_item)->modelTitle ?? '');
                $column = show_tooltip_datatable_text($row_text, $textLimit);
                break;

            case 'image':
            case 'photo':
            case 'avatar':
                $column = fancyImageLink($row->folderName, $row_value, 60, null, 'rounded shadow');
                break;
            case 'date':
                $column = show_date($row_value);
                break;
            case 'datetime':
                $column = show_datetime($row_value);
                break;
            case 'time':
                $column = show_time($row_value);
                break;
            case 'number_format':
                $column = number_format($row_value, 0);
                break;
            case 'status':
                $column = is_array($col['status_list']) ? (isset($col['status_list'][$row->{$colName}]) ? $col['status_list'][$row->{$colName}] : 'unknown') : '';
                break;
            case 'multi_select':
                $column = '';
                $list = collect($col['related_list']);
                $value_array = explode(',', $row_value);
                foreach ($value_array as $key => $item) {
                    $list_item = $list->where('id', '=', $item)->first();
                    if ($list_item) {
                        $column .= $list_item['title'].'; ';
                    }
                }
                break;
            case 'text':
            case 'long_text':
            default:
                $column = show_tooltip_datatable_text($row_value, $textLimit);
                break;
        }

        return $column;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('export_datatable_column')) {
    function export_datatable_column($col, $row, $withTrashed = false, $colName = null, $module = null)
    {
        if (!$row) {
            return '';
        }

        if ($module == 'reports') {
            $colName = $colName ?? $col['slug'];
        } else {
            $colName = $col['name'] ?? $col['slug'];
        }
        $textLimit = $textLimit ?? 40;

        $row_value = strip_tags($row->{$colName});
        $row_text = Str::limit($row_value, $textLimit);
        $relationName = $col['relation_name'];
        $field_type = $col['field_type'];

        if ($withTrashed and $relationName and in_array($field_type, ['related_to', 'nested', 'nested_with_url', 'morphMany'])) {
            try {
                $_item = $row->{$relationName}()->withTrashed()->first();
            } catch (Exception $exp) {
                $_item = $row->{$relationName}->first();
            }
        } else {
            $_item = $row->{$relationName};
        }

        switch ($col['show_in_index_type']) {
            case 'related_to':
                $column = Str::limit(optional($_item)->modelTitle, $textLimit);
                break;
            case 'nested':
                if ($module == 'reports') {
                    $list = collect($col['related_list']);
                    $relationClass = $col['relation_name_class'];
                    $classReportTitle = $relationClass::$reportTitle ?? 'title';
                    $list_item = $list->where('id', '=', $row_value)->first();
                    if ($list_item) {
                        $row_text = $list_item[$classReportTitle];
                    }
                } else {
                    $row_text = optional($_item)->modelTitle;
                }
                $column = $row_text;
                break;

            case 'nested_with_url':
                $column = optional($_item)->modelTitle;
                break;

            case 'morphMany':
                if (!$withTrashed) {
                    $_item = optional($_item)->first();
                }
                $column = optional($_item)->title ?? (optional($_item)->modelTitle ?? '');
                break;

            case 'image':
//                $column = fancyImageLink($row->folderName, $row_value, 60, null, 'rounded shadow');
                $column = $row->imagePath ?? getLocalizedURL("{$row->folderName}/{$row_value}");
                break;

            case 'number_format':
                $column = number_format($row_value, 0);
                break;
            case 'status':
                $column = is_array($col['status_list']) ? (isset($col['status_list'][$row->{$colName}]) ? $col['status_list'][$row->{$colName}] : 'unknown') : '';
                break;
            case 'multi_select':
                $column = '';
                $list = collect($col['related_list']);
                $value_array = explode(',', $row_value);
                foreach ($value_array as $key => $item) {
                    $list_item = $list->where('id', '=', $item)->first();
                    if ($list_item) {
                        $column .= $list_item['title'].'; ';
                    }
                }
                break;
            default:
                $column = $row_text;
                break;
        }

        return $column;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('mass_checkbox')) {

    function mass_checkbox($row)
    {
        return '<input type="checkbox" name="mass_checkbox[]" class="mass_checkbox" value="'.$row->id.'" />';
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('cpanel_module')) {
    function cpanel_module(
        $name,
        $model,
        $controller,
        $icon = null,
        $can = null,
        $is_base_controller = true,
        $except = null,
        $only = null,
        $text = null,
        $title = null,
        $folder_name = null,
        $url = null,
        $bgClass = null,
        $color = null,
        $dash_active = true,
        $is_active = true
    ) {
        $except = $except ?? [];
        $only = $only ?? [];
        $name = str_replace('_', '-', $name);
        $title = $title ?? str_replace('-', '_', $name);
        $text = $text ?? "sitecore::locale.{$title}";
        $folder_name = strtolower($folder_name ?? $title);
        $namespace = "Wbcodes\SiteCore\Http\Controllers\Web";
        if ($can !== false) {
            $can = ucfirst($can ?? get_singular_module($name));
        }

        if (!$url) {
            $url = getLocalizedURL($name);
        }

        return [
            'name'               => $name,
            'model'              => $model,
            'namespace'          => $namespace,
            'controller'         => basename($controller),
            'controller_path'    => $controller,
            'icon'               => $icon ?? 'bx bx-grid',
            'can'                => $can,
            'except'             => $except ?? [],
            'only'               => $only ?? [],
            'title'              => $title,
            'text'               => $text,
            'folder_name'        => $folder_name,
            'url'                => trim($url, '/'),
            'bgClass'            => $bgClass ?? 'bg-orange',
            'color'              => $color ?? '',
            'dash_active'        => $dash_active ?? true,
            'is_active'          => $is_active ?? true,
            'is_base_controller' => !isset($is_base_controller) ? true : $is_base_controller,
        ];
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('cpanel_crud')) {
    function cpanel_crud($name, $script_name = null)
    {
        return [
            'name'        => str_replace('_', '-', $name),
            'trans'       => str_replace('-', '_', $name),
            'script_name' => $script_name ?? $name,
        ];
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_cpanel_modules')) {
    function get_cpanel_modules()
    {
        return config("cpanel.modules", []);
    }
}
/*---------------------------------------{</>}---------------------------------------*/
if (!function_exists('get_cpanel_module')) {
    function get_cpanel_module($moduleName, $key)
    {
        $moduleName = str_replace('-', '_', $moduleName);

        return config("cpanel.modules.{$moduleName}.{$key}");
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_cpanel_module_key')) {
    function get_cpanel_module_key($value, $by = 'model')
    {
        $panel_modules = config("cpanel.modules");
        foreach ($panel_modules as $key => $module) {
            if ($module[$by] == $value) {
                return $key;
            }
        }

        return null;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('is_base_controller')) {
    function is_base_controller($module)
    {
        return get_cpanel_module($module, 'is_base_controller');
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_singular_module')) {
    function get_singular_module($moduleName)
    {
        $moduleName = str_replace('-', '_', $moduleName);
        $moduleName = lcfirst(fixModuleClass($moduleName));

        return Str::singular($moduleName);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_show_field_type')) {
    function get_show_field_type($field_type)
    {
        $return_same_type_array = [
            "hidden", "nested", "related_to", "morphMany", "date", "time", "datetime", "password", "status_list", "youtube_link", "external_link", "multi_select",
        ];

        if (in_array($field_type, $return_same_type_array)) {
            return $field_type;
        }

        switch ($field_type) {
            case "text":
                $show_field_type = 'textarea';
                break;
            case "image":
            case "avatar":
            case "photo":
                $show_field_type = 'image';
                break;
            case "checkbox":
                $show_field_type = 'checkbox';
                break;
            default:
                $show_field_type = 'text'; // input type=text
                break;
        }

        return $show_field_type;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('group_by')) {

    function group_by($key, $data)
    {
        $result = [];

        foreach ($data as $val) {
            if (array_key_exists($key, $val)) {
                $result[$val[$key]][] = $val;
            } else {
                $result[""][] = $val;
            }
        }

        return $result;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('group_by_cols')) {

    function group_by_cols($keys, $data)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        $result = [];
        foreach ($keys as $index => $key) {
            $results = group_by($key, $data);
            foreach ($results as $group => $group_data) {
                if (isset($keys[$index + 1])) {
                    $result[$group] = group_by($keys[$index + 1], $group_data);
                } else {
                    break;
                }
            }
        }

        return $result;
    }

}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_fields_section_with_cols')) {
    function get_fields_section_with_cols($data)
    {
        return group_by_cols(['fields_section', 'fields_section_col'], $data);
    }

}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('getModels')) {

    function getModels()
    {
        $path = app_path()."/Models";

        $out = [];
        $results = scandir($path);
        foreach ($results as $result) {
            if ($result === '.' or $result === '..') {
                continue;
            }
            $filename = $path.'/'.$result;
            if (is_dir($filename)) {
                $out = array_merge($out, getModels($filename));
            } else {
                $out[] = substr($filename, 0, -4);
            }
        }

        return $out;
    }
}
/*---------------------------------------{</>}---------------------------------------*/


if (!function_exists('get_permission_related_table')) {

    /**
     * @param      $moduleName
     * @return string
     */
    function get_permission_related_table($moduleName)
    {
        $moduleName = get_related_module_name($moduleName);

        return get_cpanel_module($moduleName, 'can');
    }
}
/*---------------------------------------{</>}---------------------------------------*/


if (!function_exists('my_staff_users')) {

    /**
     * @param  null  $user
     * @param  bool  $withUser
     * @return string
     */
    function my_staff_users($withUser = false, $user = null)
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return collect();
        }

        $userRole = $user->roles->first();

        // If user not CEO or DEVELOPER (first parent)
        if (optional($userRole)->parent_id) {
            dd('asd');
            $user_staff_roles_array = user_staff_roles()->pluck('id')->toArray();
            $users = User::whereHas('roles', function ($q) use ($user_staff_roles_array) {
                $q->whereIn('id', $user_staff_roles_array);
            });
            if ($withUser) {
                $users->orWhere('id', optional($user)->id);
            }

            return $users->get();
        }

        return User::all();
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('user_staff_roles')) {

    /**
     * @param  null  $user
     * @return Collection|string
     */
    function user_staff_roles($user = null)
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return collect();
        }
        $user_roles = Role::whereIn('id', $user->roles->pluck('id'))->get();
        $child_roles = collect();
        foreach ($user_roles as $role) {
            $child_roles = collect($child_roles)->merge($role->slaves())->unique();
        }

        return $child_roles;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('check_user_role_is_root_node')) {

    /**
     * check if user role parent is null or 0 => this meaning the role is grandparents (Root Node)3
     * example : (ceo, data-entry)
     * @param  null  $user
     * @return Collection|string
     */
    function check_user_role_is_root_node($user = null)
    {
        $user = $user ?? auth()->user();
        $user_roles_parents = $user->roles->pluck('parent_id')->toArray();

        return !empty(array_intersect([null, 0], $user_roles_parents));
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_module_profile_permissions')) {

    function get_module_profile_permissions($module_name)
    {
        $ModuleClass = get_class_name($module_name);

        return collect(array_keys($ModuleClass::PERMISSION_ACTION))->map(function ($item) use ($module_name) {
            return "{$module_name}.{$item}";
        })->toArray();
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('available_to_convert_lead_categories')) {

    function available_to_convert_lead_categories()
    {
        $slugs = [
            'high-interest',
            'normal-interest',
            'low-interest',
        ];

        return ListOption::LeadCategories()->whereIn('slug', $slugs)->get();
    }
}
/*---------------------------------------{</>}---------------------------------------*/


if (!function_exists('get_activity_cols_by_type')) {

    function get_activity_cols_by_type($type)
    {
        switch ($type) {
            case 'task':
                $cols = new TaskCols();
                break;
            case 'call':
                $cols = new CallCols();
                break;
            case 'meeting':
                $cols = new MeetingCols();
                break;
            default:
                $cols = new ActivityCols();
                break;
        }

        return $cols;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_module_cols_class')) {

    function get_module_cols_class($module, $module_name = null, $isAjax = false)
    {
        if ($module_name == null) {
            $module_name = $module->name;
        }

        $moduleClass = get_class_name($module_name);

        if (!class_exists($moduleClass)) {
            if ($isAjax) {
                return response()->json("The $moduleClass class not exists.");
            }
            abort(404);
        }
        if (isset($moduleClass::$columns)) {
            $moduleColsClass = $moduleClass::$columns;
            if (!class_exists($moduleColsClass)) {
                if ($isAjax) {
                    return response()->json("The $moduleColsClass class not exists.");
                }
                abort(404);
            }

            return $moduleColsClass;
        }
        abort(404);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_hidden_cols_values')) {

    function get_hidden_cols_values($quick_create_columns, $row, $module_column = null)
    {
        $hidden_column_values = [];
        $hidden_column = collect($quick_create_columns)->where('quick_field_type', 'hidden')->pluck('name')->toArray();
        foreach ($hidden_column as $co) {
            if (strpos($co, '_type')) {
                $hidden_column_values[$co] = get_class($row);
            }
            if (strpos($co, '_id')) {
                $hidden_column_values[$co] = $row->id;
            }
        }
//        like after_sale_id is related to deal_id (deal id make hidden and select it automatically) Example in : after sale report modal
        $related_hidden_column = collect($quick_create_columns)->where('quick_field_type', 'related_hidden')->pluck('name')->toArray();
        foreach ($related_hidden_column as $related_col_name) {
            if ($related_col_name == $module_column) {
                $hidden_column_values[$related_col_name] = $row->id;
            } else {
                if (strpos($related_col_name, '_id')) {
                    $hidden_column_values[$related_col_name] = $row->{$related_col_name};
                }
            }
        }

        return count($hidden_column_values) ? $hidden_column_values : null;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_session_agent')) {

    function get_session_agent($agent)
    {
        $result = [];

        // Get Platform Details
        $result['platform'] = get_platform_name($agent);

        // Get Browser Details
        $browser = get_browser_details($agent);
        $result['browser'] = $browser['browser'];
        $result['user_browser'] = $browser['user_browser'];

        return $result;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_platform_name')) {

    function get_platform_name($agent)
    {
        //First get the platform?
        if (preg_match('/linux/i', $agent)) {
            $platform = 'linux';
        } elseif (preg_match('/macintosh|mac os x/i', $agent)) {
            $platform = 'mac';
        } elseif (preg_match('/windows|win32/i', $agent)) {
            $platform = 'windows';
        } else {
            $platform = 'Unknown';
        }

        return $platform;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_browser_details')) {

    function get_browser_details($agent)
    {
        // Next get the name of the useragent yes separately and for good reason
        if (preg_match('/MSIE/i', $agent) && !preg_match('/Opera/i', $agent)) {
            $browser = 'Internet Explorer';
            $user_browser = "MSIE";
        } elseif (preg_match('/Firefox/i', $agent)) {
            $browser = 'Mozilla Firefox';
            $user_browser = "Firefox";
        } elseif (preg_match('/Chrome/i', $agent)) {
            $browser = 'Google Chrome';
            $user_browser = "Chrome";
        } elseif (preg_match('/Safari/i', $agent)) {
            $browser = 'Apple Safari';
            $user_browser = "Safari";
        } elseif (preg_match('/Opera/i', $agent)) {
            $browser = 'Opera';
            $user_browser = "Opera";
        } elseif (preg_match('/Netscape/i', $agent)) {
            $browser = 'Netscape';
            $user_browser = "Netscape";
        } else {
            $browser = 'Unknown';
            $user_browser = 'Unknown';
        }

        return [
            'browser'      => $browser,
            'user_browser' => $user_browser,
        ];
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_location_by_ip')) {

    function get_location_by_ip($ip = null)
    {
        $ip = $ip ?? ($_SERVER["HTTP_CF_CONNECTING_IP"] ?? getenv('REMOTE_ADDR'));
        if (!in_array($ip, ['127.0.0.1'])) {
            $geo = unserialize(file_get_contents("http://www.geoplugin.net/php.gp?ip=$ip"));
            $city = $geo['geoplugin_city'] ?? null;
            $country = $geo['geoplugin_countryName'] ?? null;

            return "{$city}, {$country}";
        }

        return null;

    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('bg_colors')) {

    function bg_colors()
    {
        return [
            'bg-info',
            'bg-warning',
            'bg-danger',
            'bg-success',
            'bg-primary',
            'bg-muted',
            'bg-yellow',
            'bg-red',
            'bg-blue',
            'bg-black',
            'bg-grey',
        ];
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('create_new_audit')) {
    function create_new_audit($column_names, $old_values, $new_values, $audit_changes)
    {
        if (is_array($column_names)) {
            $old_values_data = [];
            $new_values_data = [];
            foreach ($column_names as $key => $column_name) {
                $old_values_data[$column_name] = $old_values[$key];
                $new_values_data[$column_name] = $new_values[$key];
            }
            $audit_changes['old_values'] = $old_values_data;
            $audit_changes['new_values'] = $new_values_data;
        } else {
            $audit_changes['old_values'] = [$column_names => $old_values];
            $audit_changes['new_values'] = [$column_names => $new_values];
        }
        Audit::create($audit_changes);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('change_dropzone_files_to_module')) {
    function change_dropzone_files_to_module($attachments, $row)
    {
        $newFilePath = "attachments";
        foreach ($attachments as $id) {
            $attachment = Attachment::where([
                ['attachable_id', '<>', $row->id],
                ['attachable_type', '<>', get_class($row)]
            ])->find($id);
            if ($attachment) {
                $oldPath = "{$attachment->path}/{$attachment->storage_name}";
                $newPath = "{$newFilePath}/{$attachment->storage_name}";
                if (Storage::disk('public')->exists($oldPath)) {
                    // check if folder exist
                    if (!Storage::disk('public')->exists($newFilePath)) {
                        Storage::makeDirectory($newFilePath);
                    }
                    Storage::disk('public')->move($oldPath, $newPath);
                }
                $attachment->attachable_id = $row->id;
                $attachment->attachable_type = get_class($row);
                $attachment->path = $newFilePath;
                $attachment->save();
            }
        }
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_default_values')) {
    function get_default_values($column, $values = null)
    {
        $column_name = $column['name'];
        if (!$values) {
            $values = [];
        }

        if ($column_name == 'owner_id') {
            $values[$column_name] = auth()->id();
        } else {
            if ($column['field_type'] == 'nested') {
                $default = collect($column['related_list'])->where('default_selected', 1)->first();
                if ($default) {
                    $values[$column_name] = $default->id;
                }
            }
        }

        return $values;
    }
}
/*---------------------------------------{</>}---------------------------------------*/


if (!function_exists('change_all_user_group_columns')) {
    function change_all_user_group_columns($row, $original = null)
    {
        $will_change_column_names = ['quality_user_id', 'other_user_id', 'call_center_user_id'];
        foreach ($will_change_column_names as $column_name) {

            // Change row->deal->contact sale_user_id or (another column name) value
            if (!is_null($row->{$column_name})) {
                // if there are original value and row->sale_user_id not null and row->sale_user_id != original['sale_user_id']
                $in_create_module = !$original;
                $in_update_module = ($original and ($row->{$column_name} != $original[$column_name]));

                if ($in_create_module or $in_update_module) {
                    $contact = $row->contact ?? optional($row->deal)->contact;
                    $allow_update_contact = $contact and ($contact->{$column_name} != $row->{$column_name});
                    if ($allow_update_contact) {
                        $contact->{$column_name} = $row->{$column_name};
                        try {
                            $contact->save();
                        } catch (Exception $exp) {
                        }
                        change_contact_deals_user_group_column($contact, $column_name);
                    } else {
                        change_deal_modules_user_group_column($row->deal, $column_name);
                    }
                }
            }
        }
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('change_contact_deals_user_group_column')) {
    function change_contact_deals_user_group_column($contact, $column_name)
    {
        // if there are original value and row->sale_user_id not null and row->sale_user_id != original['sale_user_id']
        foreach ($contact->deals as $deal) {
            if ($deal->{$column_name} != $contact->{$column_name}) {
                $deal->{$column_name} = $contact->{$column_name};
                $deal->save();
            }
            change_deal_modules_user_group_column($deal, $column_name);
        }
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('change_deal_modules_user_group_column')) {
    function change_deal_modules_user_group_column($deal, $column_name)
    {
        if (!$deal) {
            return;
        }
        $deal_related_modules = [];
        foreach ($deal_related_modules as $deal_related_module) {

            foreach ($deal->{$deal_related_module} as $item) {   //Example: deal->appointments as appointment

                if ($item->{$column_name} != $deal->{$column_name}) { //Example: appointment->sale_user_id != deal->sale_user_id
                    $item->{$column_name} = $deal->{$column_name};    //Example: appointment->sale_user_id == deal->sale_user_id
                    $item->save();
                }
            }
        }
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('save_serial_number_in_db')) {
    function save_serial_number_in_db($row)
    {
        if (!$row->serial) {
            $row->serial = $row->serial_number;
            $row->flushEventListeners();
            $row->save();
            $row->getEventDispatcher();
        }
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_model_name')) {
    function get_model_name($tableName)
    {
        return ucfirst(Str::singular(Str::camel($tableName)));
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('select_available_operator')) {
    /**
     * @param      $name
     * @param      $title
     * @param      $list
     * @param      $field_type  = select
     * @param      $field  = single
     * @return array
     */
    function select_available_operator($name, $title, $list, $field_type = null, $field = null)
    {
        $field_type = $field_type ?? 'select';
        $field = $field ?? 'single';

        return [
            'name'       => $name,
            'title'      => $title,
            'field'      => $field,
            'field_type' => $field_type,
            'list'       => $list,
        ];
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('text_available_operator')) {
    function text_available_operator($name, $title, $field = null, $field_type = null)
    {
        $field_type = $field_type ?? 'text';
        $field = $field ?? 'single';

        return [
            'name'       => $name,
            'title'      => $title,
            'field'      => $field,
            'field_type' => $field_type,
        ];
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_popover')) {
    function get_popover($row, $column, $is_popover, $icon = false)
    {
        $field_name = $column['name'];
        $relation_name = $column['relation_name'] ?? '';
        $field_type = $column['field_type'];
        $show_in_index_type = $column['show_in_index_type'];

        $popover = '';
        if ($is_popover or ($show_in_index_type == 'nested_with_url')) {

            if ($icon) {
                $icon = "<i class='bx bx-link-external'></i>";
            }
            if ($show_in_index_type == 'nested_with_url') {
                $field_type = $show_in_index_type;
            }
            switch ($field_type) {
                case 'nested_with_url':
                case 'related_to':
                    if ($row_related_item = $row->{$relation_name}) {
                        $related_url = optional($row_related_item)->url;
                        $related_title = related_info_card($row_related_item);
                        $popover = "<a class='text-primary' href='{$related_url}' target='_blank'>  $related_title $icon</a>";
                    }
                    break;
                case 'youtube_link':
                    if ($row->{$field_name}) {
                        foreach (explode(',', $row->{$field_name}) ?? [] as $video) {
                            $title = "($video) ".__sitecore_trans('open_in_youtube');
                            $popover .= "<a href='https://www.youtube.com/watch?v={$video}' target='_blank'>  $title $icon</a>";
                        }
                    }
                    break;
                case 'external_link':
                default:
                    $aUrl = $row->{$field_name};
                    $aTitle = $row->{$field_name};
                    $popover = "<a href='{$aUrl}' class='text-primary' data-toggle='tooltip' title='click to open' target='_blank'>   $aTitle $icon</a>";
                    break;
            }
        }

        return $popover;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('replace_regex_format')) {
    function replace_regex_format($regex, $replace_values)
    {
        $regexArray = str_split($regex);
        $i = 0;
        foreach ($regexArray as $k => $val) {
            if ($val == '?') {
                $regexArray[$k] = $replace_values[$i] ?? null;
                $i++;
            }
        }

        return implode('', $regexArray);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_calculate_fields_with_regex')) {
    function get_calculate_fields_with_regex($module, $cols = null)
    {
        if (!$cols) {
            $moduleClass = get_cpanel_module($module, 'model');
            $moduleColsClass = $moduleClass::$columns;
            $cols = new $moduleColsClass;
        }
        $calculate_columns = $cols->getCalculateColumns();

        $calculate_relation_fields = [];
        $columns_regex = [];
        foreach ($calculate_columns as $column) {
            $field_name = $column['name'];
            if ($regex = $column['calculate_relation']['regex'] ?? null) {
                $columns_regex[$field_name] = $regex;
            }
            $fields = $column['calculate_relation']['fields'] ?? [];
            if (count($fields)) {
                $calculate_relation_fields[$field_name] = $fields;
            }
        }

        return [
            "calculate_relation_fields" => $calculate_relation_fields,
            "regex"                     => $columns_regex,
        ];

    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('calculate_relation_fields')) {
    function calculate_relation_fields($moduleClass, $column, $row)
    {
        $moduleColsClass = $moduleClass::$columns;
        $cols = new $moduleColsClass;
        if ($cols->isInCalculateRelations($column)) {
            $module = get_cpanel_module_key($moduleClass);
            $data = get_calculate_fields_with_regex($module, $cols);
            $columns_regex = $data['regex'] ?? [];
            collect($columns_regex)->map(function ($originalRegex, $calculate_column) use ($row, $data) {
                $fields = $data['calculate_relation_fields'][$calculate_column] ?? [];

                $fieldValues = collect($fields)->map(function ($field) use ($row) {
                    return $row->{$field} ?? 0; // val
                })->toArray();

                $regex = replace_regex_format($originalRegex, $fieldValues);
                $result = eval("return {$regex};");
                $row->{$calculate_column} = $result;
            });
        }

        return $row;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('calculate_field')) {
    function calculate_field($moduleClass, $column, $row)
    {
        $moduleColsClass = $moduleClass::$columns;
        $cols = new $moduleColsClass;
        if ($cols->isInCalculateColumns($column)) {
            $data = $column['calculate_relation'] ?? [];
            $columns_regex = $data['regex'] ?? [];
            $calculate_column = $column['name'];
            collect($columns_regex)->map(function ($originalRegex) use ($row, $data, $calculate_column) {
                $fields = $data['fields'] ?? [];

                $fieldValues = collect($fields)->map(function ($field) use ($row) {
                    return $row->{$field} ?? 0; // val
                })->toArray();

                $regex = replace_regex_format($originalRegex, $fieldValues);
                $result = eval("return {$regex};");
                $row->{$calculate_column} = $result;
            });
        }

        return $row;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_list_option')) {
    function get_list_option($scope)
    {
        return ListOption::cacheListOption($scope);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

