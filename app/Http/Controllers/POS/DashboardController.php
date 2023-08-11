<?php

namespace App\Http\Controllers\POS;

use Illuminate\Http\Request;
use App\Models\POS\POSBIRSettings;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;
class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $terminalid = Auth()->user()->terminal_id;
        $year = Request()->payload['year'];
        $month = Request()->payload['month'] ?? date('m');
        $data['total_sales'] = $this->total_sales($month,$year);
        $data['sales_per_category'] = $this->sales_per_category($month,$year);
        $data['graph_overview'] = $this->graph_overview($year);
        $data['top_selling'] = $this->top_selling($month,$year);
        $data['slow_moving_product_data'] = $this->slow_moving_product($month,$year);
        $data['stock_management'] = $this->stock_management($month,$year);
        $data['employe_performance'] = $this->employe_performance($month,$year);
        return response()->json($data,200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function total_sales($month,$year)
    {
       $data = DB::connection('sqlsrv_pos')->select('EXEC sp_Dashboard_Monthly_Total_Sales ?,?', [$month,$year]);
        if($data){
            return $data[0];
        }
    }
    public function today_sales()
    {
       $data = DB::table()->get();
    }
    
    public function sales_per_category($month,$year)
    {
        $data = DB::connection('sqlsrv_pos')->select('EXEC sp_Dashboard_Sales_Per_Category ?,?', [$month,$year]);
        return $data;
    }

    public function monthly_sales()
    {
        //
    }
    public function graph_overview($year)
    {
        $data = DB::connection('sqlsrv_pos')->select('EXEC sp_Dashboard_Graph_Overview ?', [$year]);
        if($data){
            return $data[0];
        }
    }
     
    public function slow_moving_product($month,$year)
    {
        $data = DB::connection('sqlsrv_pos')->select('EXEC sp_Dashboard_SlowMoving_Items ?,?', [$month,$year]);
        return $data;
    }
    public function top_selling($month,$year)
    {
        $data = DB::connection('sqlsrv_pos')->select('EXEC sp_Dashboard_Top_Selling_Products ?,?', [$month,$year]);
        return $data;
    }
    public function employe_performance($month,$year)
    {
        
        $data = DB::connection('sqlsrv_pos')->select('EXEC sp_Dashboard_Employee_Performance ?,?', [$month,$year]);
        return $data;
    }
    public function stock_management($month,$year)
    {
        $data = DB::connection('sqlsrv_pos')->select('EXEC sp_Dashboard_Inventory_StockManagement ?,?', [$month,$year]);
        return $data;
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::connection('sqlsrv')->beginTransaction();
        try{
            POSBIRSettings::create([
                'pos_supplier_company_name'=>$request->payload['pos_supplier_company_name'] ?? '',
                'pos_supplier_address_bldg'=>$request->payload['pos_supplier_address_bldg'] ?? '',
                'pos_supplier_address_streetno'=>$request->payload['pos_supplier_address_streetno'] ?? '',
                'pos_supplier_address_region'=>$request->payload['pos_supplier_address_region'] ?? '',
                'pos_supplier_address_municipality'=>$request->payload['pos_supplier_address_municipality'] ?? '',
                'pos_supplier_address_barangay'=>$request->payload['pos_supplier_address_barangay'] ?? '',
                'pos_supplier_address_country'=>$request->payload['pos_supplier_address_country'] ?? '',
                'pos_supplier_address_zipcode'=>$request->payload['pos_supplier_address_zipcode'] ?? '',
                'pos_supplier_tin'=>$request->payload['pos_supplier_tin'] ?? '',
                'bir_accreditation_number'=>$request->payload['bir_accreditation_number'] ?? '',
                'bir_accreditation_date'=>$request->payload['bir_accreditation_date'] ?? '',
                'bir_accreditation_valid_until_date'=>$request->payload['bir_accreditation_valid_until_date'] ?? '',
                'bir_permit_to_use_number'=>$request->payload['bir_permit_to_use_number'] ?? '',
                'bir_permit_to_use_issued_date'=>$request->payload['bir_permit_to_use_issued_date'] ?? '',
                'isactive'=>$request->payload['isactive'] ?? '',
                'CreatedBy'=>Auth()->user()->idnumber,
                'created_at'=>Carbon::now(),
            ]);
            DB::connection('sqlsrv')->commit();
            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
       
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
            
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        DB::connection('sqlsrv')->beginTransaction();
        try{
            POSBIRSettings::where('id',$id)->update([
                'pos_supplier_company_name'=>$request->payload['pos_supplier_company_name'] ?? '',
                'pos_supplier_address_bldg'=>$request->payload['pos_supplier_address_bldg'] ?? '',
                'pos_supplier_address_streetno'=>$request->payload['pos_supplier_address_streetno'] ?? '',
                'pos_supplier_address_region'=>$request->payload['pos_supplier_address_region'] ?? '',
                'pos_supplier_address_municipality'=>$request->payload['pos_supplier_address_municipality'] ?? '',
                'pos_supplier_address_barangay'=>$request->payload['pos_supplier_address_barangay'] ?? '',
                'pos_supplier_address_country'=>$request->payload['pos_supplier_address_country'] ?? '',
                'pos_supplier_address_zipcode'=>$request->payload['pos_supplier_address_zipcode'] ?? '',
                'pos_supplier_tin'=>$request->payload['pos_supplier_tin'] ?? '',
                'bir_accreditation_number'=>$request->payload['bir_accreditation_number'] ?? '',
                'bir_accreditation_date'=>$request->payload['bir_accreditation_date'] ?? '',
                'bir_accreditation_valid_until_date'=>$request->payload['bir_accreditation_valid_until_date'] ?? '',
                'bir_permit_to_use_number'=>$request->payload['bir_permit_to_use_number'] ?? '',
                'bir_permit_to_use_issued_date'=>$request->payload['bir_permit_to_use_issued_date'] ?? '',
                'isactive'=>$request->payload['isactive'] ?? '',
                'CreatedBy'=>Auth()->user()->idnumber,
            ]);
            DB::connection('sqlsrv')->commit();
            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
       
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
            
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
