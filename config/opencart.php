<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CONFIG OPENCART
    |--------------------------------------------------------------------------
    |
    */

    'db_prefix'     =>  env('DB_PREFIX', 'oc_'),
    'url_api_oc'    =>  env('URL_API_OC',''),
    'url_image'     =>  env('URL_IMAGE',''),
    'token'         =>  env('TOKEN','1234567890'),
    'language_id'   =>  env('LANGUAGE_ID',2),
    'store_id'      =>  env('STORE_ID',2),
    'path_image'    =>  env('PATH_IMAGE',''),
    'api_username'  =>  env('API_USERNAME',''),
    'api_key'       =>  env('API_KEY','')

];
