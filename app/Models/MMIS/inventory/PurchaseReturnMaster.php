<?php

namespace App\Models\MMIS\inventory;

use App\Models\User;
use function PHPSTORM_META\map;
use App\Models\BuildFile\Branchs;
use App\Models\BuildFile\Vendors;
use App\Models\BuildFile\Warehouses;
use Illuminate\Support\Facades\Crypt;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseReturnMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_mmis';
    protected $table = 'CDG_MMIS.dbo.purchaseReturnMaster';
    protected $guarded = [];
    protected $with = ['warehouse','branch','user','items'];
    protected $appends = ['encrypted_key_id'];

    public function items(){
        return $this->hasMany(PurchaseReturnDetails::class, 'returned_id','id');
    }
    
    public function vendor()
    {
        return $this->belongsTo(Vendors::class, 'vendor_id','id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouses::class, 'warehouse_Id','id');
    }

    public function branch()
    {
        return $this->belongsTo(Branchs::class, 'branch_Id','id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'returnedby_id','idnumber')->select('idnumber','name','warehouse_id');
    }


    public function setKeyIdAttribute($value){
        $this->attributes['id'] = Crypt::encrypt($value);
    }
    
    public function getEncryptedKeyIdAttribute()
    {
        return Crypt::encrypt($this->attributes['id']);
    }
}
