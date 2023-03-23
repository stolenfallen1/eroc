<?php

namespace App\Models\BuildFile;

use App\Models\MMIS\procurement\PurchaseRequestDetails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendors extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = 'vendors';
    protected $fillable = [
        'vendor_Code',
        'vendor_Name',
        'vendor_Address',
        'vendor_Website',
        'vendor_Email',
        'vendor_TIN',
        'vendor_CategoryId',
        'vendor_TermsId',
        'vendor_CreditLimit',
        'vendor_ContactPerson',
        'vendor_ContactPerson_Email',
        'vendor_TelNo',
        'vendor_FaxNo',
        'Remarks',
        'isVATInclusive',
        'isManufacturer',
        'isActive',
        'deleted_at',
    ];

    protected $with = ['category', 'term'];

    public function purchaseDetails(){
        return $this->hasMany(PurchaseRequestDetails::class, 'vendor_id', 'id');
    }

    public function category(){
        return $this->belongsTo(Suppliertypes::class, 'vendor_CategoryId');
    }

    public function term(){
        return $this->belongsTo(Supplierterms::class, 'vendor_TermsId');
    }

}
