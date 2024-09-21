<?php

namespace App\Http\Controllers\MMIS;

use PDF;
use Carbon\Carbon;
use App\Helpers\PDFHeader;
use App\Helpers\ParentRole;
use Illuminate\Http\Request;
use App\Models\BuildFile\Branchs;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesPerVendorReport;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\MMIS\inventory\VwExpiredItems;
use App\Models\MMIS\inventory\VwSalesPerVendor;
use App\Models\MMIS\inventory\VwReorderStockLevels;
use App\Models\MMIS\inventory\VwExpiringItemsWithin14Days;

class InventoryStocksAlertController extends Controller
{
    protected $model;
    protected $authUser;
    protected $role;


    public function __construct()
    {
        $this->authUser = auth()->user();
        $this->role = new ParentRole();
        $this->model = VwSalesPerVendor::query();
    }

    public function index(Request $request)
    {
        if ($this->role->staff() || $this->role->department_head()) {
            $data['ReOrderStocks'] = $this->ReOrderStocks($request->branch_id, $request->warehouse_id);
            $data['ExpireItemsWithin14days'] = $this->ExpiredItemsWithinDays($request->branch_id, $request->warehouse_id);
            $data['ExpireItems'] = $this->ExpiredItems($request->branch_id, $request->warehouse_id);
            return response()->json($data, 200);
        }
    }


    public function GenerateSalesPerVendor(Request $request)
    {
        $per_page = Request()->per_page ?? '1';
        $from = $request->dateFrom ? $request->dateFrom : date('Y-m-d');
        $to = $request->dateTo ? $request->dateTo : date('Y-m-d');
        $vendor = $request->supplierID ?? '';
        $customerType = $request->customerType ?? '';
        if ($from && $to) {
            $this->model->whereBetween('payment_date', [$from, $to]);
        }
        if ($vendor) {
            $this->model->where('vendorID', $vendor);
        }
        if ($customerType) {
            $this->model->where('customerType', $customerType);
        }
        $data = $this->model->paginate($per_page);
        return response()->json($data, 200);
    }



    public function ReOrderStocks($branchid = null, $warehouseid = null)
    {
        $branch_id      = $branchid ? $branchid : $this->authUser->branch_id;
        $warehouse_id   = $warehouseid ? $warehouseid : $this->authUser->warehouse_id;
        $data = VwReorderStockLevels::where('branch_id', $branch_id)->where('warehouse_Id', $warehouse_id)->get();
        return $data;
    }

    public function ExpiredItems($branchid = null, $warehouseid = null)
    {
        $branch_id      = $branchid ? $branchid : $this->authUser->branch_id;
        $warehouse_id   = $warehouseid ? $warehouseid : $this->authUser->warehouse_id;
        $data = VwExpiredItems::where('branch_id', $branch_id)->where('warehouse_Id', $warehouse_id)->get();
        return $data;
    }

    public function ExpiredItemsWithinDays($branchid = null, $warehouseid = null)
    {
        $branch_id      = $branchid ? $branchid : $this->authUser->branch_id;
        $warehouse_id   = $warehouseid ? $warehouseid : $this->authUser->warehouse_id;
        $data =  VwExpiringItemsWithin14Days::where('branch_id', $branch_id)->where('warehouse_Id', $warehouse_id)->get();
        return $data;
    }



    public function PrintExpiredItems($branchid, $warehouseid)
    {
        $path = '/expired-items/' . $branchid . '/' . $warehouseid;
        $expiredData = $this->ExpiredItems($branchid, $warehouseid);
        $groupedItems = array();
        // Iterate through the items using foreach
        foreach ($expiredData as $item) {
            $category = $item['category'];
            // Check if the category exists in the groupedItems array
            if (isset($groupedItems[$category])) {
                // If the category exists, add the item to the category's array
                $groupedItems[$category][] = $item;
            } else {
                // If the category doesn't exist, create a new array for the category and add the item
                $groupedItems[$category] = array($item);
            }
        }
        $branch = Branchs::where('id', $branchid)->first();
        $pdf_data = [
            'logo' => (new PDFHeader())->imageSRC(),
            'qr' => (new PDFHeader())->QrPath($path),
            'title' => 'Expired Items Report',
            'branch' => $branch,
            'data' => $groupedItems
        ];
        // return $pdf_data;
        $pdf = PDF::loadView('inventory.ExpiredItems', ['pdf_data' => $pdf_data])->setPaper('letter', 'landscape');
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->get_font("Montserrat", "normal");
        $dompdf->get_canvas()->page_text(750, 575, "{PAGE_NUM} / {PAGE_COUNT}", $font, 10, array(0, 0, 0));

        return $pdf->stream('expired-items-' . Carbon::now()->format('m-d-Y') . '.pdf');
    }


    public function PrintExpiredItems14Days($branchid, $warehouseid)
    {
        $path = '/expired-items-within-14days/' . $branchid . '/' . $warehouseid;
        $expiredData = $this->ExpiredItemsWithinDays($branchid, $warehouseid);
        $groupedItems = array();
        // Iterate through the items using foreach
        foreach ($expiredData as $item) {
            $category = $item['category'];
            // Check if the category exists in the groupedItems array
            if (isset($groupedItems[$category])) {
                // If the category exists, add the item to the category's array
                $groupedItems[$category][] = $item;
            } else {
                // If the category doesn't exist, create a new array for the category and add the item
                $groupedItems[$category] = array($item);
            }
        }
        $branch = Branchs::where('id', $branchid)->first();
        $pdf_data = [
            'logo' => (new PDFHeader())->imageSRC(),
            'qr' => (new PDFHeader())->QrPath($path),
            'title' => 'Expiring Items Within 14 Days Report',
            'branch' => $branch,
            'data' => $groupedItems
        ];
        // return $pdf_data;
        $pdf = PDF::loadView('inventory.ExpiredItems', ['pdf_data' => $pdf_data])->setPaper('letter', 'landscape');
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->get_font("Montserrat", "normal");
        $dompdf->get_canvas()->page_text(750, 575, "{PAGE_NUM} / {PAGE_COUNT}", $font, 10, array(0, 0, 0));

        return $pdf->stream('expired-items-within-14days-' . Carbon::now()->format('m-d-Y') . '.pdf');
    }



    public function PrintReOrder($branchid, $warehouseid)
    {
        $path = '/reorder-stocks/' . $branchid . '/' . $warehouseid;
        $expiredData = $this->ReOrderStocks($branchid, $warehouseid);
        $groupedItems = array();
        // Iterate through the items using foreach
        foreach ($expiredData as $item) {
            $category = $item['category'];
            // Check if the category exists in the groupedItems array
            if (isset($groupedItems[$category])) {
                // If the category exists, add the item to the category's array
                $groupedItems[$category][] = $item;
            } else {
                // If the category doesn't exist, create a new array for the category and add the item
                $groupedItems[$category] = array($item);
            }
        }
        $branch = Branchs::where('id', $branchid)->first();
        $pdf_data = [
            'logo' => (new PDFHeader())->imageSRC(),
            'qr' => (new PDFHeader())->QrPath($path),
            'title' => 'ReOrder Stock Report',
            'branch' => $branch,
            'data' => $groupedItems
        ];
        // return $pdf_data;
        $pdf = PDF::loadView('inventory.ReOrderStocks', ['pdf_data' => $pdf_data])->setPaper('letter', 'landscape');
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->get_font("Montserrat", "normal");
        $dompdf->get_canvas()->page_text(750, 575, "{PAGE_NUM} / {PAGE_COUNT}", $font, 10, array(0, 0, 0));
        return $pdf->stream('reorder-stocks-' . Carbon::now()->format('m-d-Y') . '.pdf');
    }



    public function PrintPDFSalesPerVendor(Request $request)
    {
        $path = '/reorder-stocks/';
        $from = $request->dateFrom ? $request->dateFrom : date('Y-m-d');
        $to = $request->dateTo ? $request->dateTo : date('Y-m-d');
        $vendor = $request->supplierID ?? '';
        $customerType = $request->customerType ?? '';
        if ($from && $to) {
            $this->model->whereBetween('payment_date', [$from, $to]);
        }
        if ($vendor) {
            $this->model->where('vendorID', $vendor);
        }
        if ($customerType) {
            $this->model->where('customerType', $customerType);
        }
        $data = $this->model->get();

        $groupedItems = array();
        // Iterate through the items using foreach
        foreach ($data as $item) {
            $category = $item['categoryname'];
            // Check if the category exists in the groupedItems array
            if (isset($groupedItems[$category])) {
                // If the category exists, add the item to the category's array
                $groupedItems[$category][] = $item;
            } else {
                // If the category doesn't exist, create a new array for the category and add the item
                $groupedItems[$category] = array($item);
            }
        }
        $branch = Branchs::where('id', $request->branch_id)->first();
        $pdf_data = [
            'logo' => (new PDFHeader())->imageSRC(),
            'qr' => (new PDFHeader())->QrPath($path),
            'title' => 'SCD Claims Report',
            'branch' => $branch,
            'data' => $groupedItems
        ];
        // return $pdf_data;
        $pdf = PDF::loadView('inventory.SalesPerVendor', ['pdf_data' => $pdf_data])->setPaper('letter', 'landscape');
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->get_font("Montserrat", "normal");
        $dompdf->get_canvas()->page_text(750, 575, "{PAGE_NUM} / {PAGE_COUNT}", $font, 10, array(0, 0, 0));

        return $pdf->stream('reorder-stocks-' . Carbon::now()->format('m-d-Y') . '.pdf');
    }

    public function PrintExcelSalesPerVendor(Request $request)
    {
        $from = $request->dateFrom ? $request->dateFrom : date('Y-m-d');
        $to = $request->dateTo ? $request->dateTo : date('Y-m-d H:i:s', strtotime("+6 hours"));
        $vendor = $request->supplierID ?? '';
        $customerType = $request->customerType ?? '';
        $branch = Branchs::where('id', $request->branch_id)->first();
        return Excel::download(new SalesPerVendorReport($from, $to, $vendor, $customerType, $branch), 'reorder-stocks-' . Carbon::now()->format('m-d-Y') . '.xlsx');
    }
}
