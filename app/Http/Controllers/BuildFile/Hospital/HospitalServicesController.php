<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\FmsExamProcedureItems;

class HospitalServicesController extends Controller
{
    public function index()
    {
        try {
            $data = FmsExamProcedureItems::query();
            $data->with("category","category.revenue");
            if(Request()->item_group_id) {
                $data->where('msc_item_group',Request()->item_group_id);
            }
            if(Request()->keyword) {
                $data->where('exam_description', 'LIKE', '%'.Request()->keyword.'%');
            }
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function search()
    {
        try {
            $data = FmsExamProcedureItems::with('category',"category.revenue");
            if(Request()->itemname) {
                $data->where('exam_description', 'LIKE', '%'.Request()->itemname.'%');
            }
            $data->orderBy('id', 'desc');
            $data->where('isActive', '1');
            $data->offset(0,300);
            return response()->json($data->get(), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function store(Request $request)
    {
        DB::connection('sqlsrv')->beginTransaction();
        try {
            $payload = $request->payload;
            $item = FmsExamProcedureItems::create([
                'exam_description' =>  isset($payload['exam_description']) ? $payload['exam_description'] : null,
                'exam_resultName' =>  isset($payload['exam_resultName']) ? $payload['exam_resultName'] : $payload['exam_description'],
                'exam_section' => (int) isset($payload['exam_section']) ? $payload['exam_section'] : null,
                'transaction_code' => isset($payload['msc_item_category_ID']['revenue']) ? $payload['msc_item_category_ID']['revenue']['transaction_code'] : null,
                'map_revenue_code' => isset($payload['msc_item_category_ID']['revenue']) ? $payload['msc_item_category_ID']['revenue']['transaction_code'] : null,
                'msc_item_category_ID' => (int) isset($payload['cagetory_id']) ? $payload['cagetory_id'] : null,
                'form' => (int) isset($payload['form']) ? $payload['form'] : null,
                'barcodeid' =>  isset($payload['barcodeid']) ? $payload['barcodeid'] : null,
                'barcodeidcustom' =>  isset($payload['barcodeidcustom']) ? $payload['barcodeidcustom'] : null,
                'includeInStatement' => (int) isset($payload['includeInStatement']) ? $payload['includeInStatement'] : null,
                'isCharging' => (int) isset($payload['isCharging']) ? $payload['isCharging'] : null,
                'isConsultation' => (int) isset($payload['isConsultation']) ? $payload['isConsultation'] : null,
                'isDoctorfee' => (int) isset($payload['isDoctorfee']) ? $payload['isDoctorfee'] : null,
                'isImaging' => (int) isset($payload['isImaging']) ? $payload['isImaging'] : null,
                'isPhic' => (int) isset($payload['isPhic']) ? $payload['isPhic'] : null,
                'isProfile' =>  isset($payload['isProfile']) ? $payload['isProfile'] : null,
                'isVATExempt' => (int) isset($payload['isVATExempt']) ? $payload['isVATExempt'] : null,
                'isVatable' => (int) isset($payload['isVatable']) ? $payload['isVatable'] : null,
                'isZeroRated' => (int) isset($payload['isZeroRated']) ? $payload['isZeroRated'] : null,
                'isactive' =>  (int)isset($payload['isactive']) ? $payload['isactive'] : null,
                'isallowdiscount' =>  (int)isset($payload['isallowdiscount']) ? $payload['isallowdiscount'] : null,
                'isallowstat' =>  (int)isset($payload['isallowstat']) ? $payload['isallowstat'] : null,
                'msc_item_group' =>  (int)isset($payload['msc_item_group']) ? $payload['msc_item_group'] : null,
                'msc_modalities_id' =>   (int)isset($payload['msc_modalities_id']) ? $payload['msc_modalities_id'] : null,
                'remarks' =>  isset($payload['remarks']) ? $payload['remarks'] : null,
                'statpercent' =>  isset($payload['statpercent']) ? $payload['statpercent'] : null,
                'created_at' => Carbon::now(),
                'CreateBy'=>Auth()->user()->idnumber
            ]);
            DB::connection('sqlsrv')->commit();

        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            return response()->json(["error" => $e], 200);
        }
        return response()->json(["message" => "success"], 200);
    }

    public function update(Request $request, $id)
    {
        DB::connection('sqlsrv')->beginTransaction();
        try {
            $payload = $request->payload;
            $item = FmsExamProcedureItems::where('id',$id)->update([
                'exam_description' =>  isset($payload['exam_description']) ? $payload['exam_description'] : null,
                'exam_resultName' =>  isset($payload['exam_resultName']) ? $payload['exam_resultName'] : $payload['exam_description'],
                'exam_section' => (int) isset($payload['exam_section']) ? $payload['exam_section'] : null,
                'msc_item_category_ID' => (int) isset($payload['cagetory_id']) ? $payload['cagetory_id'] : null,
                'form' => (int) isset($payload['form']) ? $payload['form'] : null,
                'barcodeid' =>  isset($payload['barcodeid']) ? $payload['barcodeid'] : $id,
                'barcodeidcustom' =>  isset($payload['barcodeidcustom']) ? $payload['barcodeidcustom'] : null,
                'includeInStatement' => (int) isset($payload['includeInStatement']) ? $payload['includeInStatement'] : null,
                'isCharging' => (int) isset($payload['isCharging']) ? $payload['isCharging'] : null,
                'isConsultation' => (int) isset($payload['isConsultation']) ? $payload['isConsultation'] : null,
                'isDoctorfee' => (int) isset($payload['isDoctorfee']) ? $payload['isDoctorfee'] : null,
                'isImaging' => (int) isset($payload['isImaging']) ? $payload['isImaging'] : null,
                'isPhic' => (int) isset($payload['isPhic']) ? $payload['isPhic'] : null,
                'isProfile' =>  isset($payload['isProfile']) ? $payload['isProfile'] : null,
                'isVATExempt' => (int) isset($payload['isVATExempt']) ? $payload['isVATExempt'] : null,
                'isVatable' => (int) isset($payload['isVatable']) ? $payload['isVatable'] : null,
                'isZeroRated' => (int) isset($payload['isZeroRated']) ? $payload['isZeroRated'] : null,
                'isactive' =>  (int)isset($payload['isactive']) ? $payload['isactive'] : null,
                'isallowdiscount' =>  (int)isset($payload['isallowdiscount']) ? $payload['isallowdiscount'] : null,
                'isallowstat' =>  (int)isset($payload['isallowstat']) ? $payload['isallowstat'] : null,
                'msc_item_group' =>  (int)isset($payload['msc_item_group']) ? $payload['msc_item_group'] : null,
                'map_revenue_code' =>   (int)isset($payload['category']['revenue']) ? $payload['category']['revenue']['transaction_code'] : null,
                'transaction_code' =>   (int)isset($payload['category']['revenue']) ? $payload['category']['revenue']['transaction_code'] : null,
                'msc_modalities_id' =>   (int)isset($payload['msc_modalities_id']) ? $payload['msc_modalities_id'] : null,
                'remarks' =>  isset($payload['remarks']) ? $payload['remarks'] : null,
                'statpercent' =>  isset($payload['statpercent']) ? $payload['statpercent'] : null,
                'updated_at' => Carbon::now(),
                'UpdateBy'=>Auth()->user()->idnumber
            ]);
            DB::connection('sqlsrv')->commit();
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            return response()->json(["error" => $e], 200);
        }
        return response()->json(["message" => "success"], 200);
    }
}
