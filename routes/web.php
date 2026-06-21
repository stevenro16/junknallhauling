<?php

use App\Http\Controllers\Admin\AddressController;
use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EquipmentController as AdminEquipmentController;
use App\Http\Controllers\Admin\InquiryApiController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\RentalAgreementController as AdminRentalAgreementController;
use App\Http\Controllers\Admin\ServiceCatalogController;
use App\Http\Controllers\Admin\SiteContentController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\RentalAgreementController;
use App\Http\Controllers\Public\StatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
*/
Route::view('/', 'public.home')->name('home');
Route::view('/services', 'public.services')->name('services');
Route::view('/about', 'public.about')->name('about');
Route::view('/reviews', 'public.reviews')->name('reviews');

Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::get('/status', [StatusController::class, 'index'])->name('status');

Route::get('/rental-agreement/{token}', [RentalAgreementController::class, 'show'])->name('rental-agreement.show');

/*
|--------------------------------------------------------------------------
| Admin authentication (no guard)
|--------------------------------------------------------------------------
*/
Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

/*
|--------------------------------------------------------------------------
| Admin portal (session-guarded)
|--------------------------------------------------------------------------
*/
Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    // Reachable while must_change_password is set (excluded from the redirect).
    Route::get('/change-password', [AuthController::class, 'showChangePassword'])->name('change-password');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change-password.update');
    Route::get('/me', [AuthController::class, 'me'])->name('me');
    Route::patch('/me', [AuthController::class, 'updateMe'])->name('me.update');

    // Everything else requires the password to be changed first.
    Route::middleware('admin.password')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/inquiries/{id}', [InquiryController::class, 'show'])->name('inquiries.show');
        Route::get('/rental-agreement/{id}', [AdminRentalAgreementController::class, 'show'])->name('rental-agreement.show');

        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
        // Placeholder replaced in Phase 9.
        Route::get('/print/rental-agreement/{id}', fn (string $id) => 'Print — built in Phase 9')->name('print.rental-agreement');

        // Admin accounts CRUD (JSON).
        Route::get('/admins', [AdminAccountController::class, 'index'])->name('admins.index');
        Route::post('/admins', [AdminAccountController::class, 'store'])->name('admins.store');
        Route::patch('/admins/{id}', [AdminAccountController::class, 'update'])->name('admins.update');
        Route::delete('/admins/{id}', [AdminAccountController::class, 'destroy'])->name('admins.destroy');

        // Admin JSON API (session + CSRF).
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/inquiries', [InquiryApiController::class, 'index'])->name('inquiries.index');
            Route::post('/inquiries', [InquiryApiController::class, 'store'])->name('inquiries.store');
            Route::get('/inquiries/counts', [InquiryApiController::class, 'counts'])->name('inquiries.counts');
            Route::get('/inquiries/{id}', [InquiryApiController::class, 'show'])->name('inquiries.show');
            Route::patch('/inquiries/{id}', [InquiryApiController::class, 'update'])->name('inquiries.update');
            Route::get('/inquiries/{id}/history', [InquiryApiController::class, 'history'])->name('inquiries.history');
            Route::post('/inquiries/{id}/audit', [InquiryApiController::class, 'audit'])->name('inquiries.audit');
            Route::post('/inquiries/{id}/rental-agreement', [InquiryApiController::class, 'agreement'])->name('inquiries.agreement');
            Route::delete('/rental-agreement/{id}', [AdminRentalAgreementController::class, 'destroy'])->name('rental-agreement.destroy');

            // Service catalog
            Route::get('/services', [ServiceCatalogController::class, 'index'])->name('services.index');
            Route::post('/services', [ServiceCatalogController::class, 'store'])->name('services.store');
            Route::patch('/services/{id}', [ServiceCatalogController::class, 'update'])->name('services.update');
            Route::delete('/services/{id}', [ServiceCatalogController::class, 'destroy'])->name('services.destroy');

            // Address autocomplete (OpenStreetMap)
            Route::get('/address-suggest', [AddressController::class, 'suggest'])->name('address.suggest');

            // Site content (marketing copy + serving areas)
            Route::patch('/content', [SiteContentController::class, 'update'])->name('content.update');

            // Equipment catalog
            Route::get('/equipment', [AdminEquipmentController::class, 'index'])->name('equipment.index');
            Route::post('/equipment', [AdminEquipmentController::class, 'store'])->name('equipment.store');
            Route::patch('/equipment/{id}', [AdminEquipmentController::class, 'update'])->name('equipment.update');
            Route::delete('/equipment/{id}', [AdminEquipmentController::class, 'destroy'])->name('equipment.destroy');
        });
    });
});
