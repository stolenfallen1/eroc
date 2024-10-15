<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SysConfigGeneral extends Model
{
    use HasFactory;
    protected $table = 'sysConfigGeneral';
    protected $connection = "sqlsrv";

    protected $fillable = [
        'Hospital_name',
        'Hospital_Logo',
        'Hospital_Fax',
        'Hospital_EmailAdd',
        'Hospital_Telephone',
        'Hospital_Website',
        'Hospital_Owner',
        'Hospital_TIN',
        'Hospital_StreetBldg1',
        'Hospital_StreetBldg2',
        'Hospital_StreetBldg3',
        'Hospital_Barangay',
        'Hospital_TownCity',
        'Hospital_Province',
        'Hospital_Region',
        'Hospital_Country',
        'Hospital_Area',
        'Hospital_Zipcode',
        'Hospital_Complete_Address',
        'Hospital_AccrBeds',
        'Hospital_BedCapacity',
        'Hospital_AccrNo',
        'Hospital_Type',
        'Hospital_Category',
        'Hospital_PHICAccrNo',
        'Hospital_PHICDaysCovered',
        'CodeFormat',
        'CodeType',
        'CodeSeparator',
        'phic_facility',
        'phic_facilityoth',
        'phic_pmcc',
        'phic_UserName',
        'phic_UserPwd',
        'phic_publickey_path',
        'phic_storage_UserName',
        'phic_storage_UserPwd',
    ];
}
