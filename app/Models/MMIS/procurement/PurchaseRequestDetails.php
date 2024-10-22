<?php

namespace App\Models\MMIS\procurement;

use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\Unitofmeasurement;
use App\Models\BuildFile\Vendors;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestDetails extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_mmis';
    protected $table = 'purchaseRequestDetail';
    protected $guarded = [];
    protected $appends = ['full_path'];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class, 'pr_request_id');
    }

    public function itemMaster()
    {
        return $this->belongsTo(Itemmasters::class, 'item_Id');
    }

    public function canvases()
    {
        return $this->hasMany(CanvasMaster::class, 'pr_request_details_id', 'id');
    }
    
    public function recommendedCanvas()
    {
        return $this->hasOne(CanvasMaster::class, 'pr_request_details_id')->where('isRecommended', 1);
    }

    public function changedRecommendedCanvas()
    {
        return $this->belongsTo(CanvasMaster::class, 'recommended_supplier_id','id')->where('isRecommended', 1);
    }


    public function purchaseOrderDetails(){
        return $this->hasOne(PurchaseOrderDetails::class, 'pr_detail_id', 'id');
    }

    public function preparedSupplier(){
        return $this->belongsTo(Vendors::class, 'prepared_supplier_id', 'id');
    }

    public function unit(){
        return $this->belongsTo(Unitofmeasurement::class, 'item_Request_Department_Approved_UnitofMeasurement_Id');
    }
    public function unit2(){
        return $this->belongsTo(Unitofmeasurement::class, 'item_Request_UnitofMeasurement_Id');
    }

    public function getFullPathAttribute()
    {
        if ($this->filepath) {
            return config('app.url') . $this->filepath;
        }
    }

    public function depApprovedBy(){
        return $this->belongsTo(User::class, 'pr_DepartmentHead_ApprovedBy', 'idnumber');
    }

    public function depCancelledBy(){
        return $this->belongsTo(User::class, 'pr_DepartmentHead_CancelledBy', 'idnumber');
    }

    public function adminApprovedBy(){
        return $this->belongsTo(User::class, 'pr_Branch_Level1_ApprovedBy', 'idnumber');
    }

    public function adminCancelledBy(){
        return $this->belongsTo(User::class, 'pr_Branch_Level1_CancelledBy', 'idnumber');
    }

    public function conApprovedBy(){
        return $this->belongsTo(User::class, 'pr_Branch_Level2_ApprovedBy', 'idnumber');
    }

    public function conCancelledBy(){
        return $this->belongsTo(User::class, 'pr_Branch_Level2_CancelledBy', 'idnumber');
    }
}
