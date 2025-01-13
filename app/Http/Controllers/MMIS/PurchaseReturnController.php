<?php

namespace App\Http\Controllers\MMIS;

use DB;
use PDF;
use Exception;
use Carbon\Carbon;
use App\Helpers\ParentRole;
use App\Helpers\PDFHeader;
use Illuminate\Http\Request;
use App\Helpers\RecomputePrice;
use App\Models\BuildFile\Branchs;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\inventory\VwPurchaseReturn;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Models\MMIS\inventory\ItemBatchModelMaster;
use App\Models\MMIS\inventory\PurchaseReturnMaster;
use Illuminate\Contracts\Encryption\DecryptException;

class PurchaseReturnController extends Controller
{
    protected $model;
    protected $authUser;
    protected $role;

   
    public function __construct()
    {
      $this->authUser = auth()->user();  
      $this->role = new ParentRole();
      $this->model = PurchaseReturnMaster::query();
    }
    public function index(Request $request){
        $per_page = Request()->per_page;
        $this->model->with('vendor');
        $this->model->where('warehouse_id',Auth()->user()->warehouse_id);
        $this->model->where('branch_id',Auth()->user()->branch_id);
        $this->model->orderBy('id','desc');
        return response()->json($this->model->paginate($per_page),200);
    }
    public function list(Request $request){
        $data= VwPurchaseReturn::where('warehouse_id',$request->warehouse_id)->where('branch_id',$request->branch_id)->where('onhand','>',0)->whereNotNull('updated_at')->get();
        return response()->json($data,200);
    }

    public function store(Request $request){
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try{
            $sequence = SystemSequence::where(['isActive' => true, 'code' => 'PORSN'])->where('branch_id', Auth()->user()->branch_id)->first();
            $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
            $prefix = $sequence->seq_prefix;
            $suffix = $sequence->seq_suffix;
            $selected_items = $request->payload['selected_items'];
            // Map each item to the product of item_Qty and price, then sum the array
            $total = array_sum(array_map(function($item) {
                $totalamount = floatval($item['rr_Detail_Item_TotalNetAmount']);
                // Return the product of qty and price
                return $totalamount;
            }, $selected_items));

            $data = PurchaseReturnMaster::updateOrCreate(
                [
                    'returned_document_number' => $number,
                    'warehouse_Id' => $request->payload['warehouse_id'],
                    'branch_Id' => $request->payload['branch_id'],
                ],
                [
                    'branch_Id' => $request->payload['branch_id'] ?? '',
                    'warehouse_Id' => $request->payload['warehouse_id'] ?? '',
                    'returned_document_number' => $number ?? '',
                    'po_document_number' => $request->payload['po_document_number'] ?? '',
                    'rr_Document_Number' => $request->payload['rr_Document_Number'] ?? '',
                    'rr_Document_Invoice_No' => $request->payload['rr_Document_Invoice_No'] ?? '',
                    'returned_date' => Carbon::now(),
                    'returnedby_id' => Auth()->user()->idnumber ?? '',
                    'approvedby_id' => 1,
                    'approvedby_date' =>Carbon::now(),
                    'vendor_id' => $request->payload['vendor_id'] ?? '',
                    'total_item_returned' => count($request->payload['selected_items'] ?? []) ?? '',
                    'total_amount' => $total ?? '',
                    'remarks' => $request->payload['remarks'] ?? '',
                    'return_status' => 1 ?? '',
                    'createdby' => Auth()->user()->idnumber ?? '',
                    'created_at' =>Carbon::now() ?? '',
                ]
            );
            foreach($selected_items as $row){
                $data->items()->updateOrCreate(
                    [
                        'returned_id'=>$data->id,
                        'returned_item_id'=>$row['itemID'],
                        'returned_item_batch_id'=>$row['batchID'],
                    ],
                    [
                        'returned_id'=> $data->id,
                        'returned_item_id'=> $row['itemID'],
                        'returned_item_qty'=> isset($row['qty']) ? $row['qty'] : $row['onhand'],
                        'returned_item_unitofmeasurement_id'=> $row['item_UnitofMeasurement_Id'],
                        'returned_item_batch_id'=> $row['batchID'],
                        'returned_item_price'=> $row['price'],
                        'returned_item_total_gross'=> $row['rr_Detail_Item_TotalGrossAmount'],
                        'returned_item_discount'=>$row['rr_Detail_Item_TotalDiscount_Amount'],
                        'returned_item_vat_amount'=> $row['rr_Detail_Item_Vat_Amount'],
                        'returned_item_total_net_amount'=>$row['rr_Detail_Item_TotalNetAmount'],
                        'isFreeGoods'=> 0,
                        'createdby'=>Auth()->user()->idnumber ?? '',
                        'created_at'=> Carbon::now() ?? '',
                    ]
                );

                $batch = ItemBatchModelMaster::where('id',$row['batchID'])->first();
                $transaction = FmsTransactionCode::where('description', 'like','%INVENTORY PURCHASE RETURNS%')->where('isActive', 1)->first();
                $batch->update([
                    'item_Qty_Used'=> $row['qty']
                ]);
                $warehouse_item = Warehouseitems::where([
                    'branch_id' => $request->payload['branch_id'],
                    'warehouse_Id' => $request->payload['warehouse_id'],
                    'item_Id' => $row['itemID'],
                    ])->first();
                $sequence1 = SystemSequence::where('code', 'PORSN')->where('branch_id', Auth()->user()->branch_id)->first(); // for inventory transaction only
                InventoryTransaction::create([
                    'branch_Id' => $request->payload['branch_id'],
                    'warehouse_Id' => $request->payload['warehouse_id'],
                    'transaction_Item_Batch_Detail' => $row['batchID'],
                    'transaction_Item_Id' =>  $row['itemID'],
                    'transaction_Date' => Carbon::now(),
                    'trasanction_Reference_Number' => generateCompleteSequence($sequence1->seq_prefix, $sequence1->seq_no, $sequence1->seq_suffix, ''),
                    'transaction_Item_UnitofMeasurement_Id' => $row['item_UnitofMeasurement_Id'],
                    'transaction_Qty' => $row['qty'],
                    'transaction_Item_OnHand' => $warehouse_item->item_OnHand - $row['qty'],
                    'transaction_Item_ListCost' => $row['price'],
                    'transaction_UserID' => Auth()->user()->idnumber,
                    'createdBy' =>  Auth()->user()->idnumber,
                    'transaction_Acctg_TransType' =>  $transaction->code ?? '',
                ]);
                (new RecomputePrice())->compute($request->payload['warehouse_id'],'',$row['itemID'],'out');
            }
            $sequence->update([
                'seq_no' => (int) $sequence->seq_no + 1,
                'recent_generated' => generateCompleteSequence($prefix, $number, $suffix, ""),
            ]);
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(["data" => $data], 200);
        }catch(Exception $e) {
            return response()->json(["error" => $e->getMessage()], 200);
            DB::connection('sqlsrv_mmis')->rollBack();
        }
        
    }

   

    public function PrintReturnedItems($branchid,$rid){
        $path = '/returned-items/'.$branchid.'/'.$rid;
        try {
            $id = Crypt::decrypt($rid);
            $data = PurchaseReturnMaster::with('vendor')->where('branch_Id',$branchid)->where('id',$id)->first(); 
            if($data){
                $branch = Branchs::where('id',$branchid)->first();
                $pdf_data = [
                    'logo' => (new PDFHeader())->imageSRC(),
                    'qr' => (new PDFHeader())->QrPath($path),
                    'title' => 'Returned Items Report',
                    'branch' => $branch, 
                    'data'=>$data
                ];
                // return $pdf_data;
                $pdf = PDF::loadView('inventory.ReturnedItems', ['pdf_data' => $pdf_data])->setPaper('letter', 'landscape');
                $pdf->render();
                $dompdf = $pdf->getDomPDF();
                $font = $dompdf->getFontMetrics()->get_font("Montserrat", "normal");
                $dompdf->get_canvas()->page_text(750, 575, "{PAGE_NUM} / {PAGE_COUNT}", $font, 10, array(0, 0, 0));
                return $pdf->stream('reorder-stocks-' .Carbon::now()->format('m-d-Y').'.pdf'); 
            }
        } catch (DecryptException $e) {
            // If decryption fails, return the original value or handle the error
            return response()->json(['data'=>'No match found'],200); // Return unencrypted value or handle error
        }
       
    }
}