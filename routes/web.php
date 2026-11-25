<?php

use App\Http\Controllers\CustomerMenuController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\StaffReportsController;
use App\Http\Controllers\StaffTableQrController;
use App\Http\Controllers\StaffWorkersController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'staff.login');
});

Route::get('/staff/login', [StaffAuthController::class, 'showLoginForm'])
    ->name('staff.login');
Route::post('/staff/login', [StaffAuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('staff.login.store');
Route::post('/staff/logout', [StaffAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('staff.logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::get('/kitchen', [KitchenController::class, 'index'])
        ->name('kitchen');
    Route::post('/kitchen/orders/{order}/status', [KitchenController::class, 'updateStatus'])
        ->name('kitchen.orders.status');
    Route::post('/kitchen/alerts/{alert}/acknowledge', [KitchenController::class, 'acknowledgeAlert'])
        ->name('kitchen.alerts.acknowledge');
    Route::get('/staff/reports', [StaffReportsController::class, 'index'])
        ->middleware('can:manage-restaurant')
        ->name('staff.reports.sales');
    Route::get('/staff/tables/qr', [StaffTableQrController::class, 'index'])
        ->middleware('can:manage-restaurant')
        ->name('staff.tables.qr');
    Route::get('/staff/tables/{table}/qr.svg', [StaffTableQrController::class, 'image'])
        ->middleware('can:manage-restaurant')
        ->name('staff.tables.qr.image');
    Route::get('/staff/workers', [StaffWorkersController::class, 'index'])
        ->middleware('can:manage-restaurant')
        ->name('staff.workers.index');
    Route::post('/staff/workers', [StaffWorkersController::class, 'store'])
        ->middleware('can:manage-restaurant')
        ->name('staff.workers.store');
    Route::post('/staff/workers/upload', [StaffWorkersController::class, 'upload'])
        ->middleware('can:manage-restaurant')
        ->name('staff.workers.upload');
    Route::get('/staff/workers/{worker}/edit', [StaffWorkersController::class, 'edit'])
        ->middleware('can:manage-restaurant')
        ->name('staff.workers.edit');
    Route::put('/staff/workers/{worker}', [StaffWorkersController::class, 'update'])
        ->middleware('can:manage-restaurant')
        ->name('staff.workers.update');
    Route::delete('/staff/workers/{worker}', [StaffWorkersController::class, 'destroy'])
        ->middleware('can:manage-restaurant')
        ->name('staff.workers.destroy');
    Route::post('/staff/workers/{worker}/toggle', [StaffWorkersController::class, 'toggleActive'])
        ->middleware('can:manage-restaurant')
        ->name('staff.workers.toggle');
});

Route::get('/table', [CustomerMenuController::class, 'showTableCodeForm'])
    ->name('customer.table-code');
Route::post('/table', [CustomerMenuController::class, 'resolveTableCode'])
    ->middleware('throttle:10,1')
    ->name('customer.table-code.store');
Route::get('/table/{qrCode}', [CustomerMenuController::class, 'index'])
    ->name('customer.menu');
Route::post('/table/{qrCode}/order', [CustomerMenuController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('customer.order');
Route::post('/table/{qrCode}/cancel', [CustomerMenuController::class, 'cancel'])
    ->middleware('throttle:10,1')
    ->name('customer.order.cancel');
Route::get('/table/{qrCode}/order-status', [CustomerMenuController::class, 'orderStatus'])
    ->middleware('throttle:30,1')
    ->name('customer.order.status');
