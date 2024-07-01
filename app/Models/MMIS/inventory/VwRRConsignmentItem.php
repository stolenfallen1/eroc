<?php

namespace App\Models\MMIS\inventory;

use App\Models\User;
use App\Models\MMIS\Audit;
use App\Models\BuildFile\Branchs;
use App\Models\BuildFile\Vendors;
use App\Models\Approver\InvStatus;
use App\Models\BuildFile\Warehouses;
use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\inventory\ConsignmentItems;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VwRRConsignmentItem extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'vwRRconsignment';

    protected $guarded = [];

}
