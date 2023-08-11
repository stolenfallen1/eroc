<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;
use App\Models\POS\CustomerGroupMapping;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customers extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'customers';
    protected $guarded = [];

    public function customer_mapping(){
        return $this->belongsTo(CustomerGroupMapping::class,'customer_id', 'id');
    }
}
