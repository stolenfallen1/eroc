<?php

namespace App\Models\MMIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'departments';
    protected $guarded = [];
}
