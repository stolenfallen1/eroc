<?php

namespace App\Models\MMIS\inventory;

use App\Models\User;
use App\Models\MMIS\Audit;
use App\Models\BuildFile\Branchs;
use App\Models\BuildFile\Vendors;
use App\Models\Approver\InvStatus;
use App\Models\BuildFile\Warehouses;
use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\inventory\Consignment;
use App\Models\MMIS\inventory\ConsignmentItems;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\MMIS\inventory\PurchaseOrderConsignmentItem;

class PurchaseOrderConsignment extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'purchaseOrderConsignment';
    protected $guarded = [];

    protected $appends = ['audit_code'];

    public function rr_consignment_master(){
        return $this->belongsTo(Consignment::class, 'rr_id','id');
    }

    public function purchaseRequest(){
        return $this->belongsTo(PurchaseRequest::class, 'pr_request_id','id');
    }
    public function consignmentPr()
    {
        return $this->hasMany(PurchaseOrderConsignment::class, 'rr_id', 'rr_id');
    }
    public function consignmentPo()
    {
        return $this->hasMany(PurchaseOrderConsignment::class, 'rr_id', 'rr_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderConsignmentItem::class, 'po_consignment_id', 'id');
    }
    
    public function purchaseOrder(){
        return $this->belongsTo(purchaseOrderMaster::class, 'po_id','id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendors::class, 'vendor_id','id');
    }
    public function receiver()
    {
        return $this->belongsTo(User::class, 'createdby', 'idnumber');
    }
    public function getAuditCodeAttribute(){
        // Assuming 'code' is the attribute that holds the PO number in purchaseOrderMaster and PurchaseRequest models
        $poCode = $this->purchaseOrder ? $this->purchaseOrder->code : '';
        $prCode = $this->purchaseRequest ? $this->purchaseRequest->code : '';
        $rrCode = $this->rr_consignment_master ? $this->rr_consignment_master->code : '';
        return $rrCode.' - '.$poCode.'- Invoice Number- '.$this->invoice_no;
    }
}
