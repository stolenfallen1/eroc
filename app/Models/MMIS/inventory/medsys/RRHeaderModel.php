<?php

namespace App\Models\MMIS\inventory\medsys;

use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\inventory\medsys\RRDetailsModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RRHeaderModel extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_medsys_inventory";
    protected $table = 'CDG_DB.dbo.tbInvRRHeader';
    protected $guarded = [];

    
    public function details(){
        return $this->hasMany(RRDetailsModel::class, 'RecordNumber');
    }
}
