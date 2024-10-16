<?php

namespace App\Providers;

use App\Models\POS\Payments;


use Illuminate\Pagination\Paginator;
use App\Models\MMIS\inventory\Delivery;
use Illuminate\Support\ServiceProvider;
use App\Observers\POS\TransactionObserver;
use App\Observers\Appointment\PatientMasterObserver;
use App\Observers\MMIS\InventoryTransactionObserver;
use App\Observers\Appointment\MedsysOutpatientObserver;

use App\Observers\HISPatientMasterObserver;
use App\Observers\HISPatientRegistryObserver;
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
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();
        Payments::observe(TransactionObserver::class);
        Delivery::observe(InventoryTransactionObserver::class);
        // Patient::observe(PatientMasterObserver::class);
        // PatientRegistry::observe(MedsysOutpatientObserver::class);
        // CashAssessment::observe(CashAssessmentObserver::class);

        Patient::observe(HISPatientMasterObserver::class);
        PatientRegistry::observe(HISPatientRegistryObserver::class);

    }
}
