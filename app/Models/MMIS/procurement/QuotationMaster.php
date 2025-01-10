<?php

namespace App\Models\MMIS\procurement;

use App\Models\User;
use App\Models\BuildFile\Branchs;
use App\Models\BuildFile\Vendors;
use App\Models\BuildFile\Warehouses;
use App\Models\BuildFile\Itemmasters;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Unitofmeasurement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuotationMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_mmis';
    protected $table = 'CDG_MMIS.dbo.RFQMaster';
    protected $guarded = [];

    protected $appends = ['encrypted_key_id'];

    public function purchaseRequestDetail(){
        return $this->belongsTo(PurchaseRequestDetails::class, 'pr_request_detail_id');
    }

    public function purchaseRequest(){
        return $this->belongsTo(PurchaseRequest::class, 'pr_request_id');
    }
    
    public function item(){
        return $this->belongsTo(Itemmasters::class, 'pr_Document_Item_Id','id');
    }

    
    public function vendor()
    {
        return $this->belongsTo(Vendors::class, 'rfq_document_Vendor_Id');
    }

    public  function user(){
        return $this->belongsTo(User::class, 'rfq_document_IssuedBy','idnumber');
    }

    
    public function branch()
    {
        return $this->belongsTo(Branchs::class, 'rfq_document_branch_id');
    }

    public  function warehouse(){
        return $this->belongsTo(Warehouses::class, 'rfq_document_warehouse_id');
    }
    
    public function unit()
    {
        return $this->belongsTo(Unitofmeasurement::class, 'pr_Document_Item_Approved_UnitofMeasurement_Id');
    }

    public function getCurrencyAttribute(){
        $currency = $this->po_Document_currency_id == 1 ? "₱" :"$";
        return $currency;
    }

    public function setKeyIdAttribute($value){
        $this->attributes['id'] = Crypt::encrypt($value);
    }

    public function getEncryptedKeyIdAttribute()
    {
        return Crypt::encrypt($this->attributes['id']);
    }
}
