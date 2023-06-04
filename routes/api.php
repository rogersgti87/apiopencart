<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::get('/categories',[CategoryController::class,'index']);
Route::get('/categories/{id}',[CategoryController::class,'show']);
Route::post('/categories',[CategoryController::class,'store']);
Route::put('/categories/{category_id}',[CategoryController::class,'update']);
Route::delete('/categories/{category_id}',[CategoryController::class,'destroy']);

Route::get('/products',[ProductController::class,'index']);
Route::get('/products/{id}',[ProductController::class,'show']);
Route::post('/products',[ProductController::class,'store']);
Route::put('/products/{product_id}',[ProductController::class,'update']);
Route::delete('/products/{product_id}',[ProductController::class,'destroy']);
