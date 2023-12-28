<?php

namespace App\Console\Commands;

use App\Models\MMIS\procurement\CanvasMaster;
use App\Models\MMIS\procurement\PurchaseOrderDetails;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Models\OldMmis\PurchaseOrder;
use Illuminate\Console\Command;

class RemoveVat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:canvas-vat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this command will remove vat for inclusive supplier';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $po_number = $this->ask('What it the PO number');

        $data = purchaseOrderMaster::with('details.canvas')->where('po_Document_number', 'like', '%'. $po_number)->first();
        $o_total = 0;
        $o_net_total = 0;
        $o_discount_amount = 0;
        foreach ($data->details as $detail) {
            $canvas = CanvasMaster::where('id', $detail['canvas_id'])->first();
            $total = $canvas['canvas_item_amount'] * $canvas['canvas_Item_Qty'];
            $o_total += $total;
            $discount_amount = 0;

            if(floatval($canvas['canvas_item_discount_percent']) > 0){
                $discount_amount = $total * ($canvas['canvas_item_discount_percent'] / 100);
                $o_discount_amount += $discount_amount;
            }
            
            $net_total = $total - $discount_amount;
            $o_net_total += $net_total;
            PurchaseOrderDetails::where('id', $detail['id'])->update([
                'po_Detail_vat_percent' => 0,
                'po_Detail_vat_amount' => 0,
                'po_Detail_net_amount' => $net_total,
                'po_Detail_item_discount_amount' => $discount_amount,
                'po_Detail_item_listcost' => $net_total,
            ]);

            $canvas->update([
                'canvas_item_vat_rate' => 0,
                'canvas_item_vat_amount' => 0,
                'canvas_item_total_amount' => $total,
                'canvas_item_discount_amount' => $discount_amount,
                'canvas_item_net_amount' => $net_total,
            ]);
        }
        $data->update([
            'po_Document_vat_percent' => 0,
            'po_Document_vat_amount' => 0,
            'po_Document_discount_amount' => $o_discount_amount,
            'po_Document_total_gross_amount' => $o_total,
            'po_Document_total_net_amount' => $o_net_total
        ]);
        // $data = PurchaseRequest::with(['purchaseRequestDetails' => function($q){
        //     $q->with('recommendedCanvas.PurchaseOrderDetails')->whereHas('recommendedCanvas');
        // }])->where('pr_Document_Number', 'like', '%'. $pr_number . '%')->first();
        // dd($data);
        // foreach ($data->purchaseRequestDetails as $detail) {
        //     $canvas = CanvasMaster::where('id', $detail->recommendedCanvas->id)->first();
        //     $total = $canvas['canvas_item_amount'] * $canvas['canvas_Item_Qty'];
        //     $discount_amount = 0;

        //     if(floatval($canvas['canvas_item_discount_percent']) > 0){
        //         $discount_amount = $total * ($canvas['canvas_item_discount_percent'] / 100);
        //     }
            
        //     $net_total = $total - $discount_amount;
        //     foreach ($detail->recommendedCanvas->PurchaseOrderDetails as $po_detail) {
        //         PurchaseOrderDetails::where('id', $po_detail['id'])->update([
        //             'po_Detail_vat_percent' => 0,
        //             'po_Detail_vat_amount' => 0,
        //             'po_Detail_net_amount' => $net_total,
        //             'po_Detail_item_discount_amount' => $discount_amount,
        //             'po_Detail_item_listcost' => $net_total,
        //         ]);
        //     }
        //     $canvas->update([
        //         'canvas_item_vat_rate' => 0,
        //         'canvas_item_vat_amount' => 0,
        //         'canvas_item_total_amount' => $total,
        //         'canvas_item_discount_amount' => $discount_amount,
        //         'canvas_item_net_amount' => $net_total,
        //     ]);
        // }
        // purchaseOrderMaster::where('pr_Request_id', $data->id)->update([
        //     'po_Document_vat_percent' => 0,
        //     'po_Document_vat_amount' => 0
        // ]);

        echo('Success');
    }
}
