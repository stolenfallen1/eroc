<?php

namespace App\Models\MMIS\procurement;

use App\Models\User;
use App\Models\BuildFile\Branchs;
use App\Models\Approver\InvStatus;
use App\Models\BuildFile\Priority;
use App\Models\BuildFile\ItemGroup;
use App\Models\BuildFile\Warehouses;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Itemcategories;
use App\Models\BuildFile\Itemsubcategories;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\MMIS\procurement\PurchaseRequestDetails;
use App\Models\MMIS\procurement\VwPurchaseRequestDetails;

class PurchaseRequest extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'CDG_MMIS.dbo.purchaseRequestMaster';

    protected $guarded = [];
    protected $appends = ['code','encrypted_key_id'];
    // protected $fillable = [

    // ];

    public function purchaseRequestAttachments(){
        return $this->hasMany(PurchaseRequestAttachment::class, 'pr_request_id', 'id');
    }

    public function purchaseRequestDetails(){
        return $this->hasMany(PurchaseRequestDetails::class, 'pr_request_id', 'id')->whereNull('IsFreeGoods');
    }
    public function newpurchaseRequestDetails(){
        return $this->hasMany(VwPurchaseRequestDetails::class, 'pr_request_id', 'id')->whereNull('IsFreeGoods');
    }
    public  function status(){
        return $this->belongsTo(InvStatus::class, 'pr_Status_Id', 'id');
    }

    public  function priority(){
        return $this->belongsTo(Priority::class, 'pr_Priority_Id');
    }

    public  function warehouse(){
        return $this->belongsTo(Warehouses::class, 'warehouse_Id');
    }

    public  function user(){
        return $this->belongsTo(User::class, 'pr_RequestedBy', 'idnumber');
    }

    public  function departmentApprovedBy(){
        return $this->belongsTo(User::class, 'pr_DepartmentHead_ApprovedBy', 'idnumber');
    }

    public  function administratorApprovedBy(){
        return $this->belongsTo(User::class, 'pr_Branch_Level1_ApprovedBy', 'idnumber');
    }
    public  function consultantApprovedBy(){
        return $this->belongsTo(User::class, 'pr_Branch_Level2_ApprovedBy', 'idnumber');
    }

    
    public  function departmentDeclinedBy(){
        return $this->belongsTo(User::class, 'pr_DepartmentHead_CancelledBy', 'idnumber');
    }

    public  function administrator(){
        return $this->belongsTo(User::class, 'pr_Branch_Level1_ApprovedBy', 'idnumber');
    }

    public  function category(){
        return $this->belongsTo(Itemcategories::class, 'item_Category_Id');
    }

    public  function subcategory(){
        return $this->belongsTo(Itemsubcategories::class, 'item_SubCategory_Id');
    }

    public function itemGroup()
    {
        return $this->belongsTo(ItemGroup::class, 'invgroup_id');
    }

    public function canvases()
    {
        return $this->hasMany(CanvasMaster::class, 'pr_request_id', 'id');
    }

    public function purchaseOrder()
    {
        return $this->hasMany(purchaseOrderMaster::class, 'pr_Request_id', 'id');
    }

    public function branch()
    {
        return $this->belongsTo(Branchs::class, 'branch_Id');
    }

    public function getCodeAttribute(){
        return generateCompleteSequence($this->pr_Document_Prefix, $this->pr_Document_Number, $this->pr_Document_Suffix, "-");
    }
    public function setKeyIdAttribute($value){
        $this->attributes['id'] = Crypt::encrypt($value);
    }
    public function getEncryptedKeyIdAttribute()
    {
        return Crypt::encrypt($this->attributes['id']);
    }
}