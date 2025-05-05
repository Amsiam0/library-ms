<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\BookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\CategoryController;

Route::name('api.v1.')->prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');


    Route::middleware('auth:sanctum')->group(function () {
        Route::post('users', [AuthController::class, 'store'])->name('users.create');
        Route::get('users', [AuthController::class, 'index'])->name('users.index');
        Route::put('users/change-password', [AuthController::class, 'changePassword'])->name('users.change-password');


        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::apiResource('books', BookController::class);
        Route::put('books/{book}/stock', [BookController::class, 'increaseStock'])->name('books.stock');
        Route::post('/book-loan-request', [BookController::class, 'requestBookLoan']);
        Route::get('/book-loans', [BookController::class, 'getBookLoans']);
        Route::put('/book-loans/{id}/request-due-date', [BookController::class, 'requestUpdateDueDate']);
        Route::resource('categories', CategoryController::class);


        // Feedback routes
        Route::get('/feedback/latest', [FeedbackController::class, 'getLatestFeedback']);
        Route::post('/books/{book}/feedback', [FeedbackController::class, 'store']);
        Route::get('/books/{book}/feedback', [FeedbackController::class, 'getBookFeedback']);
    });
});
