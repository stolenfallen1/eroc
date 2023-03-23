<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Model;


class Suppliertypes extends Model
{
    protected $table = 'mscSuppliertypes';
    protected $connection = "sqlsrv";
    protected $guarded = [];

    public function vendors(){
        return $this->hasMany(Vendors::class, 'vendor_CategoryId', 'id');
    }
}
