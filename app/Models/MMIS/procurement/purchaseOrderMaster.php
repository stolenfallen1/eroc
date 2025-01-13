<?php

namespace App\Models\MMIS\procurement;

use App\Models\User;
use App\Models\BuildFile\Branchs;
use App\Models\BuildFile\Vendors;
use App\Models\Approver\InvStatus;
use App\Models\BuildFile\Warehouses;
use Illuminate\Support\Facades\Crypt;
use App\Models\MMIS\inventory\Delivery;
use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\inventory\DeliveryItems;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class purchaseOrderMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_mmis';
    protected $table = 'purchaseOrderMaster';

    protected $appends = ['code','encrypted_key_id','currency'];
    
    protected $guarded = [];

    public function details(){
        return $this->hasMany(PurchaseOrderDetails::class, 'po_id', 'id')
                ->where(function($query) {
                    $query->whereNull('isFreeGoods')
                        ->orWhere('isFreeGoods', 0);
                })->orderBy('pr_detail_id', 'asc');
    }

    public function podetails(){
        return $this->hasMany(PurchaseOrderDetails::class, 'po_id', 'id')->where('isFreeGoods',1);
    }
    public function deliveryItems(){
        return $this->hasManyThrough(
            DeliveryItems::class, 
            Delivery::class, 
            'po_id', 
            'rr_id', 
            'id', 
            'id'
        );
    }

    public function delivery(){
        return $this->hasMany(Delivery::class, 'po_id', 'id');
    }

    public function latestdelivery(){
        return $this->hasOne(Delivery::class, 'po_id', 'id')->orderBy('created_at', 'desc');
    }

    public function purchaseRequest(){
        return $this->belongsTo(PurchaseRequest::class, 'pr_Request_id');
    }

    public function vendor(){
        return $this->belongsTo(Vendors::class, 'po_Document_vendor_id');
    }

    public function branch(){
        return $this->belongsTo(Branchs::class, 'po_Document_branch_id');
    }

    public function warehouse(){
        return $this->belongsTo(Warehouses::class, 'po_Document_warehouse_id');
    }
    
    public function status(){
        return $this->belongsTo(InvStatus::class, 'po_status_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'po_Document_userid', 'idnumber');
    }

    public function administrator(){
        return $this->belongsTo(User::class, 'admin_approved_by', 'idnumber');
    }

    public function comptroller(){
        return $this->belongsTo(User::class, 'comptroller_approved_by', 'idnumber');
    }

    public function corporateAdmin(){
        return $this->belongsTo(User::class, 'corp_admin_approved_by', 'idnumber');
    }

    public function president(){
        return $this->belongsTo(User::class, 'ysl_approved_by', 'idnumber');
    }

    public function getCodeAttribute(){
        return generateCompleteSequence($this->po_Document_prefix, $this->po_Document_number, $this->po_Document_suffix, "-");
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
