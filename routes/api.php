<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\BookController;

use App\Http\Controllers\Api\v1\BookLoanController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\CategoryController;

Route::name('api.v1.')->prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');


    Route::middleware('auth:sanctum')->group(function () {

        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::apiResource('books', BookController::class);
        Route::put('books/{book}/stock', [BookController::class, 'increaseStock'])->name('books.stock');
        Route::post('/book-loan-request', [BookController::class, 'requestBookLoan']);
        Route::get('/book-loans', [BookLoanController::class, 'index']);
        Route::put('/book-loans/{id}/request-due-date', [BookController::class, 'requestUpdateDueDate']);
        Route::resource('categories', CategoryController::class);

        Route::group(['prefix' => 'book-loans', 'middleware'=> [AdminMiddleware::class]], function () {
            Route::put('/{id}/approve', [BookLoanController::class, 'approve'])->name('book-loans.approve');
            Route::put('/{id}/reject', [BookLoanController::class, 'reject'])->name('book-loans.reject');
            Route::put('/{id}/distribute', [BookLoanController::class, 'distribute'])->name('book-loans.distribute');
            Route::put('/{id}/update-return-date', [BookLoanController::class, 'updateReturnDate'])->name('book-loans.update-return-date');
        });


    });
});
