<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\BookController;
use App\Http\Controllers\Api\v1\BookLoanController;
use App\Http\Controllers\Api\v1\CategoryController;
use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Controllers\Api\v1\FeedbackController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Middleware\AdminMiddleware;

Route::name('api.v1.')->prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('users', [AuthController::class, 'store'])->name('users.create');
        Route::get('users', [AuthController::class, 'index'])->name('users.index');
        Route::put('users/change-password', [AuthController::class, 'changePassword'])->name('users.change-password');
        Route::get('/users/stats', [AuthController::class, 'userStats'])->name('users.stats');


        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::apiResource('books', BookController::class);
        Route::put('books/{book}/stock', [BookController::class, 'increaseStock'])->name('books.stock');

        Route::resource('categories', CategoryController::class);



        Route::post('/book-loan-request', [BookLoanController::class, 'requestBookLoan']);
        Route::get('/book-loans', [BookLoanController::class, 'index']);
        Route::put('/book-loans/{id}/request-due-date', [BookLoanController::class, 'requestUpdateDueDate']);
        Route::group(['prefix' => 'book-loans', 'middleware' => [AdminMiddleware::class]], function () {
            Route::get('due-date-increase-requests', [BookLoanController::class, 'updateDueDateRequestList'])->name('book-loans.due-date-increase-requests');
            Route::put('/{id}/approve', [BookLoanController::class, 'approve'])->name('book-loans.approve');
            Route::put('/{id}/reject', [BookLoanController::class, 'reject'])->name('book-loans.reject');
            Route::put('/{id}/distribute', [BookLoanController::class, 'distribute'])->name('book-loans.distribute');
            Route::put('/{id}/action-due-date-request/{status}', [BookLoanController::class, 'actionDueDateRequest'])->name('book-loans.action-due-date-request');
            Route::put('/{id}/return', [BookLoanController::class, 'returnBook'])->name('book-loans.return');
        });

        // Feedback routes
        Route::get('/feedback/latest', [FeedbackController::class, 'getLatestFeedback']);
        Route::post('/books/{book}/feedback', [FeedbackController::class, 'store']);
        Route::get('/books/{book}/feedback', [FeedbackController::class, 'getBookFeedback']);

        // Dashboard routes middlewared by admin
        Route::group(['prefix' => 'dashboard', 'middleware' => [AdminMiddleware::class]], function () {
            Route::get('/', [DashboardController::class, 'getAnalytics']);
        });
    });
});
