<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\Setting\Module;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SidebarGroup extends Model
{
    use HasFactory;
    protected $table = 'sidebar_group';
    protected $connection = "sqlsrv";
    protected $guarded = [];
    // protected $with = ['modules'];

    public function modules(){
        return $this->hasMany(Module::class,'sidebar_group_id', 'id');
    }
}
