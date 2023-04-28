<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Model;

class Supplierterms extends Model
{
    protected $table = 'mscSupplierterms';
    protected $connection = "sqlsrv";
    protected $guarded = [];

    public function vendors(){
        return $this->hasMany(Vendors::class, 'vendor_TermsId', 'id');
    }
}
