<?php

use App\Http\Controllers\Api\CustomerApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.secret')->prefix('v1')->group(function () {
    Route::get('customers/{identifier}/invoices', [CustomerApiController::class, 'invoices']);
    Route::post('customers/invoices', [CustomerApiController::class, 'invoices']);
});
