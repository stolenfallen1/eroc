<?php

namespace App\Http\Controllers\BuildFile;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\BuildFile\Vendors;
use App\Http\Controllers\Controller;
use App\Helpers\SearchFilter\Vendors as SearchFilterVendors;

class VendorController extends Controller
{
    public function index(){
        return (new SearchFilterVendors)->searchable();
    }

    public function vendorList(Request $request){

       
        $query = Vendors::whereNull('deleted_at')->where('isActive',1)->orderBy('vendor_Name','asc');
        if(Request()->vendor_id){
            $query->where('id',Request()->vendor_id);
        }
        $data = $query->get();
        return response()->json($data, 200);
    }

    public function store(Request $request){
        Vendors::updateOrCreate(
            [
                'vendor_Code'=>$request->vendor_Code,
                'vendor_Name'=>$request->vendor_Name,
            ],
            $request->all()
        );
    }

    public function update(Request $request, Vendors $vendor){

        $input = $request->except(['vendor_Name', 'vendor_CreditLimit']);
        $vendor->update($input);
        
    }

    public function destroy(Vendors $vendor){
        $vendor->update([
            'deleted_at' => Carbon::now()
        ]);
    }
}
