<?php

namespace App\Models\MMIS\procurement;

use App\Models\Approver\invStatus;
use App\Models\BuildFile\Itemcategories;
use App\Models\BuildFile\ItemGroup;
use App\Models\BuildFile\Itemsubcategories;
use App\Models\BuildFile\Priority;
use App\Models\BuildFile\Warehouses;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'purchaseRequestMaster';

    protected $guarded = [];
    // protected $fillable = [

    // ];

    public function purchaseRequestAttachments(){
        return $this->hasMany(PurchaseRequestAttachment::class, 'pr_request_id', 'id');
    }

    public function purchaseRequestDetails(){
        return $this->hasMany(PurchaseRequestDetails::class, 'pr_request_id', 'id');
    }

    public  function status(){
        return $this->belongsTo(invStatus::class, 'pr_Status_Id', 'id');
    }

    public  function priority(){
        return $this->belongsTo(Priority::class, 'pr_Priority_Id');
    }

    public  function warehouse(){
        return $this->belongsTo(Warehouses::class, 'warehouse_Id');
    }

    public  function user(){
        return $this->belongsTo(User::class, 'pr_RequestedBy');
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
}