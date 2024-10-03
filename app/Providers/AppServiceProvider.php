<?php

namespace App\Providers;

use App\Observers\HISPatientMasterObserver;
use App\Observers\HISPatientRegistryObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        Patient::observe(HISPatientMasterObserver::class);
        PatientRegistry::observe(HISPatientRegistryObserver::class);

    }
}
