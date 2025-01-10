<?php

namespace App\Http\Controllers\MMIS;

use Carbon\Carbon;
use App\Helpers\ParentRole;
use Illuminate\Http\Request;
use App\Models\BuildFile\Vendors;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Itemmasters;
use App\Models\MMIS\procurement\VwRFQ;
use App\Models\BuildFile\SystemSequence;
use App\Models\MMIS\procurement\CanvasMaster;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Models\MMIS\procurement\QuotationMaster;
use App\Helpers\SearchFilter\Procurements\Canvases;
use App\Models\MMIS\procurement\PurchaseRequestDetails;

class PurchaseQuotationController extends Controller
{
    protected $model;
    protected $authUser;
    protected $role;

    public function __construct()
    {
        // $this->model = DB::connection('sqlsrv_mmis')->table('purchaseRequestMaster');
        $this->authUser = auth()->user();
        $this->role = new ParentRole();
    }

    public function index(Request $request)
    {
        $data = VwRFQ::query();
        $data->with('purchaseRequest','purchaseRequest.warehouse','user','vendor');
        if($request->branch){
            $data->where('rfq_document_branch_id',$request->branch);
        }
        if($request->department){
            $data->where('rfq_document_warehouse_id',$request->department);
        }
        $per_page = Request()->per_page;
        return $data->paginate($per_page);
    }

    public function store(Request $request)
    {

        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $sequence = SystemSequence::where(['isActive' => true, 'code' => 'RFQSN'])->first();
            $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
            $prefix = $sequence->seq_prefix;
            $suffix = $sequence->seq_suffix;

            foreach($request->payload['items'] as $item){
                QuotationMaster::updateOrCreate(
                [
                    'rfq_document_Vendor_Id'                         => $request->payload['rfq_document_Vendor_Id'] ?? '',
                    'pr_request_id'                                  => $item['pr_request_id'] ?? '',
                    'pr_request_detail_id'                           => $item['id'] ?? '',
                ],
                [
                    'rfq_document_Reference_Number'                  => $number ?? '',
                    'rfq_document_Date_Required'                     => $request->payload['rfq_document_Date_Required'] ?? '',
                    'rfq_document_Issued_Date'                       => $request->payload['rfq_document_Issued_Date'] ?? '',
                    'rfq_document_IssuedBy'                          => $request->payload['rfq_document_IssuedBy'] ?? '',
                    'rfq_document_Vendor_Id'                         => $request->payload['rfq_document_Vendor_Id'] ?? '',
                    'rfq_document_IntructionToBidders'               => $request->payload['rfq_document_IntructionToBidders'] ?? '',
                    'rfq_document_LeadTime'                          => $request->payload['rfq_document_LeadTime'] ?? '',
                    'pr_request_id'                                  => $item['pr_request_id'] ?? '',
                    'pr_request_detail_id'                           => $item['id'] ?? '',
                    'pr_Document_Item_Id'                            => $item['item_Id'] ?? '',
                    'pr_Document_Item_Approved_Qty'                  => $item['item_Request_Qty'] ?? '',
                    'pr_Document_Item_Approved_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'] ?? '',
                    'rfq_document_warehouse_id'                      =>$request->payload['rfq_document_warehouse_id'] ?? '',
                    'rfq_document_branch_id'                         =>$request->payload['rfq_document_branch_id'] ?? '',
                    'rfq_Document_Status'                            => 1 ?? '',
                    'created_at'                                     => Carbon::now(),
                ]);
            }
            $sequence->update([
                'seq_no' => (int) $sequence->seq_no + 1,
                'recent_generated' => generateCompleteSequence($prefix, $number, $suffix, ""),
            ]);

            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(['message'=>'Record successfully saved'], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e->getMessage()], 200);
        }
    }
}
