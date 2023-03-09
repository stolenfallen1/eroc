<?php

namespace App\Models;

use PDO;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Approver\InvApprover;
use App\Models\BuildFile\Warehouses;
use Illuminate\Notifications\Notifiable;
use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
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
    protected $fillable = [
        'name',
        'email',
        'password',
        'warehouse_id',
        'branch_id',
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

    protected $with = ['warehouse','approvaldetail'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouses::class, 'warehouse_id', 'id');
    }

    public function approvaldetail()
    {
        return $this->hasOne(InvApprover::class, 'user_id', 'id');
    }
    
    public function purchaseRequest()
    {
        return $this->hasMany(PurchaseRequest::class, 'pr_RequestedBy', 'id');
    }

    public function createToken()
    {
        $token = sha1(time());
        $this->api_token = $token;
        $this->save();
        return $token;
    }
    public function revokeToken()
    {
        $this->api_token = null;
        $this->save();
    }
}
