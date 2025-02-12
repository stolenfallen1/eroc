<?php

use App\Http\Controllers\BuildFile\Hospital\BadHabitsController;
use App\Http\Controllers\BuildFile\Hospital\DietMealsController;
use App\Http\Controllers\BuildFile\Hospital\DietMealTypeController;
use App\Http\Controllers\BuildFile\Hospital\DietSubTypeController;
use App\Http\Controllers\BuildFile\Hospital\DietTypeController;
use App\Http\Controllers\BuildFile\Hospital\DispositionController;
use App\Http\Controllers\BuildFile\Hospital\mscHospitalRoomStatusController;
use App\Http\Controllers\HIS\AllergyTypeController;
use App\Http\Controllers\HIS\CaseIndicatorController;
use App\Http\Controllers\HIS\DosagesController;
use App\Http\Controllers\HIS\HISHospitalRoomsController;
use App\Http\Controllers\HIS\MedicalPackageMasterController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SystemReportsController;
use App\Http\Controllers\BuildFile\ItemController;
use App\Http\Controllers\BuildFile\UnitController;
use App\Http\Controllers\GlobalSettingsController;
use App\Http\Controllers\BuildFile\BrandController;
use App\Http\Controllers\Database\DriverController;
use App\Http\Controllers\BuildFile\BranchController;
use App\Http\Controllers\BuildFile\VendorController;
use App\Http\Controllers\BuildFile\ApproverController;
use App\Http\Controllers\BuildFile\CategoryController;
use App\Http\Controllers\BuildFile\mscPriceController;
use App\Http\Controllers\BuildFile\PriorityController;
use App\Http\Controllers\BuildFile\SupplierController;
use App\Http\Controllers\BuildFile\AntibioticController;
use App\Http\Controllers\BuildFile\DepartmentController;
use App\Http\Controllers\BuildFile\DosageFormController;
use App\Http\Controllers\BuildFile\GenericNameController;
use App\Http\Controllers\BuildFile\mscCurrencyController;
use App\Http\Controllers\BuildFile\SubCategoryController;
use App\Http\Controllers\BuildFile\vendor\TypeController;
use App\Http\Controllers\BuildFile\Hospital\SexController;
use App\Http\Controllers\BuildFile\MscWarehouseController;
use App\Http\Controllers\BuildFile\vendor\LevelController;
use App\Http\Controllers\BuildFile\vendor\TermsController;
use App\Http\Controllers\BuildFile\ApproverLevelController;
use App\Http\Controllers\BuildFile\mscPriceGroupController;
use App\Http\Controllers\BuildFile\SystemSettingController;
use App\Http\Controllers\UserManager\UserManagerController;
use App\Http\Controllers\BuildFile\address\RegionController;
use App\Http\Controllers\BuildFile\ClassificationController;
use App\Http\Controllers\BuildFile\FMS\CostCenterController;
use App\Http\Controllers\BuildFile\Hospital\BanksController;
use App\Http\Controllers\BuildFile\InventoryGroupController;
use App\Http\Controllers\BuildFile\address\CountryController;
use App\Http\Controllers\BuildFile\address\ZipcodeController;
use App\Http\Controllers\BuildFile\FMS\AccountTypeController;
use App\Http\Controllers\BuildFile\Hospital\DoctorController;
use App\Http\Controllers\BuildFile\Hospital\StatusController;
use App\Http\Controllers\BuildFile\Hospital\SuffixController;
use App\Http\Controllers\BuildFile\MscManufacturerController;
use App\Http\Controllers\BuildFile\address\BarangayController;
use App\Http\Controllers\BuildFile\address\ProvinceController;
use App\Http\Controllers\BuildFile\EmployeePositionController;
use App\Http\Controllers\BuildFile\FMS\AccountClassController;
use App\Http\Controllers\BuildFile\FMS\AccountGroupController;
use App\Http\Controllers\BuildFile\FMS\MedicareTypeController;
use App\Http\Controllers\BuildFile\FMS\RevenueClassController;
use App\Http\Controllers\BuildFile\Hospital\CompanyController;
use App\Http\Controllers\BuildFile\Hospital\IDTypesController;
use App\Http\Controllers\BuildFile\TherapeuticClassController;
use App\Http\Controllers\BuildFile\Hospital\BuildingController;
use App\Http\Controllers\BuildFile\Hospital\CaseTypeController;
use App\Http\Controllers\BuildFile\MscWarehouseGroupController;
use App\Http\Controllers\BuildFile\DrugAdministrationController;
use App\Http\Controllers\BuildFile\Hospital\BedStatusController;
use App\Http\Controllers\BuildFile\Hospital\BloodTypeController;
use App\Http\Controllers\BuildFile\Hospital\DeathTypeController;
use App\Http\Controllers\BuildFile\Hospital\ReligionsController;
use App\Http\Controllers\BuildFile\Hospital\AgeBracketController;
use App\Http\Controllers\BuildFile\Hospital\DebitCardsController;
use App\Http\Controllers\BuildFile\Hospital\RefundTypeController;
use App\Http\Controllers\BuildFile\address\MunicipalityController;
use App\Http\Controllers\BuildFile\FMS\TransactionCodesController;
use App\Http\Controllers\BuildFile\Hospital\CivilStatusController;
use App\Http\Controllers\BuildFile\Hospital\CreditCardsController;
use App\Http\Controllers\BuildFile\Hospital\BankAccountsController;
use App\Http\Controllers\BuildFile\Hospital\HospitalPlanController;
use App\Http\Controllers\BuildFile\Hospital\ServicesTypeController;
use App\Http\Controllers\BuildFile\Hospital\AdmissionTypeController;
use App\Http\Controllers\BuildFile\Hospital\HospitalRoomsController;
use App\Http\Controllers\BuildFile\Hospital\NationalitiesController;
use App\Http\Controllers\BuildFile\Hospital\PaymentMethodController;
use App\Http\Controllers\BuildFile\Hospital\Setting\ModuleController;
use App\Http\Controllers\BuildFile\Hospital\Setting\SystemController;
use App\Http\Controllers\BuildFile\Hospital\ShiftSchedulesController;
use App\Http\Controllers\BuildFile\Hospital\AdmissionSourceController;
use App\Http\Controllers\BuildFile\Hospital\TransactionTypeController;
use App\Http\Controllers\BuildFile\Hospital\DoctorCategoriesController;
use App\Http\Controllers\BuildFile\Hospital\HospitalModalityController;
use App\Http\Controllers\BuildFile\Hospital\HospitalServicesController;
use App\Http\Controllers\BuildFile\Hospital\PatientRelationsController;
use App\Http\Controllers\BuildFile\Hospital\Setting\SubModuleController;
use App\Http\Controllers\BuildFile\Hospital\Setting\SubSystemController;
use App\Http\Controllers\BuildFile\Hospital\HospitalRoomsClassController;
use App\Http\Controllers\BuildFile\Hospital\HospitalRoomsStatusController;
use App\Http\Controllers\BuildFile\FMS\TransactionClassificationController;
use App\Http\Controllers\BuildFile\Hospital\DoctorSpecializationController;
use App\Http\Controllers\BuildFile\Hospital\HospitalSevicesSectionController;
use App\Http\Controllers\BuildFile\Hospital\HospitalItemandSuppliesController;
use App\Http\Controllers\BuildFile\Hospital\HospitalRoomsAccomodationController;

Route::controller(CategoryController::class)->group(function () {

    Route::get('services-categories', 'getServicesCategory');
    Route::get('categories', 'getAllCategory');

    Route::get('sub-categories', 'getAllSubCategories');
    Route::get('classifications', 'getAllClassifications');
    Route::get('supplier-categories', 'getAllSupplierCategories');
    Route::get('supplier-terms-all', 'getAllSupplierTerms');


    Route::post('get-category-list', 'mscAllcategory');
    Route::get('get-categories', 'list');
    Route::post('create-category', 'store');
    Route::put('update-category/{id}', 'update');
    Route::delete('delete-category/{id}', 'destroy');
});

Route::controller(HospitalSevicesSectionController::class)->group(function () {
    Route::get('services-section', 'list');
});


Route::resource('modalities', HospitalModalityController::class);
Route::controller(HospitalModalityController::class)->group(function () {
    Route::get('get-modalities', 'list');
});



Route::controller(VendorController::class)->group(function () {
    Route::get('vendors', 'index');
    Route::get('get-vendors', 'vendorList');
    Route::post('vendors', 'store');
    Route::put('vendors/{vendor}', 'update');
    Route::delete('vendors/{vendor}', 'destroy');
});

Route::controller(ItemController::class)->group(function () {
    Route::get('items', 'searchItem');
    Route::get('item-list', 'searchItems');
    Route::get('warehouse-location-items', 'searchwarehouseItem');
    Route::get('items-group', 'getItemGroup');
    Route::get('services-items-group', 'getServicesItemGroup');

});




Route::controller(UnitController::class)->group(function () {
    Route::get('units', 'index');
    Route::get('get-units', 'list');
    Route::post('create-unit', 'store');
    Route::put('update-unit/{id}', 'update');
    Route::delete('delete-unit/{id}', 'destroy');

});

Route::controller(PriorityController::class)->group(function () {
    Route::get('priorities', 'index');
    Route::get('get-priorities', 'list');
    Route::post('create-priorities', 'store');
    Route::put('update-priorities/{id}', 'update');
    Route::delete('delete-priorities/{id}', 'destroy');

});

Route::controller(SystemSettingController::class)->group(function () {
    Route::get('system-settings', 'getPRSNSequences');
});

Route::controller(BrandController::class)->group(function () {
    Route::get('brand', 'index');
    Route::get('get-brand', 'list');
    Route::post('create-brand', 'store');
    Route::put('update-brand/{id}', 'update');
    Route::delete('delete-brand/{id}', 'destroy');

});

Route::controller(AntibioticController::class)->group(function () {
    Route::get('antibiotic', 'index');
    Route::get('get-antibiotic-class', 'list');
    Route::post('create-antibiotic-class', 'store');
    Route::put('update-antibiotic-class/{id}', 'update');
    Route::delete('delete-antibiotic-class/{id}', 'destroy');
});

Route::controller(GenericNameController::class)->group(function () {
    Route::get('generic-name', 'index');
    Route::get('get-generic-name', 'list');
    Route::post('create-generic-name', 'store');
    Route::put('update-generic-name/{id}', 'update');
    Route::delete('delete-generic-name/{id}', 'destroy');
});

Route::resource('dosages', DosageFormController::class);
Route::controller(DrugAdministrationController::class)->group(function () {
    Route::get('drug-administration', 'index');
    Route::get('dosage-forms', 'dosageForms');
    Route::get('get-administration', 'list');
    Route::post('create-administration', 'store');
    Route::put('update-administration/{id}', 'update');
    Route::delete('delete-administration/{id}', 'destroy');

});

Route::controller(TherapeuticClassController::class)->group(function () {
    Route::get('therapeutic-class', 'index');
    Route::get('get-therapeutic-class', 'list');
    Route::post('create-therapeutic-class', 'store');
    Route::put('update-therapeutic-class/{id}', 'update');
    Route::delete('delete-therapeutic-class/{id}', 'destroy');
});

Route::controller(BranchController::class)->group(function () {
    Route::get('branches', 'index');
    Route::get('get-branches', 'list');
    Route::post('create-branch', 'store');
    Route::put('update-branch/{id}', 'update');
    Route::delete('delete-branch/{id}', 'destroy');
});

Route::controller(DepartmentController::class)->group(function () {
    Route::get('departments', 'index');
    Route::get('get-departments-list', 'getDepartmentList');
    Route::get('department-sections', 'getSections');
    Route::post('get-department-access', 'UserDeptAccess');

    Route::post('add-department-access', 'add_department_access');
    Route::post('remove-department-access', 'remove_department_access');
});



Route::controller(InventoryGroupController::class)->group(function () {
    Route::get('get-inventory-group', 'list');
    Route::get('get-list-inventory-group', 'index');
    Route::post('create-inventory-group', 'store');
    Route::put('update-inventory-group/{id}', 'update');
    Route::delete('delete-inventory-group/{id}', 'destroy');

});

Route::controller(SubCategoryController::class)->group(function () {
    Route::post('get-sub-category-list', 'mscAllSubcategory');
    Route::get('get-subcategories', 'list');
    Route::post('create-sub-category', 'store');
    Route::put('update-sub-category/{id}', 'update');
    Route::delete('delete-sub-category/{id}', 'destroy');
});


Route::controller(ClassificationController::class)->group(function () {
    Route::post('get-classification', 'classification');
    Route::post('create-sub-category-classification', 'store');
    Route::put('update-sub-category-classification/{id}', 'update');
    Route::delete('delete-sub-category-classification/{id}', 'destroy');
});


Route::controller(MscWarehouseController::class)->group(function () {
    Route::get('get-warehouse', 'list');
    Route::get('warehouse-list', 'warehouselist');
    Route::get('get-branch-warehouse', 'branch_warehouse');
    Route::get('get-branch', 'branch');
    Route::get('get-warehouse-list', 'warehousegroup');

    Route::post('create-warehouse', 'store');
    Route::put('update-warehouse/{id}', 'update');
    Route::delete('delete-warehouse/{id}', 'destroy');

});

Route::controller(MscWarehouseGroupController::class)->group(function () {
    Route::get('get-warehouse-group', 'list');
    Route::post('create-warehouse-group', 'store');
    Route::put('update-warehouse-group/{id}', 'update');
    Route::delete('delete-warehouse-group/{id}', 'destroy');

});

Route::controller(MscManufacturerController::class)->group(function () {
    Route::get('get-manufacturer', 'list');
    Route::post('create-manufacturer', 'store');
    Route::put('update-manufacturer/{id}', 'update');
    Route::delete('delete-manufacturer/{id}', 'destroy');

});


Route::controller(ApproverController::class)->group(function () {
    Route::get('get-approver', 'list');
    Route::get('get-user-list', 'users');
    Route::get('get-approvers-level', 'approver_level');
    Route::post('create-approver', 'store');
    Route::put('update-approver/{id}', 'update');
    Route::delete('delete-approver/{id}', 'destroy');

});


Route::controller(ApproverLevelController::class)->group(function () {
    Route::get('get-approver-level', 'index');
    Route::post('create-approver-level', 'store');
    Route::put('update-approver-level/{id}', 'update');
    Route::delete('delete-approver-level/{id}', 'destroy');
});



Route::controller(mscCurrencyController::class)->group(function () {
    Route::get('get-currencies', 'index');
    Route::get('currencies', 'getCurrencies');
    Route::post('create-currencies', 'store');
    Route::put('update-currencies/{id}', 'update');
    Route::delete('delete-currencies/{id}', 'destroy');

});


Route::controller(mscPriceGroupController::class)->group(function () {
    Route::get('get-price-groups', 'index');
    Route::get('list-price-groups', 'list');
    Route::post('create-price-groups', 'store');
    Route::put('update-price-groups/{id}', 'update');
    Route::delete('delete-price-groups/{id}', 'destroy');

});

// Route::controller(mscPriceGroupController::class)->group(function () {
//     Route::get('get-price-groups', 'index');
//     Route::get('list-price-groups', 'list');
//     Route::post('create-price-groups', 'store');
//     Route::put('update-price-groups/{id}', 'update');
//     Route::delete('delete-price-groups/{id}', 'destroy');
// });


Route::controller(mscPriceController::class)->group(function () {
    Route::get('get-price-schemes', 'index');
    Route::post('create-price-schemes', 'store');
    Route::put('update-price-schemes/{id}', 'update');
    Route::delete('delete-price-schemes/{id}', 'destroy');
    Route::get('list-price-schemes', 'list');
});

Route::resource('consultants', DoctorController::class);
Route::controller(DoctorController::class)->group(function () {
    Route::get('search-doctors', 'list');
    Route::get('get-doctors', 'list');
    Route::get('doctors', 'index');
    Route::get('get-his-doctors', 'his_list');
    Route::get('doctors-categories', 'doctorsCategories');
    Route::get('doctors-specialization', 'doctorsSpecialization');
    Route::get('get-his-professional-details', 'index');
    Route::get('get-his-reader-details', 'index');
});

Route::controller(HospitalRoomsAccomodationController::class)->group(function () {
    Route::get('get-room-accomodations', 'list');
});

Route::controller(HospitalRoomsClassController::class)->group(function () {
    Route::get('get-room-class', 'list');
});
Route::controller(HospitalRoomsStatusController::class)->group(function () {
    Route::get('get-room-status', 'list');
});
Route::resource('hospital-room-status', mscHospitalRoomStatusController::class);

Route::controller(CompanyController::class)->group(function () {
    Route::get('get-companies', 'list');
    Route::get('get-company-list', 'index');
});

Route::controller(CountryController::class)->group(function () {
    Route::get('get-countries', 'index');
    Route::get('get-country-list', 'list');
    Route::post('create-countries', 'store');
    Route::put('update-countries/{id}', 'update');
    Route::delete('delete-countries/{id}', 'destroy');

});

Route::controller(RegionController::class)->group(function () {
    Route::get('get-regions', 'index');
    Route::post('create-regions', 'store');
    Route::put('update-regions/{id}', 'update');
    Route::delete('delete-regions/{id}', 'destroy');

});

Route::controller(ProvinceController::class)->group(function () {
    Route::get('get-provinces', 'index');
    Route::get('get-province', 'province');
    Route::post('create-provinces', 'store');
    Route::put('update-provinces/{id}', 'update');
});


Route::controller(MunicipalityController::class)->group(function () {
    Route::get('get-municipalities', 'index');
    Route::get('get-municipality', 'municipality');
    Route::post('create-municipalities', 'store');
    Route::put('update-municipalities/{id}', 'update');
});


Route::controller(BarangayController::class)->group(function () {
    Route::get('get-barangay', 'index');
    Route::get('get-barangays', 'list');
    Route::post('create-barangay', 'store');
    Route::put('update-barangay/{id}', 'update');
});

Route::controller(ZipcodeController::class)->group(function () {
    Route::get('zip-code-list', 'list');
});
Route::resource('zip-codes', ZipcodeController::class);
Route::resource('supplier-level', LevelController::class);
Route::resource('supplier-terms', TermsController::class);
Route::resource('supplier-types', TypeController::class);



Route::controller(UserController::class)->group(function () {
    Route::get('user-list', 'index');
    Route::delete('delete-user-information/{id}', 'destroy');
});
Route::resource('save-user-information', UserController::class);



Route::controller(RoleController::class)->group(function () {
    Route::get('get-role', 'list');
    Route::get('get-inv-level', 'getlevel');
    Route::get('get-permissions', 'permission');
    Route::get('get-role-permission', 'role_permission');

    Route::post('submit-selected-permission', 'save_permission');
    Route::post('add-permission', 'add_permission');
    Route::delete('delete-roles/{id}', 'destroy');

});
Route::resource('roles', RoleController::class);
Route::resource('positions', EmployeePositionController::class);



// ======================== hospital build file ==========================
Route::controller(SystemController::class)->group(function () {
    Route::get('systems-list', 'list');
});

Route::resource('systems', SystemController::class);
Route::resource('sub-systems', SubSystemController::class);

Route::controller(ModuleController::class)->group(function () {
    Route::get('module-list', 'list');
    Route::get('get-system-modules', 'systemModule');
    Route::get('systems-drivers', 'systemsdriver');
    Route::get('systems-sidebar', 'getSidebar');
});



Route::resource('system-modules', ModuleController::class);
Route::resource('system-sub-modules', SubModuleController::class);


Route::controller(AdmissionSourceController::class)->group(function () {
    Route::get('get-admission-source', 'list');
});
Route::resource('admission-source', AdmissionSourceController::class);
Route::controller(AdmissionTypeController::class)->group(function () {
    Route::get('get-admission-type', 'list');
});
Route::resource('admission-type', AdmissionTypeController::class);
Route::resource('age-bracket', AgeBracketController::class);
Route::resource('bed-status', BedStatusController::class);
Route::resource('blood-types', BloodTypeController::class);
Route::resource('death-type', DeathTypeController::class);

Route::controller(DeathTypeController::class)->group(function () {
    Route::get('get-death-type', 'list');
});
Route::resource('case-type', CaseTypeController::class);
Route::controller(CaseTypeController::class)->group(function () {
    Route::get('get-case-type', 'list');
});

Route::controller(TransactionTypeController::class)->group(function () {
    Route::get('get-transaction-type', 'list');
});
Route::resource('transaction-type', TransactionTypeController::class);

Route::controller(HospitalPlanController::class)->group(function () {
    Route::get('get-hospital-plan', 'list');
});
Route::resource('hospital-plan', HospitalPlanController::class);
Route::controller(IDTypesController::class)->group(function () {
    Route::get('get-id-types', 'list');
});
Route::resource('id-types', IDTypesController::class);

Route::controller(ReligionsController::class)->group(function () {
    Route::get('get-religions', 'list');
});
Route::resource('religions', ReligionsController::class);

Route::controller(NationalitiesController::class)->group(function () {
    Route::get('get-nationalities', 'list');
});
Route::resource('nationalities', NationalitiesController::class);

Route::controller(SexController::class)->group(function () {
    Route::get('get-sex', 'list');
});
Route::resource('sex', SexController::class);

Route::controller(CivilStatusController::class)->group(function () {
    Route::get('get-civil-status', 'list');
});

Route::resource('civil-status', CivilStatusController::class);
Route::resource('statuses', StatusController::class);
Route::controller(StatusController::class)->group(function () {
    Route::get('get-his-status', 'his_status');
});
Route::resource('patient-relations', PatientRelationsController::class);


Route::controller(DoctorCategoriesController::class)->group(function () {
    Route::get('get-doctor-categories', 'list');
});
Route::resource('doctor-categories', DoctorCategoriesController::class);


Route::controller(ServicesTypeController::class)->group(function () {
    Route::get('get-services-type', 'list');
});
Route::resource('services-type', ServicesTypeController::class);


Route::controller(SuffixController::class)->group(function () {
    Route::get('get-suffix', 'list');
    Route::get('get-titles', 'titles');
});
Route::resource('suffix', SuffixController::class);
Route::controller(PaymentMethodController::class)->group(function () {
    Route::get('get-payment-methods', 'list');
});
Route::resource('payment-methods', PaymentMethodController::class);
Route::resource('refund-type', RefundTypeController::class);
Route::resource('shift-schedules', ShiftSchedulesController::class);
Route::controller(ShiftSchedulesController::class)->group(function () {
    Route::get('get-shift-schedules', 'list');
});
Route::resource('banks', BanksController::class);

Route::controller(BanksController::class)->group(function () {
    Route::get('get-banks', 'list');
});
Route::resource('bank-accounts', BankAccountsController::class);
Route::controller(CreditCardsController::class)->group(function () {
    Route::get('get-credit-cards', 'list');
});
Route::resource('credit-cards', CreditCardsController::class);
Route::controller(DebitCardsController::class)->group(function () {
    Route::get('get-debit-cards', 'list');
});
Route::resource('debit-cards', DebitCardsController::class);





Route::controller(BuildingController::class)->group(function () {
    Route::get('buildings', 'list');
    Route::get('assign-station', 'listofAssignStation');
    Route::post('assigned-station', 'store_assignedstation');
});


Route::resource('submit-rooms-and-beds', HospitalRoomsController::class);
Route::controller(HospitalRoomsController::class)->group(function () {
    Route::get('rooms-and-beds', 'index');
});

Route::controller(HISHospitalRoomsController::class)->group(function () {
    Route::get('get-station-list', 'getStation');
    Route::get('get-ipd-rooms', 'index');
});


Route::resource('item-and-services', HospitalServicesController::class);
Route::controller(HospitalServicesController::class)->group(function () {
    Route::get('search-services', 'search');
});


Route::resource('item-and-supplies', HospitalItemandSuppliesController::class);
Route::controller(HospitalItemandSuppliesController::class)->group(function () {
    Route::get('search-item-and-supplies', 'search');
    Route::get('report-count', 'report_count');
});


Route::resource('doctor-specialization', DoctorSpecializationController::class);

Route::resource('bad-habits', BadHabitsController::class);

Route::resource('diet-meals', DietMealsController::class);
Route::resource('diet-type', DietTypeController::class);
Route::resource('diet-sub-type', DietSubTypeController::class);
Route::resource('diet-meal-type', DietMealTypeController::class);

Route::resource('disposition-type', DispositionController::class);

// ==================end hospital build=============================



// ==================FMS build=============================
Route::resource('account-classes', AccountClassController::class);

Route::controller(AccountClassController::class)->group(function () {
    Route::get('get-account-class', 'list');
});

Route::resource('account-types', AccountTypeController::class);
Route::controller(AccountTypeController::class)->group(function () {
    Route::get('get-account-type', 'list');
});

Route::resource('account-groups', AccountGroupController::class);
Route::resource('cost-centers', CostCenterController::class);

Route::controller(MedicareTypeController::class)->group(function () {
    Route::get('get-medicare', 'list');
});

Route::resource('medicare-types', MedicareTypeController::class);
Route::resource('revenue-classes', RevenueClassController::class);
Route::resource('transaction-classifications', TransactionClassificationController::class);
Route::resource('transaction-codes', TransactionCodesController::class);

Route::controller(TransactionCodesController::class)->group(function () {
    Route::get('get-transaction-codes', 'list');
    Route::get('revenue-code', 'revenuecode');
    Route::get('charge-code', 'chargingcode');
    Route::post('get-charges-code', 'chargingcode');


    Route::post('get-revenue-access', 'UserRevenueCodeAccess');
    Route::post('add-revenue-access', 'add_revenue_access');
    Route::post('remove-revenue-access', 'remove_revenue_access');

    // FOR HIS
    Route::post('get-his-charges', 'hischargeslist');
    Route::get('get-charges-specimen', 'chargespecimen');
});
Route::resource('database-drivers', DriverController::class);

Route::controller(DosagesController::class)->group(function () {
    Route::get('get-dosages', 'index');
});


Route::controller(GlobalSettingsController::class)->group(function () {
    Route::get('get-other-settings', 'list');
    Route::post('get-other-user-access', 'getuseraccess');
    Route::post('add-globalsetting-access', 'add_user_access');
    Route::post('remove-globalsetting-access', 'remove_user_access');

    // FOR HIS 
    Route::get('get-his-setup-options', 'his_list');
    Route::post('update-his-setup-options', 'updateglobalsetting');
});
Route::resource('global-settings', GlobalSettingsController::class);
Route::controller(SystemReportsController::class)->group(function () {
    Route::get('reports', 'list');
    Route::post('get-assigned-report', 'assigned_report');
    Route::post('add-report-access', 'add_report_access');
    Route::post('add-report', 'addreport');
    Route::post('remove-report-access', 'remove_report_access');
    Route::get('get-reports', 'mscReportlist');
});

Route::controller(AllergyTypeController::class)->group(function () {
    Route::get('get-allergy-type', 'index');
    Route::get('get-allergy-symptoms', 'getAllergySymptoms');

    Route::post('create-allergy-type', 'store');
    Route::put('update-allergy-type/{id}', 'update');
    Route::put('archive-allergy-type/{id}', 'archive');
});

Route::controller(CaseIndicatorController::class)->group(function () {
    Route::get('get-case-indicators', 'list');
});

Route::controller(MedicalPackageMasterController::class)->group(function () {
    Route::get('get-medical-package', 'index');
});

Route::resource('system-reports', SystemReportsController::class);