<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class vwExpiredItems extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'CDG_POS.dbo.vwExpired_Items';
    protected $guarded = [];
}
