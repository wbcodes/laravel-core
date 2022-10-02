<?php


// Upload and Remove Images
//------------------------------------
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


if (!function_exists('file_generate_hash')) {

    /**
     * Generate filename to store
     * @return string
     */
    function file_generate_hash()
    {
        return md5(time()).Str::random(3).'_'.rand(100, 999).Str::random(3);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('get_folder_path')) {
    function get_folder_path($folder)
    {
        // get folder public path
        $path = public_path($folder);

        // check if folder exist
        if (!file_exists($path)) {
            File::makeDirectory($path, 0777, true);
        }

        return $path;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('store_file')) {
    /**
     * @param $path
     * @param $file
     *
     * @return string
     */
    function store_file($path, $file)
    {
        // get file extension
        $extension = $file->getClientOriginalExtension();
        // filename to store
        // time +_+ 00 + XXX + 000 + x + 0000 = // time_00XXX000x0000.png
        $hash = md5(time()).'_'.rand(10, 99).Str::random(3).rand(100, 999).chr(rand(65, 90)).rand(1000, 9999);

        $filename = $hash.'.'.$extension;

        $file->move($path, $filename);

        return $filename;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('upload_image')) {
    /**
     * @param $request
     * @param $field_name
     * @param $folder
     * @param $old_image
     *
     * @return string
     */
    function upload_image($request, $field_name, $folder, $old_image = null)
    {
        //delete old file
        if ($old_image) {
            unlink_file($old_image, $folder);
        }

        // public path for folder
        $path = get_folder_path($folder);

        // get file from request
        $file = $request->file($field_name);

        // save file in folder and return name
        return store_file($path, $file);
    }
}
/*---------------------------------- </> ----------------------------------*/


if (!function_exists('upload_images')) {
    /**
     * @param $request
     * @param $field_name
     * @param $folder
     *
     * @return array
     */
    function upload_images($request, $field_name, $folder)
    {
        // public path for folder
        $path = get_folder_path($folder);

        $arrFileNames = [];
        // get just extension
        foreach ($request->file($field_name) as $file) {
            // save file in folder and return name
            $arrFileNames[] = store_file($path, $file);
        }

        return $arrFileNames;
    }
}
/*---------------------------------- </> ----------------------------------*/

if (!function_exists('attach_files')) {
    /**
     * @param $request
     * @param $field_name
     * @param $folder
     * @param $row
     * @param $type
     *
     * @return array
     */
    function attach_files($request, $field_name, $folder, $row = null)
    {

        // public path for folder
        $path = get_folder_path($folder);

        $arrFileNames = [];
        // get just extension
        foreach ($request->file($field_name) as $file) {
            // filename to store
            $original_name = $file->getClientOriginalName();
            $size = $file->getSize();

            $type = getItemIfExists(explode('/', $file->getMimeType()), 0);

            // save file in folder and return name
            $storage_name = store_file($path, $file);

            $arrFileNames[] = saveAttachments($request, $row, $original_name, $storage_name, $folder, $size, $type);
        }

        return $arrFileNames;
    }
}
/*---------------------------------- </> ----------------------------------*/

if (!function_exists('saveAttachments')) {
    function saveAttachments($request, $row, $original_name, $storage_name, $folder, $size, $type)
    {
//        $file = new Attachment();
//        $file->file_name = $original_name;
//        $file->storage_name = $storage_name;
//        $file->path = $folder;
//        $file->size = $size;
//        $file->type = $type;
//        if ($row) {
//            $row->attachments()->save($file);
//        } else {
//            $file->attachable_id = $request->attachable_id;
//            $file->attachable_type = $request->attachable_type;
//            $file->save();
//        }
//
//        return $file;
    }
}
/*---------------------------------- </> ----------------------------------*/

if (!function_exists('unlink_file')) {
    /**
     * for delete file from directory.
     *
     * @param $fileName   ( obj->file )
     * @param $folderName ('uploads/folderName')
     */
    function unlink_file($fileName, $folderName)
    {
        // get file source
        if ($fileName && $fileName != '') {
            $old = public_path($folderName.'/'.$fileName);
            if (File::exists($old)) {
                // unlink or remove previous image from folder
                unlink($old);
            }
        }
    }
}
/*---------------------------------- </> ----------------------------------*/

if (!function_exists('upload_from_tiny')) {
    /**
     * @param        $request
     * @param string $field_name
     * @param        $folder
     *
     * @return mixed
     */
    function upload_from_tiny($request, $field_name, $folder)
    {
        try {
            $folder = "uploads/{$folder}";

            $file = $request->file($field_name);

            $path = get_folder_path($folder);

            $hash = 'image_'.time().'_'.$file->hashName();

            $filename = $file->move($path, $hash);

            $path = asset("uploads/{$folder}/{$filename}");

            return response(['location' => $path], 200);
        } catch (Exception $exp) {
            return response(['location' => $exp], 401);
        }
    }
}
/*---------------------------------- </> ----------------------------------*/

if (!function_exists('fancy_image')) {
    /**
     * @param     $prefix
     * @param     $imageName
     * @param int $width
     * @param     $alt
     * @param     $className
     *
     * @return string
     */
    function fancy_image($prefix, $imageName, $width = 100, $alt = null, $className = null)
    {
        $className = $className != null ? $className : 'img-thumbnail';
        $height = $className == 'img-circle' ? $width : 'auto';
        if (!file_exists((public_path("{$prefix}/{$imageName}")))) {
            return '';
        }
        $output = "<a class='grouped_elements' data-fancybox='group' data-caption='{$imageName}' href='/{$prefix}/{$imageName}'>";
        $output .= "<img src='/{$prefix}/{$imageName}' class='{$className}' width='{$width}' height='{$height}' alt='{$alt}'/>";
        $output .= '</a>';

        return $output;
    }
}
/*---------------------------------- </> ----------------------------------*/


if (!function_exists('file_size')) {

    /**
     * @param $size
     * @param $unit
     * @return string
     */
    function file_size($size, $unit = null)
    {
        $size = $size ?? 0;
        $unit = Str::lower($unit ?? 'kb');
        if ($size) {
            switch ($unit) {
                case 'mb':
                    $size = $size / 1048576;
                    break;
                case 'gb':
                    $size = $size / 1073741824;
                    break;
                case 'kb':
                default:
                    $size = $size / 1024;
                    break;
            }
        }

        $file_size = number_format($size, 2);

        return "{$file_size} {$unit}";
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('unlink_old_file')) {

    /**
     * for delete file from directory
     * @param $fieldName  ( obj->image )
     * @param $public_file_dir  ('storage/FOLDERNAME')
     */
    function unlink_old_file($fieldName, $public_file_dir)
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

if (!function_exists('file_upload')) {

    /**
     * @param      $request
     * @param      $field_name
     * @param      $folder
     * @param  null  $old_Path
     * @return string
     */
    function file_upload($request, $field_name, $folder, $old_Path = null)
    {
        // get file extension
        $extension = $request->file($field_name)->getClientOriginalExtension();

        // generate filename to store
        $hash = file_generate_hash();

        $fileNameToStore = $hash.'.'.$extension;

        // delete old image
        if ($old_Path) {
            unlink_old_file($old_Path, $folder);
        }

        // check if folder exist
        if (!Storage::exists(storage_path($folder))) {
            Storage::makeDirectory(storage_path($folder));
        }

        $request->file($field_name)->storeAs($folder, $fileNameToStore, 'public');

        return $fileNameToStore;
    }
}
/*---------------------------------------{</>}---------------------------------------*/

