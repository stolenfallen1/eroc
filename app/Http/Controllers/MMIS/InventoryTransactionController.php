<?php

namespace App\Http\Controllers\MMIS;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\MMIS\inventory\InventoryTransaction;

class InventoryTransactionController extends Controller
{
    public function index()
    {
        $warehouse_id = Request()->warehouse_Id ?? Auth::user()->warehouse_id;
        $data['purchase'] = InventoryTransaction::with('unit','user')->where('transaction_Acctg_TransType','IPU')->where(['warehouse_Id' => $warehouse_id, 'transaction_Item_Id' => Request()->item_id])->get();
        $data['beginning'] = InventoryTransaction::with('unit','user')->where('transaction_Acctg_TransType','IPC')->where(['warehouse_Id' => $warehouse_id, 'transaction_Item_Id' => Request()->item_id])->get();
        return response()->json($data, 200);
    }

}
