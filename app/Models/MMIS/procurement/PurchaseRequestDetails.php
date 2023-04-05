<?php

namespace App\Models\MMIS\procurement;

use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\Vendors;
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

    public function purchaseOrderDetails(){
        return $this->hasMany(PurchaseOrderDetails::class, 'pr_detail_id', 'id');
    }

    public function getFullPathAttribute()
    {
        if ($this->filepath) {
            return config('app.url') . $this->filepath;
        }
    }
}
