<?php

/**
 * Author: Zahir Hayrullah
 * create date :  10/04/2020  07:00 AM
 * Last Modified Date: 10/04/2020  07:00 AM.
 */

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;

if (!function_exists('get_auth_user')) {
    /**
     * @param $user
     *
     * @return Authenticatable|null
     */
    function get_auth_user($user = null)
    {
        $key = $user ? "auth_user_{$user->id}" : 'auth_user';
        if (!Session::has($key)) {
            // 1-session again
            $user = $user ?? auth()->user();
            Session::put($key, $user);
        }

        return session($key);
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('user_avatar')) {
    /**
     * @param  $user
     *
     * @return mixed
     */
    function user_avatar($user = null)
    {
        return optional(get_auth_user($user))->avatar;
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('user_roles')) {
    /**
     * @param  $user
     *
     * @return mixed
     */
    function user_roles($user = null)
    {
        return optional(get_auth_user($user))->roles;
    }
}
/*---------------------------------- </> --------------------------------*/


if (!function_exists('jsonOutput')) {
    /**
     * @param $error_array
     * @param $success_output
     *
     * @return array
     */
    function jsonOutput($error_array, $success_output = null)
    {
        return [
            'error'   => $error_array,
            'success' => $success_output,
        ];
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('callAPI')) {
    /**
     * @param $method
     * @param $url
     * @param $data
     *
     * @return bool|string
     */
    function callAPI($method, $url, $data = null)
    {
        // $data must be json when method is post
        $curl = curl_init();
        if (!$curl) {
            return false;
        }

        switch ($method) {
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
        }
        if ($data) {
            curl_setopt($curl, CURLOPT_POST, $data);
            $url = sprintf('%s?%s', $url, http_build_query($data));
        }

        $headers = get_api_headers();
        // OPTIONS:

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        // EXECUTE:
        $result = curl_exec($curl);
//        if (!$result) {
//            die('Connection Failure');
//        }
        curl_close($curl);

        return $result;
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('get_api_headers')) {
    function get_api_headers($headers = [])
    {
        $headers[] = 'Content-Type: application/json';
        if (array_key_exists('HTTP_HOST', $_SERVER)) {
            $headers[] = 'ORIGIN: '.$_SERVER['HTTP_HOST'];
        }
        if (array_key_exists('HTTP_REFERER', $_SERVER)) {
            $headers[] = 'REFERER: '.$_SERVER['HTTP_REFERER'];
        }
        if (array_key_exists('HTTP_ORIGIN', $_SERVER)) {
            $headers[] = 'ORIGIN: '.$_SERVER['HTTP_ORIGIN'];
        }

        return $headers;
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('delete_record')) {
    /**
     * @param $item
     * @param $permissionName
     *
     * @return JsonResponse
     */
    function delete_record($item, $permissionName)
    {
        if (!is_can_delete($permissionName)) {
            return response()->json("You don't have authorize to delete this item.", 401);
        }
//    $item = class_name($modelName)::find($id);
        if ($item) {
            $item->delete();

            return response()->json('The item has been "deleted" successfully', 200);
        }

        return response()->json('The item not found', 404);
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('restore_record')) {
    /**
     * @param $item
     * @param $permissionName
     *
     * @return JsonResponse
     */
    function restore_record($item, $permissionName)
    {
        if (!is_can_restore($permissionName)) {
            return response()->json("You don't have authorize to restore this item.", 401);
        }
//    $item = class_name($modelName)::withTrashed()->find($id);
        if ($item) {
            $item->restore();

            return response()->json('The item has been "restored" successfully', 200);
        }

        return response()->json('The item not found', 404);
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('force_delete_record')) {
    /**
     * @param $item
     * @param $permissionName
     *
     * @return JsonResponse
     */
    function force_delete_record($item, $permissionName)
    {
        if (!is_can_force_delete($permissionName)) {
            return response()->json("You don't have authorize to destroy this item.", 401);
        }
//    $item = class_name($modelName)::withTrashed()->find($id);
        if ($item) {
            $item->forceDelete();

            return response()->json('The item has been "destroyed" successfully', 200);
        }

        return response()->json('The item not found', 404);
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('show_record')) {
    /**
     * @param $request
     * @param $modelName
     * @param $id
     * @param $permissionName
     * @param $with
     *
     * @return Factory|JsonResponse|View|void
     */
    function show_record($request, $modelName, $id, $permissionName = null, $with = null)
    {
        if (!$request->ajax()) {
            // if Request not Ajax
            if (!is_can_show($permissionName) and !is_can_show_all($permissionName)) {
                return view('backend.errors.401');
            }
            abort(404);
        }
        if (!is_can_show($permissionName) and !is_can_show_all($permissionName)) {
            return json_not_authorize();
        }
        if (!is_numeric($id)) {
            return json_not_found_item('Page');
        }
        $class_name = get_class_name($modelName);
        $item = $class_name::with($with)->find($id);
        if (!$item) {
            return json_not_found_item();
        }

        return response()->json($item, 200);
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('increment_visits')) {
    /**
     * increment visits.
     *
     * @param        $row
     * @param string $key is $key_visits_slug
     */
    function increment_visits($row, $key = 'page')
    {
        $key .= '_visits_'.$row->slug;
        if (!Session::has($key)) {
            $row->timestamps = false;
            $row->increment('visits');
            Session::put($key, 1);
        }
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('json_not_found_item')) {
    /**
     * @param  $item_or_page
     * @param  $code
     *
     * @return JsonResponse
     */
    function json_not_found_item($item_or_page = null, $code = null)
    {
        $item_or_page = $item_or_page ?? 'Item';
        $code = $code ?? 404;
        $message = $message ?? $code.". This {$item_or_page} Not found.";

        return response()->json($message, $code);
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('json_not_authorize')) {
    /**
     * @return JsonResponse
     */
    function json_not_authorize()
    {
        return response()->json('not authorize to visit this page', 401);
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('has_trash_param')) {
    /**
     * // This function to check url contains trash or not.
     *
     * @return string
     */
    function has_trash_param()
    {
        $output = '';
        if (strpos($_SERVER['REQUEST_URI'], 'admin/trash')) {
            $output = '/trash';
        }

        return $output;
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('editorInfo')) {
    /**
     * @param $_page
     *
     * @return string
     */
    function editorInfo($_page)
    {
        $output = '';
        $creator = $_page->createdBy ? $_page->createdBy->name : ' system ';
        $editor = $_page->updatedBy ? $_page->updatedBy->name : null;
        $created_title = __('created_by', ['name' => $creator]);
        $created_title_date = __('addition_date', ['date' => $_page->created_at]);
        $modified_title = __('updated_by', ['name' => $editor]);
        $modified_title_date = __('edition_date', ['date' => $_page->updated_at]);

        $output .= '';
        $output .= "<p class='user-date-info'><span data-toggle='tooltip' title='{$created_title}'> <i class='fas fa-plus-square'></i> ".hiddenSm($created_title).' </span>';
        $output .= " - <span data-toggle='tooltip' title='{$created_title_date}'> <i class='fas fa-calendar-plus'></i> ".hiddenSm(optional($_page->created_at)->format('Y-m-d')).' </span></p>';
        if ($editor != null) {
            $output .= "<p class='user-date-info'><span data-toggle='tooltip' title='{$modified_title}'> <i class='fas fa-edit'></i> ".hiddenSm($modified_title).' </span>';
            $output .= " - <span data-toggle='tooltip' title='{$modified_title_date}'> <i class='fas fa-calendar'></i> ".hiddenSm(optional($_page->updated_at)->format('Y-m-d')).' </span></p>';
        }

        return $output;
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('trashInfo')) {
    /**
     * @param $_page
     *
     * @return string
     */
    function trashInfo($_page)
    {
        $output = '';
        $deletedBy = $_page->deletedBy ? $_page->deletedBy->name : ' system ';
        $output .= "<p class='user-date-info'>";
        $output .= " <span data-toggle='tooltip' title='تم حذفه بواسطة {$deletedBy}'> <i class='fas fa-trash'></i> ".hiddenSm('تم حذفه بواسطة :'.$deletedBy).' </span>';
        $output .= " - <span data-toggle='tooltip' title='تاريخ الحذف {$_page->deleted_at}'> <i class='fas fa-calendar-times'></i> ".hiddenSm('بتاريخ :'.optional($_page->deleted_at)->format('Y-m-d')).' </span>';
        $output .= '</p>';

        return $output;
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('hiddenSm')) {
    /**
     * @param $data
     * @param $className
     *
     * @return string
     */
    function hiddenSm($data, $className = null)
    {
        $className = $className ?? 'd-none d-md-inline d-lg-inline d-xl-inline';

        return "<span class='{$className}'>{$data}</span>";
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('titleLink')) {
    /**
     * @param        $prefix
     * @param        $row
     * @param        $can_edit
     * @param string $attr
     *
     * @return string
     */
    function titleLink($prefix, $row, $can_edit, $attr = 'title')
    {
        $output = '<p>';
        foreach (langSymbols() as $symbol) {
            if (isset($row->{langAttr($attr, $symbol)})) {
                $_title = $row->{langAttr($attr, $symbol)};
                $str_title = Str::limit($_title, 50);
                $output .= "<span data-toggle='tooltip' title='{$_title}' >{$str_title}</span><br/>";
                if ($can_edit) {
                    $output .= "<a href='/{$prefix}/{$row->id}/edit' data-toggle='tooltip' title='{$_title}' >{$str_title}</a><br/>";
                }
            }
        }

        return "{$output}</p>";
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('slugLink')) {
    /**
     * @param        $row
     * @param string $prefix
     * @param null   $url
     *
     * @return string
     */
    function slugLink($row, $prefix = '', $url = null)
    {
        if (!$url) {
            $url = url($prefix.'/'.$row->slug);
        }
        //    $fullLink=url($row->slug);
        //    $output = "<a href='$url' target='_blank' data-toggle='tooltip' title='{$fullLink}'>$row->slug</a>";
        return "<a href='{$url}' target='_blank' data-toggle='tooltip' title='زيارة الرابط: {$row->slug}'>{$row->slug}</a>";
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('actionLinks')) {
    /**
     * @param $row
     * @param $prefix
     * @param $user_can_edit
     * @param $user_can_delete
     *
     * @return string
     */
    function actionLinks($row, $prefix, $user_can_edit, $user_can_delete)
    {
        //    if ($modelName == null) {
        //      $modelName = str_replace('admin/', '', $prefix);
        //    }
        if (auth()->check()) {
            $output = '';
            $trans_edit = __('edit');
            if ($prefix == null and $user_can_edit) {
                $output = "<a href='javascript:void(0)' class='btn btn-primary w-100 btn-xs edit' id='{$row->id}'><i class='fas fa-pencil-alt'></i> {$trans_edit} </a>";
            } else {
                if ($user_can_edit) {
                    $output = "<a href='/{$prefix}/{$row->id}/edit' class='btn btn-primary w-100 btn-xs edit' id='{$row->id}'><i class='fas fa-pencil-alt'></i> {$trans_edit} </a>";
                }
            }
            if ($user_can_delete) {
                $trans_delete = __('delete');
                $output .= "<button class='btn btn-danger w-100 btn-xs delete' id='{$row->id}'><i class='fas fa-trash'></i> {$trans_delete} </button>";
            }

            return $output;
        }
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('trashActionLinks')) {
    /**
     * @param $row
     * @param $user_can_restore
     * @param $user_can_force_delete
     *
     * @return string
     */
    function trashActionLinks($row, $user_can_restore, $user_can_force_delete)
    {
        $output = '';
        if (auth()->check()) {
            if ($user_can_restore) {
                $restore = __('restore');
                $output = "<a href='javascript:void(0)' class='btn btn-info w-100 btn-xs btn-restore' id='{$row->id}'><i class='fas fa-trash-restore'></i> {$restore} </a>";
            }
            if ($user_can_force_delete) {
                $force_delete = __('force_delete');
                $output .= "<button class='btn bg-dark w-100 btn-xs btn-force-delete' id='{$row->id}'><i class='fas fa-fire-alt'></i> {$force_delete} </button>";
            }
        }

        return $output;
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('addTableButton')) {
    function addTableButton($modelName, $title = null, $href = null, $name = null, $id = null, $icon = null)
    {
        if (!is_can_create($modelName)) {
            return '';
        }
        if (has_trash_param()) {
            return '';
        }
        $name = $name ?? 'add';
        $id = $id ?? 'add-btn';
        $icon = $icon ?? 'fa-plus';
        if (!$href) {
            $output = "<button class='btn btn-tool {$id}' name='{$name}' id='{$id}'>
          <i class='fas fa-fw {$icon} text-success'></i>
          {$title}
        </button>

          ";
        } else {
            $output = "<a href='{$href}' class='btn btn-tool {$id}' id='{$id}'>
          <i class='fas {$icon} text-success'></i>
          {$title}
        </a>";
        }
        //         <button type="button" class="btn btn-tool" data-card-widget="collapse">
        //          <i class="fas fa-minus"></i>
        //         </button>
        //         <button type="button" class="btn btn-tool" data-card-widget="remove">
        //          <i class="fas fa-times"></i>
        //         </button>
        return $output;
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('addTrashButton')) {
    function addTrashButton($permissionName, $href = null, $params = null)
    {
        $request_has_trashed = has_trash_param() ? false : true;
        if ($request_has_trashed and !is_can_restore($permissionName) and !is_can_force_delete($permissionName)) {
            return '';
        } elseif (!is_can_show($permissionName) and !is_can_show_all($permissionName)) {
            return '';
        }
        $defaultHref = $request_has_trashed ? "/admin/trash/{$href}" : "/admin/{$href}";
        $defaultTitle = $request_has_trashed ? __('button.deleted_records') : __('button.active_records');
        $defaultId = $request_has_trashed ? 'trash_data' : 'all_data';
        $defaultIcon = $request_has_trashed ? 'fa-trash-alt' : 'fa-list';

        $href = url($defaultHref).$params ?? '';
        $title = $title ?? $defaultTitle;
        $id = $id ?? $defaultId;
        $icon = $icon ?? $defaultIcon;

        return "<a href='{$href}' class='btn btn-sm btn-danger float-right text-capitalize' id='{$id}'> <i class='fas {$icon}'></i> {$title} </a>";
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('activeRecordButton')) {
    /**
     * @param $permissionName
     * @param $href
     * @param $params
     * @param $className
     *
     * @return string
     */
    function activeRecordButton($permissionName, $href = null, $params = null, $className = null)
    {
        if (request()->has('active') and (request()->get('active') === 'false')) {
            $href = url("/admin/{$href}");
            $title = $title ?? __('active_records');
            $className = $className ?? 'btn-outline-info';
            $icon = 'fa-fw fa-check-circle';
        } else {
            $href = url("/admin/{$href}?active=false");
            $title = $title ?? __('inactive_records');
            $className = $className ?? 'btn-warning';
            $icon = 'fa-fw fa-times-circle';
        }
        if (!is_can_show($permissionName) and !is_can_show_all($permissionName)) {
            return '';
        }
        if ($params) {
            $href .= $params;
        }
        $id = $id ?? 'all_data';
        $icon = $icon ?? 'fa-fw fa-check-circle';

        return "<div class=''> <a href='{$href}' class='btn {$className} mx-2' id='{$id}'> <i class='fas {$icon}'></i> {$title} </a></div>";
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('activeButton')) {
    function activeButton($item = null)
    {
        if ($item) {
            $checked = old('active', $item->active) != 1 ? '' : 'checked';
        } else {
            $checked = 'checked';
        }
        $active = __('active').' <i class="fas fa-check"></i>';
        $inactive = __('inactive').' <i class="fas fa-times"> </i>';

        return "<div class='checkbox '>
      <!-- edit active button -->
      <input type='checkbox' class='text-center align-middle' name='active' id='_active'
          data-toggle='toggle'
          data-on='{$active}' data-onstyle='success' data-off='{$inactive}' data-offstyle='danger' data-width='125' value='1' {$checked}>
         <!-- edit active button -->
        </div>";
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('copyBtn')) {
    /**
     * @param $shorten_link
     * @param $className
     *
     * @return string
     */
    function copyBtn($shorten_link, $className = null)
    {
        $className = $className ?? 'float-right';

        return " <button class='btn border btn-light btn-clipboard {$className}' data-toggle='tooltip' data-clipboard-text='{$shorten_link}' title='".__('copy')."'>
            <img src='".asset('img/clippy.svg')." ' width='17px' alt='".__('copy_to_clipboard')."'></button>";
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('back_button')) {
    /**
     * @return string
     */
    function back_button()
    {
        $url = url()->previous();
        $title = __('back');

        return "<a href='$url' class=' btn btn-default float-right'> <i class='fas fa-fw fa-chevron-circle-left'></i> $title </a>";
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('class_name')) {
    function class_name($modelName)
    {
        // check name if content \\ dont add App as prefix model
        if (strpos($modelName, '\\') === false) {
            $modelName = "\\App\\Models\\{$modelName}";
        }
        // if(!(class_exists($modelName))) {
        //   return "$modelName model Not Found.";
        // }
        return $modelName;
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('authorized_view')) {
    function authorized_view($permissionName, $viewName, $type)
    {
        if (!is_can_create($permissionName)) {
            return view('backend.errors.401');
        }

        return view($viewName.$type);
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('get_actions_column')) {
    function get_actions_column($datatable, $can_edit, $can_delete, $can_restore, $can_force_delete, $trash)
    {
        $datatable->addColumn('action', function ($row) use ($can_edit, $can_delete, $can_restore, $can_force_delete, $trash) {
            if ($trash) {
                return trashActionLinks($row, $can_restore, $can_force_delete);
            } else {
                return actionLinks($row, $row->adminPrefix, $can_edit, $can_delete);
            }
        });
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('list_of_error_codes')) {
    function list_of_error_codes()
    {
        return config('record_errors.codes');
//    return [
//      ['code' => 401, 'icon' => 'fas fa-fas fa-fw fa-exclamation', 'bg-color' => 'bg-warning', 'color' => 'warning'],
//      ['code' => 403, 'icon' => 'fas fa-fas fa-fw fa-exclamation-circle', 'bg-color' => 'bg-blue', 'color' => 'blue'],
//      ['code' => 404, 'icon' => 'fas fa-fas fa-fw fa-exclamation-triangle', 'bg-color' => 'bg-danger', 'color' => 'danger'],
//      ['code' => 419, 'icon' => 'fas fa-fas fa-fw fa-exclamation-circle', 'bg-color' => 'bg-secondary', 'color' => 'secondary'],
//      ['code' => 429, 'icon' => 'fas fa-fas fa-fw fa-exclamation-circle', 'bg-color' => 'bg-dark', 'color' => 'dark'],
//      ['code' => 500, 'icon' => 'fas fa-fas fa-fw fa-exclamation-triangle', 'bg-color' => 'bg-danger', 'color' => 'danger'],
//      ['code' => 503, 'icon' => 'fas fa-fas fa-fw fa-exclamation', 'bg-color' => 'bg-info', 'color' => 'info'],
//    ];
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('list_of_menu_error_items')) {
    function list_of_menu_error_items()
    {
        $items = [];
        foreach (list_of_error_codes() as $erra) {
            $items[] = [
                'text'       => "{$erra['code']} Error Records ",
                'url'        => "errors-management/records/{$erra['code']}",
                'icon_color' => "{$erra['color']}",
                'icon'       => "{$erra['icon']}",
                //     'can' => 'show-error-records'
            ];
        }

        return $items;
    }
}
/*---------------------------------- </> --------------------------------*/

if (!function_exists('displayVisitsCount')) {
    function displayVisitsCount($value)
    {
        if ($value > 1000000) {
            $number = $value / 1000000;

            return $newVal = number_format($number, 2).'M';
        } else {
            if ($value > 1000) {
                $number = $value / 1000;

                return $newVal = number_format($number, 2).'K';
            }
        }
        //if you want 2 decimal digits
    }
}
/*---------------------------------- </> --------------------------------*/
