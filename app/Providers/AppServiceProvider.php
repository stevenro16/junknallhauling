<?php

namespace App\Providers;

use App\Models\Inquiry;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Feed the admin sidebar its workqueue badge + current username.
        View::composer('partials.admin.sidebar', function ($view) {
            $byStatus = Inquiry::query()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
            $total = ($byStatus['new'] ?? 0) + ($byStatus['reviewing'] ?? 0) + ($byStatus['quoted'] ?? 0)
                + ($byStatus['scheduled'] ?? 0) + ($byStatus['service_performed'] ?? 0);

            $view->with('workqueueTotal', (int) $total)
                ->with('currentUsername', session('admin_username'));
        });
    }
}
