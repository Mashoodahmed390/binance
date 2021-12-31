<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class ResponseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Response::macro('success',function($data,$status_code){
            return response()->json([
                'success' => true,
                'message' => $data,
            ],$status_code);
        });
        Response::macro('error',function($data, $status_code){
            return response()->json([
                'success' => false,
                'message' => $data
            ],$status_code);
        });
    }
}
