<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('signup', [UserController::class,'signup']);
Route::get('/verifyEmail/{email}/{token}', [UserController::class,'verifyemail']);
Route::get('/verifyphone', [UserController::class,'verifyphone']);
Route::post('login', [UserController::class,'login'])->middleware('login');
Route::post('/forgetpassword', [UserController::class,'forgetpassword'])->middleware("forgetpassword");
Route::put('updatepassword/{email}/{token}', [UserController::class,'updatepassword']);

Route::middleware(['Jwt'])->group(function (){
Route::post('upload/profile/photo', [UserController::class,'profilephoto']);
Route::post('upload/doucment/photo', [UserController::class,'documentphoto']);
Route::post('upload/id/photo', [UserController::class,'idphoto']);
});
