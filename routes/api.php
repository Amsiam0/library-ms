<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\BookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\CategoryController;

Route::name('api.v1.')->prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');


    Route::middleware('auth:sanctum')->group(function () {

        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::apiResource('books', BookController::class);
        Route::post('books/{book}/stock', [BookController::class, 'stock'])->name('books.stock');
        Route::post('/book-loan-request', [BookController::class, 'requestBookLoan']);
        Route::get('/book-loans', [BookController::class, 'getBookLoans']);
        Route::put('/book-loans/{id}/request-due-date', [BookController::class, 'requestUpdateDueDate']);
        Route::resource('categories', CategoryController::class);
    });
});
