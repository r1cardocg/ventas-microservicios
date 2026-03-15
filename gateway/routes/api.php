<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\VentasController;

Route::post('/register',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);

Route::middleware('auth:api')->group(function () {

    Route::post('/logout',[AuthController::class,'logout']);
    Route::get('/me',[AuthController::class,'me']);

    Route::get('/productos',[InventarioController::class,'getProductos']);
    Route::post('/productos',[InventarioController::class,'createProducto']);
    Route::get('/productos/{id}/stock',[InventarioController::class, 'getStock']);

    Route::get('/ventas',[VentasController::class,'getVentas']);
    Route::post('/ventas',[VentasController::class,'createVenta']);
    Route::get('/ventas/usuario/{usuarioId}',[VentasController::class,'getVentasPorUsuario']);
});
