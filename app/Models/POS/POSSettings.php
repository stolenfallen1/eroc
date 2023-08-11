<?php

namespace App\Models\POS;

use App\Models\POS\POSBIRSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class POSSettings extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'possettings';
    protected $guarded = [];

    public function bir_settings(){
        return $this->hasOne(POSBIRSettings::class,'pos_setting_id', 'id');
    }
}
