<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

use App\Models\HIS\services\Patient;
use App\Observers\PatientMasterObserver;

use App\Models\HIS\services\PatientRegistry;
use App\Observers\MedsysOutpatientObserver;

use App\Models\HIS\his_functions\CashAssessment;
use App\Observers\CashAssessmentObserver;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        Patient::observe(PatientMasterObserver::class);
        PatientRegistry::observe(MedsysOutpatientObserver::class);
        // CashAssessment::observe(CashAssessmentObserver::class);
    }
}
