<?php

namespace App\Models\OldMMIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'categories';
    protected $guarded = [];
}
