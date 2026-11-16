<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerMenuController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/table/{qrCode}', [CustomerMenuController::class, 'index'])
    ->name('customer.menu');
Route::get('/table/{qrCode}', [\App\Http\Controllers\CustomerMenuController::class, 'index'])
    ->name('customer.menu');
Route::post('/table/{qrCode}/order', [
    \App\Http\Controllers\CustomerMenuController::class,
    'store'
])->name('customer.order');