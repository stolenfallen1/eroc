<?php

namespace App\Models\OldMMIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestForCanvas extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'supplier_canvas';
    protected $guarded = [];
}
