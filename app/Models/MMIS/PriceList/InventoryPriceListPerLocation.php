<?php

namespace App\Models\MMIS\PriceList;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
class InventoryPriceListPerLocation extends Model
{
    use HasFactory;
    public static function getReport($location_id = null)
    {
        return DB::select('EXEC CDG_MMIS.dbo.SP_Inv_PriceList_Per_Location ?', 
        [
            $location_id
        ]);
    }
}
