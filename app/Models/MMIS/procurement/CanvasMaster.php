<?php

namespace App\Models\MMIS\procurement;

use App\Models\BuildFile\Vendors;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Unitofmeasurement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CanvasMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_mmis';
    protected $table = 'CDG_MMIS.dbo.canvasMaster';
    protected $guarded = [];

    public function purchaseRequestDetail(){
        return $this->belongsTo(PurchaseRequestDetails::class, 'pr_request_details_id');
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
}
