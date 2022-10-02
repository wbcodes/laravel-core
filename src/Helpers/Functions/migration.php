<?php
/*
 * Migration helper functions
 * --------------------------------------------------
 */
use Illuminate\Database\Schema\Blueprint;

/*-----------------------------------------------------------------------------------*/

if (!function_exists('seo_columns')) {
    /**
     * @param Blueprint $table
     *
     * @return mixed
     */
    function seo_columns(Blueprint $table)
    {
        $table->string('slug', 255);
        $table->string('seo_title', 255)->nullable();
        $table->string('seo_description', 400)->nullable();
        $table->string('seo_keywords', 400)->nullable();
    }
}
/*---------------------------------- </> ----------------------------------*/

if (!function_exists('locked_columns')) {
    /**
     * @param Blueprint $table
     *
     * @return mixed
     */
    function locked_columns(Blueprint $table)
    {
        $table->unsignedBigInteger('locked_by')->nullable();
        $table->timestamp('locked_at')->nullable();
    }
}
/*---------------------------------- </> ----------------------------------*/

if (!function_exists('active_visits_columns')) {
    /**
     * @param Blueprint $table
     *
     * @return mixed
     */
    function active_visits_columns(Blueprint $table)
    {
        $table->integer('visits')->default(0);
        $table->integer('active')->default(1);
    }
}
/*---------------------------------- </> ----------------------------------*/

if (!function_exists('editor_columns')) {
    /**
     * @param Blueprint $table
     *
     * @return mixed
     */
    function editor_columns(Blueprint $table)
    {
        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('updated_by')->nullable();
        $table->unsignedBigInteger('deleted_by')->nullable();
        $table->unsignedBigInteger('restored_by')->nullable();
        $table->timestamps();
        $table->softDeletes();
        $table->timestamp('restored_at')->nullable();
    }
}
/*---------------------------------- </> ----------------------------------*/
