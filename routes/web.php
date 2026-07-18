<?php

use App\Http\Controllers\Admin\AddressController;
use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AgreementController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployeeCalendarController;
use App\Http\Controllers\Admin\EodReportController;
use App\Http\Controllers\Admin\EquipmentController as AdminEquipmentController;
use App\Http\Controllers\Admin\FieldViewController;
use App\Http\Controllers\Admin\InquiryApiController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\NotificationSettingsController;
use App\Http\Controllers\Admin\PaymentLinkController;
use App\Http\Controllers\Admin\RentalAgreementController as AdminRentalAgreementController;
use App\Http\Controllers\Admin\ServiceCatalogController;
use App\Http\Controllers\Admin\SiteContentController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\PaymentController;
use App\Http\Controllers\Public\QuoteDetailController;
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

Route::get('/sitemap.xml', function () {
    $urls = [route('home'), route('services'), route('about'), route('reviews'), route('contact')];
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
        .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
        .implode('', array_map(fn ($u) => '<url><loc>'.e($u).'</loc></url>', $urls))
        .'</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

Route::get('/rental-agreement/{token}', [RentalAgreementController::class, 'show'])->name('rental-agreement.show');
Route::get('/quote-details/{token}', [QuoteDetailController::class, 'show'])->name('quote-details.show');
Route::get('/pay/{token}', [PaymentController::class, 'show'])->name('payment.show');

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
        // Employee schedule — any logged-in user (their assigned visits only).
        Route::get('/my-schedule', [EmployeeCalendarController::class, 'index'])->name('my-schedule');
        Route::get('/my-schedule/job/{id}', [EmployeeCalendarController::class, 'job'])->name('my-schedule.job');
        Route::post('/my-schedule/job/{id}/status', [EmployeeCalendarController::class, 'updateStatus'])->name('my-schedule.status');
        Route::post('/my-schedule/job/{id}/time/{which}', [EmployeeCalendarController::class, 'recordTime'])
            ->whereIn('which', ['arrival', 'departure'])->name('my-schedule.time');
        Route::post('/my-schedule/job/{id}/photo/{which}', [EmployeeCalendarController::class, 'recordPhoto'])
            ->whereIn('which', ['arrival', 'departure'])->name('my-schedule.photo');
        Route::post('/my-schedule/job/{id}/photo/{which}/remove', [EmployeeCalendarController::class, 'removePhoto'])
            ->whereIn('which', ['arrival', 'departure'])->name('my-schedule.photo-remove');
        Route::post('/my-schedule/job/{id}/sign', [EmployeeCalendarController::class, 'sign'])->name('my-schedule.sign');
        Route::post('/my-schedule/job/{id}/eta', [EmployeeCalendarController::class, 'eta'])->name('my-schedule.eta');
        Route::post('/my-schedule/job/{id}/eta-sent', [EmployeeCalendarController::class, 'etaSent'])->name('my-schedule.eta-sent');
        Route::post('/my-schedule/job/{id}/comment', [EmployeeCalendarController::class, 'addComment'])->name('my-schedule.comment');

        // Inquiry images (photos/signatures) served as files so the job sheet HTML
        // stays small — inlining base64 would exceed the host WAF's response limit.
        Route::get('/job-image/{id}/{kind}/{index}', [EmployeeCalendarController::class, 'jobImage'])
            ->whereIn('kind', ['photos', 'arrival', 'departure', 'signature', 'legacy'])
            ->whereNumber('index')->name('job-image');
        // Rental-agreement / detail-request signatures, served as files for the same reason.
        Route::get('/doc-image/{type}/{id}', [EmployeeCalendarController::class, 'docImage'])
            ->whereIn('type', ['agreement', 'detail'])->name('doc-image');

        // Everything below is full-admin only.
        Route::middleware('role.admin')->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

            // Admin Field View — the employee field experience across all scheduled jobs.
            Route::get('/field', [FieldViewController::class, 'index'])->name('field');
            Route::get('/field/job/{id}', [FieldViewController::class, 'job'])->name('field.job');
            Route::post('/field/job/{id}/status', [FieldViewController::class, 'updateStatus'])->name('field.status');
            Route::post('/field/job/{id}/time/{which}', [FieldViewController::class, 'recordTime'])
                ->whereIn('which', ['arrival', 'departure'])->name('field.time');
            Route::post('/field/job/{id}/photo/{which}', [FieldViewController::class, 'recordPhoto'])
                ->whereIn('which', ['arrival', 'departure'])->name('field.photo');
            Route::post('/field/job/{id}/photo/{which}/remove', [FieldViewController::class, 'removePhoto'])
                ->whereIn('which', ['arrival', 'departure'])->name('field.photo-remove');
            Route::post('/field/job/{id}/sign', [FieldViewController::class, 'sign'])->name('field.sign');
            Route::post('/field/job/{id}/eta', [FieldViewController::class, 'eta'])->name('field.eta');
            Route::post('/field/job/{id}/eta-sent', [FieldViewController::class, 'etaSent'])->name('field.eta-sent');
            Route::post('/field/job/{id}/payment', [FieldViewController::class, 'recordPayment'])->name('field.payment');
            Route::post('/field/job/{id}/comment', [FieldViewController::class, 'addComment'])->name('field.comment');

            Route::get('/inquiries/{id}', [InquiryController::class, 'show'])->name('inquiries.show');
            Route::get('/inquiries/{id}/report', [InquiryController::class, 'report'])->name('inquiries.report');
            Route::get('/rental-agreement/{id}', [AdminRentalAgreementController::class, 'show'])->name('rental-agreement.show');

            Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
            Route::post('/calendar/quick-quote', [CalendarController::class, 'quickQuote'])->name('calendar.quick-quote');
            Route::get('/calendar/embed', [CalendarController::class, 'embed'])->name('calendar.embed');
            Route::get('/customers', [CustomerController::class, 'index'])->name('customers');
            Route::get('/eod-report', [EodReportController::class, 'index'])->name('eod-report');
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
                Route::post('/inquiries/{id}/clone', [InquiryApiController::class, 'clone'])->name('inquiries.clone');
                Route::get('/inquiries/counts', [InquiryApiController::class, 'counts'])->name('inquiries.counts');
                Route::get('/inquiries/{id}', [InquiryApiController::class, 'show'])->name('inquiries.show');
                Route::patch('/inquiries/{id}', [InquiryApiController::class, 'update'])->name('inquiries.update');
                Route::get('/inquiries/{id}/history', [InquiryApiController::class, 'history'])->name('inquiries.history');
                Route::post('/inquiries/{id}/audit', [InquiryApiController::class, 'audit'])->name('inquiries.audit');
                Route::post('/inquiries/{id}/comments', [InquiryApiController::class, 'comment'])->name('inquiries.comment');
                Route::post('/inquiries/{id}/rental-agreement', [InquiryApiController::class, 'agreement'])->name('inquiries.agreement');
                Route::delete('/rental-agreement/{id}', [AdminRentalAgreementController::class, 'destroy'])->name('rental-agreement.destroy');
                Route::post('/inquiries/{id}/payment-link', [InquiryApiController::class, 'paymentLink'])->name('inquiries.payment-link');
                Route::delete('/payment-link/{id}', [PaymentLinkController::class, 'destroy'])->name('payment-link.destroy');
                Route::post('/inquiries/{id}/detail-request', [InquiryApiController::class, 'detailRequest'])->name('inquiries.detail-request');
                Route::delete('/detail-request/{id}', [InquiryApiController::class, 'detailRequestDestroy'])->name('detail-request.destroy');

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

                // Agreements (editable templates attachable to services + equipment)
                Route::get('/agreements', [AgreementController::class, 'index'])->name('agreements.index');
                Route::post('/agreements', [AgreementController::class, 'store'])->name('agreements.store');
                Route::patch('/agreements/{id}', [AgreementController::class, 'update'])->name('agreements.update');
                Route::delete('/agreements/{id}', [AgreementController::class, 'destroy'])->name('agreements.destroy');

                // Per-admin notification preferences (events + channels)
                Route::patch('/notifications', [NotificationSettingsController::class, 'update'])->name('notifications.update');
                // Global customer-facing notification master switches
                Route::patch('/notifications/customer', [NotificationSettingsController::class, 'updateCustomer'])->name('notifications.customer');
                // One-off Twilio test send
                Route::post('/notifications/test-sms', [NotificationSettingsController::class, 'testSms'])->name('notifications.test-sms');
                // One-off test email
                Route::post('/notifications/test-email', [NotificationSettingsController::class, 'testEmail'])->name('notifications.test-email');
            });
        }); // end role.admin
    });
});
