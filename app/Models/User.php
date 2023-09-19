<?php

namespace App\Models;

use PDO;
use Carbon\Carbon;
use App\Helpers\GetIP;
use TCG\Voyager\Models\Role;
use App\Models\UserDeptAccess;
use App\Models\BuildFile\Branchs;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Approver\InvApprover;
use App\Models\BuildFile\Warehouses;
use Illuminate\Notifications\Notifiable;
use App\Models\BuildFile\Systemuseraccess;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends \TCG\Voyager\Models\User
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
   
    // protected $guarded = [];
     protected $fillable = [
        'name',
        'email',
        'password',
        'warehouse_id',
        'branch_id',
        'role_id',
        'firstname',
        'lastname',
        'middlename',
        'birthdate',
        'mobileno',
        'idnumber',
        'passcode',
        'createdby',
        'updatedby',
        'isonline',
        'isactive'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $with = ['warehouse','approvaldetail','branch'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouses::class, 'warehouse_id', 'id');
    }

    public function approvaldetail()
    {
        return $this->hasOne(InvApprover::class, 'user_id', 'idnumber');
    }
    
    public function purchaseRequest()
    {
        return $this->hasMany(PurchaseRequest::class, 'pr_RequestedBy', 'idnumber');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(purchaseOrderMaster::class, 'po_Document_userid', 'idnumber');
    }


    public function systemUserAccess()
    {
        return $this->hasMany(Systemuseraccess::class, 'user_id', 'id');
    }

     public function branch()
    {
        return $this->belongsTo(Branchs::class, 'branch_id', 'id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    
    public function user_department_access()
    {
        return $this->hasMany(UserDeptAccess::class, 'user_id', 'idnumber');
    }

    public function createToken()
    {
        $token = sha1(time());
        $this->api_token = $token;
        $this->datelogin = Carbon::now();
        $this->isonline = 1;
        $this->host_name = (new GetIP())->getHostname();
        $this->save();
        return $token;
    }

    public function revokeToken()
    {
        $this->api_token = null;
        $this->isonline = null;
        $this->datelogout = Carbon::now();
        $this->save();
    }
}
