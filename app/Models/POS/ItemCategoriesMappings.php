<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Itemcategories;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemCategoriesMappings extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'vwInvItemCategories';
    protected $guarded = [];
    
}
