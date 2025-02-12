<?php
namespace App\Models;
use PDO;
use Carbon\Carbon;
use App\Helpers\GetIP;
use TCG\Voyager\Models\Role;
use App\Models\UserDeptAccess;
use App\Models\BuildFile\Branchs;
use Laravel\Sanctum\HasApiTokens;
use App\Models\POS\OpenningAmount;
use App\Models\Approver\InvApprover;
use App\Models\BuildFile\Warehouses;
use App\Models\UserRevenueCodeAccess;
use Illuminate\Notifications\Notifiable;
use App\Models\BuildFile\Systemuseraccess;
use App\Models\BuildFile\UserRoleItemGroup;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\BuildFile\SystemCentralSequences;
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
    protected $connection = 'sqlsrv';
    protected $table = 'CDG_CORE.dbo.users';
    // protected $guarded = [];
     protected $fillable = [
        'name',
        'email',
        'password',
        'warehouse_id',
        'branch_id',
        'position_id',
        'section_id',
        'role_id',
        'firstname',
        'lastname',
        'middlename',
        'suffix',
        'birthdate',
        'mobileno',
        'idnumber',
        'passcode',
        'createdby',
        'updatedby',
        'isonline',
        'isactive',
        'parent_role'
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

    protected $with = ['warehouse','warehouse.subWarehouse','approvaldetail','branch', 'user_department_access','OpeningAmount','systemUserAccess'];

    protected $appends = ['departments','RevenueCode','assigneditemgroup','assingcategory'];

    
    public function warehouse()
    {
        return $this->belongsTo(Warehouses::class, 'warehouse_id', 'id');
    }

    public function approvaldetail()
    {
        return $this->belongsTo(InvApprover::class, 'parent_role', 'id');
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

    public function assined_item_group()
    {
        return $this->hasMany(UserRoleItemGroup::class, 'login_id', 'idnumber');
    }
    public function getAssigneditemgroupAttribute(){
        return $this->assined_item_group()->pluck('inventory_group_id');
    }
    public function getAssingcategoryAttribute(){
        return $this->assined_item_group()->pluck('inventory_category_id')->unique();
    }

    
    public function getDepartmentsAttribute(){
        return $this->user_department_access()->pluck('warehouse_id');
    }

     public function user_revenuecode_access()
    {
        return $this->hasMany(UserRevenueCodeAccess::class, 'user_id', 'idnumber');
    }

    public function getRevenueCodeAttribute()
    {
         return $this->user_revenuecode_access()->pluck('revenue_code');
    }

    public function OpeningAmount(){
       return $this->belongsTo(OpenningAmount::class, 'user_id', 'idnumber')->whereDate('cashonhand_beginning_transaction',Carbon::now()->format('Y-m-d'));
    }
   
    public function createToken()
    {
        $token = sha1(time());
        $this->api_token = $token;
        $this->datelogin = Carbon::now();
        $this->datelogout = null;
        $this->isonline = 1;
        $this->host_name = (new GetIP())->getHostname();
        $this->save();
        return $token;
    }

    public function revokeToken()
    {
        $this->api_token = null;
        $this->isonline = 0;
        $this->datelogout = Carbon::now();
        $this->save();
    }
}
