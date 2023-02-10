<?php

namespace App\Models\MMIS;

use App\Models\BuildFile\Categories;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'prnumber',
        'itemgroupid',
        'categoryid',
        'requestedby',
        'departmentid',
        'prstatus',
        'prremarks',
        'appd_department',
        'appd_admin',
        'reviewstatus',
        'purchaserstatus',
        'appd_finance',
        'appd_president',
        'daterequested',
        'dateapproveddept',
        'dateapprovedadmin',
        'dateapprovedfinance',
        'companyid',
        'appd_consultant',
        'appd_con_date',
        'reviewbyid',
        'reviewdate',
        'daterequired',
        'status',
        'ismedicine',
        'islaboratory',
    ];

    public function category(){
        return $this->belongsTo(Categories::class,'categoryid', 'id');
    }
}
