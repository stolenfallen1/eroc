<?php

namespace App\Models\Database;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Models\Permission;


class Database extends Model
{
    
    protected $connection = 'sqlsrv';
    protected $table = 'databases';

    public function permissions()
    {
        return $this->hasMany(Permission::class,'driver','driver');
    }
}
