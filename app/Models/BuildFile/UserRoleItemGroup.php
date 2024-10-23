<?php

namespace App\Models\BuildFile;

use App\Models\MMIS\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRoleItemGroup extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = "user_role_itemcategories";
}
