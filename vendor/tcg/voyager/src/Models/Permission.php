<?php

namespace TCG\Voyager\Models;

use TCG\Voyager\Facades\Voyager;
use App\Models\Database\Database;
use App\Models\BuildFile\SidebarGroup;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $guarded = [];

    public function roles()
    {
        return $this->belongsToMany(Voyager::modelClass('Role'));
    }

    public function database_driver()
    {
        return $this->belongsTo(Database::class, 'driver', 'driver');
    }

    public function tablename()
    {
        return $this->belongsTo(DataType::class, 'table_name', 'name');
    }

    public function sidebarGroup()
    {
        return $this->hasOne(SidebarGroup::class, 'id', 'sidebar_group_id');
    }

    public static function generateFor($table_name, $table_driver = null, $module_id = null, $sub_module_id = null, $module_name = null, $sidebar_group_id = null)
    {

        $actions = ['browse_', 'read_', 'edit_', 'add_', 'delete_', 'print_', 'post_', 'approved_', 'consignment_', 'void_'];
        $permissions = self::where(['module_id' => $module_id, 'sub_module_id' => $sub_module_id])->get();

        foreach ($actions as $key => $action) {
            $cleanedTableName = str_replace(' ', '', $table_name);
            $newKey = $action . $cleanedTableName;
            $attributes = [
                'table_name' => $cleanedTableName,
                'driver' => $table_driver,
                'module' => $module_name,
                'module_id' => $module_id,
                'sub_module_id' => $sub_module_id,
                'sidebar_group_id' => $sidebar_group_id,
                'key' => $newKey
            ];

            if ($permissions->isEmpty() || !isset($permissions[$key])) {
                self::create($attributes);
            } else {
                self::where(['id' => $permissions[$key]->id])->update($attributes);
            }
        }

        // self::firstOrCreate(['key' => 'browse_'.$table_name, 'table_name' => $table_name,'driver' => $table_driver,'module' => $module_name,'module_id' => $module_id,'sub_module_id' => $sub_module_id]);
        // self::firstOrCreate(['key' => 'read_'.$table_name, 'table_name' => $table_name,'driver' => $table_driver,'module' => $module_name,'module_id' => $module_id,'sub_module_id' => $sub_module_id]);
        // self::firstOrCreate(['key' => 'edit_'.$table_name, 'table_name' => $table_name,'driver' => $table_driver,'module' => $module_name,'module_id' => $module_id,'sub_module_id' => $sub_module_id]);
        // self::firstOrCreate(['key' => 'add_'.$table_name, 'table_name' => $table_name,'driver' => $table_driver,'module' => $module_name,'module_id' => $module_id,'sub_module_id' => $sub_module_id]);
        // self::firstOrCreate(['key' => 'delete_'.$table_name, 'table_name' => $table_name,'driver' => $table_driver,'module' => $module_name,'module_id' => $module_id,'sub_module_id' => $sub_module_id]);
        // self::firstOrCreate(['key' => 'print_'.$table_name, 'table_name' => $table_name,'driver' => $table_driver,'module' => $module_name,'module_id' => $module_id,'sub_module_id' => $sub_module_id]);
        // self::firstOrCreate(['key' => 'post_'.$table_name, 'table_name' => $table_name,'driver' => $table_driver,'module' => $module_name,'module_id' => $module_id,'sub_module_id' => $sub_module_id]);
        // self::firstOrCreate(['key' => 'approved_'.$table_name, 'table_name' => $table_name,'driver' => $table_driver,'module' => $module_name,'module_id' => $module_id,'sub_module_id' => $sub_module_id]);
    }

    public static function removeFrom($table_name)
    {
        self::where(['table_name' => $table_name])->delete();
    }
}
