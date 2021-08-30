<?php


// Upload and Remove Images
//------------------------------------
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (!function_exists('generate_hash_file')) {

    /**
     * Generate filename to store
     * @return string
     */
    function generate_hash_file()
    {
        return md5(time()).Str::random(3).'_'.rand(100, 999).Str::random(3);
    }
}
/*---------------------------------------{</>}---------------------------------------*/

if (!function_exists('upload_image')) {

    /**
     * @param      $request
     * @param      $field_name
     * @param      $folder
     * @param  null  $old_image
     * @return string
     */
    function upload_image($request, $field_name, $folder, $old_image = null)
    {
        // get file extension
        $extension = $request->file($field_name)->getClientOriginalExtension();

        // generate filename to store
        $hash = generate_hash_file();

        $fileNameToStore = $hash.'.'.$extension;

        // delete old image
        if ($old_image) {
            unlinkOldFile($old_image, $folder);
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

if (!function_exists('file_size')) {

    /**
     * @param $file_size
     * @return string
     */
    function file_size($file_size)
    {
        $file_size = number_format($file_size / 1048576, 2);

        return $file_size.' MB';
    }
}
/*---------------------------------------{</>}---------------------------------------*/