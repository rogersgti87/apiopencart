<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;

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
Route::put('/categories',[CategoryController::class,'update']);
Route::delete('/categories/{category_id}',[CategoryController::class,'destroy']);


Route::get('/manufacturers',[ManufacturerController::class,'index']);
Route::get('/manufacturers/{id}',[ManufacturerController::class,'show']);
Route::post('/manufacturers',[ManufacturerController::class,'store']);
Route::put('/manufacturers',[ManufacturerController::class,'update']);
Route::delete('/manufacturers/{brand_id}',[ManufacturerController::class,'destroy']);

Route::get('/products',[ProductController::class,'index']);
Route::get('/products/{id}',[ProductController::class,'show']);
Route::post('/products',[ProductController::class,'store']);
Route::put('/products',[ProductController::class,'update']);
Route::put('/products-custom',[ProductController::class,'updateCustom']);
Route::delete('/products/{product_id}',[ProductController::class,'destroy']);

Route::get('/orders',[OrderController::class,'index']);
Route::put('/orders',[OrderController::class,'update']);

