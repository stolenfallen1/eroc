<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\procurement\CanvasMaster;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use App\Models\MMIS\procurement\PurchaseOrderDetails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\MMIS\procurement\PurchaseRequestDetails;

class ManualUpdateCanvass extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_mmis';
    protected $table = 'CDG_MMIS.dbo.canvasMaster';
    protected $guarded = [];

    public function purchaseOrderDetails()
    {
        return $this->hasMany(PurchaseOrderDetails::class, 'canvas_id', 'id');
    }
}
