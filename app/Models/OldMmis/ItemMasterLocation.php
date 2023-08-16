<?php

namespace App\Models\OldMMIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemMasterLocation extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'item_master_locations';
    protected $guarded = [];
}
