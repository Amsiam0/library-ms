<?php

use App\Http\Controllers\Api\v1\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



use App\Http\Controllers\Api\v1\BookController;

Route::name('api.v1.')->prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/book-loan-request', [BookController::class, 'requestBookLoan']);
        Route::get('/book-loans', [BookController::class, 'getBookLoans']);
        Route::put('/book-loans/{id}/request-due-date', [BookController::class, 'requestUpdateDueDate']);
    });
});
