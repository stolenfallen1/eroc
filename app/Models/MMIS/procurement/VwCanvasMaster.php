<?php

namespace App\Models\MMIS\procurement;

use App\Models\User;
use App\Models\BuildFile\Vendors;
use App\Models\BuildFile\Itemmasters;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Unitofmeasurement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VwCanvasMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_mmis';
    protected $table = 'CDG_MMIS.dbo.VwCanvas';
    protected $guarded = [];
    
    protected $appends = ['currency'];
    
    public function purchaseRequestDetail(){
        return $this->belongsTo(PurchaseRequestDetails::class, 'pr_request_details_id');
    }
    public function item(){
        return $this->belongsTo(Itemmasters::class, 'canvas_Item_Id');
    }
    public function purchaseRequest(){
        return $this->belongsTo(PurchaseRequest::class, 'pr_request_id');
    }

    public function attachments(){
        return $this->hasMany(CanvasAttachment::class, 'canvas_id', 'id');
    }

    public function canvaser(){
        return $this->belongsTo(User::class, 'canvas_Document_CanvassBy', 'idnumber');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendors::class, 'vendor_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unitofmeasurement::class, 'canvas_Item_UnitofMeasurement_Id');
    }

    public function purchaseOrderDetails()
    {
        return $this->hasMany(PurchaseOrderDetails::class, 'canvas_id', 'id');
    }

    public function comptroller(){
        return $this->belongsTo(User::class, 'canvas_Level2_ApprovedBy', 'idnumber');
    }

    public function getCurrencyAttribute(){
        $currency = $this->currency_id == 1 ? "₱" :"$";
        return $currency;
    }
}
