<?php

namespace TCG\Voyager\Models;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Facades\Voyager;
use App\Models\Database\Database;
class Permission extends Model
{
    protected $connection = 'sqlsrv';
    protected $guarded = [];
    public function roles()
    {
        return $this->belongsToMany(Voyager::modelClass('Role'));
    }

    
    public static function generateFor($table_name,$table_driver=null)
    {
        self::firstOrCreate(['key' => 'browse_'.$table_name, 'table_name' => $table_name,'driver' => $table_driver]);
        self::firstOrCreate(['key' => 'read_'.$table_name, 'table_name' => $table_name,'driver' => $table_driver]);
        self::firstOrCreate(['key' => 'edit_'.$table_name, 'table_name' => $table_name,'driver' => $table_driver]);
        self::firstOrCreate(['key' => 'add_'.$table_name, 'table_name' => $table_name,'driver' => $table_driver]);
        self::firstOrCreate(['key' => 'delete_'.$table_name, 'table_name' => $table_name,'driver' => $table_driver]);
        self::firstOrCreate(['key' => 'print_'.$table_name, 'table_name' => $table_name,'driver' => $table_driver]);
        self::firstOrCreate(['key' => 'post_'.$table_name, 'table_name' => $table_name,'driver' => $table_driver]);
    }
    
    public static function removeFrom($table_name)
    {
        self::where(['table_name' => $table_name])->delete();
    }
}
