<?php

namespace App\Models\MMIS\inventory;

use App\Models\Approver\InvStatus;
use App\Models\BuildFile\Branchs;
use App\Models\BuildFile\Vendors;
use App\Models\BuildFile\Warehouses;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'RRMaster';

    protected $guarded = [];

    protected $appends = ['po_number', 'code'];

    public function branch(){
        return $this->belongsTo(Branchs::class, 'rr_Document_Branch_Id');
    }

    public function purchaseOrder(){
        return $this->belongsTo(purchaseOrderMaster::class, 'po_id');
    }

    public function warehouse(){
        return $this->belongsTo(Warehouses::class, 'rr_Document_Warehouse_Id');
    }

    public function items()
    {
        return $this->hasMany(DeliveryItems::class, 'rr_id', 'id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendors::class, 'rr_Document_Vendor_Id');
    }

    public function status()
    {
        return $this->belongsTo(InvStatus::class, 'rr_Status');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'rr_received_by');
    }

    public function getPoNumberAttribute(){
        return generateCompleteSequence($this->po_Document_Prefix, $this->po_Document_Number, $this->po_Document_Suffix, "-");
    }

    public function getCodeAttribute(){
        return generateCompleteSequence($this->rr_Document_Prefix, $this->rr_Document_Number, $this->rr_Document_Suffix, "-");
    }
}
