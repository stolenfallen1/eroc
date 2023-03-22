<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Systerminals extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = 'systerminal';

    protected $fillable = [
        'terminal_name','terminal_ip_address','terminal_code'
    ];
}
