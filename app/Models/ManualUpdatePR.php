<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\procurement\CanvasMaster;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\MMIS\procurement\PurchaseRequestDetails;

class ManualUpdatePR extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'purchaseRequestMaster';

    protected $guarded = [];

    public function purchaseRequestDetails(){
        return $this->hasMany(PurchaseRequestDetails::class, 'pr_request_id', 'id');
    }

    public function canvases()
    {
        return $this->hasMany(CanvasMaster::class, 'pr_request_id', 'id');
    }

    public function purchaseOrder()
    {
        return $this->hasMany(purchaseOrderMaster::class, 'pr_Request_id', 'id');
    }

}
