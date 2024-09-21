<?php

namespace App\Models\MMIS\inventory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VwPurchaseReturn extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_mmis';
    protected $table = 'CDG_MMIS.dbo.VwPurchaseItems';
    protected $guarded = [];
}
