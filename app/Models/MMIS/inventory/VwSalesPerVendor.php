<?php

namespace App\Models\MMIS\inventory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VwSalesPerVendor extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'CDG_POS.dbo.VwSalesPerVendor';
    protected $guarded = [];
}
