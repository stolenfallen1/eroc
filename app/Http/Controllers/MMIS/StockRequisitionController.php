<?php

namespace App\Http\Controllers\MMIS;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\SystemSequence;
use App\Helpers\SearchFilter\inventory\StockRequisitions;
use App\Models\MMIS\inventory\StockRequisition;

class StockRequisitionController extends Controller
{
    public function index(){
        return (new StockRequisitions)->searchable();
    }

    public function store(Request $request){
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $authUser = Auth::user();
            $sequence = SystemSequence::where(['isActive' => true, 'branch_id' => $authUser->branch_id])->where('seq_description', 'like', '%Stock requisitions%')->first();
            if(!$sequence) return response()->json(['error' => 'No system sequence set.. Please contact I.T department']);
            $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
            $prefix = $sequence->seq_prefix;
            $suffix = $sequence->seq_suffix;

            $stock_requisitions = StockRequisition::create([
                'document_number' => $number,
                'document_prefix' => $prefix,
                'document_suffix' => $suffix,
                'request_by_id' => $authUser->idnumber,
                'requester_warehouse_id' => $authUser->warehouse_id,
                'requester_branch_id' => $authUser->branch_id,
                'sender_warehouse_id' => $request->sender_warehouse_id,
                'sender_branch_id' => $request->sender_branch_id,
                'item_group_id' => $request->item_group_id,
                'category_id' => $request->category_id,
                'remarks' => $request->remarks,
            ]);
            
            foreach ($request->items as $key => $item) {
                $stock_requisitions->items()->create([
                    'warehouse_item_id' => $item['ware_house_item']['id']                    ,
                    'item_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                ]);
            }

            $sequence->update([
                'seq_no' => (int) $sequence->seq_no + 1,
                'recent_generated' => generateCompleteSequence($prefix, $number, $suffix, ""),
            ]);
            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return $stock_requisitions;
        } catch (\Throwable $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e], 200);
        }
    }
}
