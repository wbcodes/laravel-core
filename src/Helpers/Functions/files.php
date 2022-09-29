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
            unlinkOldFile($old_Path, $folder);
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

