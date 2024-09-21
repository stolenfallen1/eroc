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
    public function vendorList(){
        $data = Vendors::whereNull('deleted_at')->get();
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
