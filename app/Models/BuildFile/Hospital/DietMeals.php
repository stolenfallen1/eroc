<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DietMeals extends Model
{
    use HasFactory;

    protected $table = 'mscDietMeals';
    protected $connection = "sqlsrv";
    protected $guarded = [];


    public function dietTypes(){
        return $this->belongsTo(DietType::class, 'diet_type_id', 'id');
    }
    public function dietSubTypes(){
        return $this->belongsTo(DietSubType::class, 'diet_subtype_id', 'id');
    }
}
