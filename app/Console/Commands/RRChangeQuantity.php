<?php

namespace App\Console\Commands;

use App\Models\MMIS\inventory\Delivery;
use App\Models\MMIS\inventory\DeliveryItems;
use App\Models\MMIS\procurement\CanvasMaster;
use App\Models\MMIS\procurement\PurchaseOrderDetails;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Models\OldMmis\PurchaseOrder;
use Illuminate\Console\Command;

class RRChangeQuantity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:rr-quantity';

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
        $rr_number = $this->ask('What is the RR number');
        $item_id = $this->ask('What is the Item id');
        $new_quantity = $this->ask('What is the new quantity');

        $data = Delivery::with('items')->where('rr_Document_Number', 'like', '%'. $rr_number)->first();
        $o_total = 0;
        $o_net_total = 0;
        $o_discount_amount = 0;
        foreach ($data->items as $item) {
            $total = $item['rr_Detail_Item_TotalGrossAmount'];
            $discount_amount = $item['rr_Detail_Item_TotalDiscount_Amount'];

            if($item['rr_Detail_Item_Id'] == $item_id){
                $total = $item['rr_Detail_Item_ListCost'] * $new_quantity;

                if(floatval($item['rr_Detail_Item_TotalDiscount_Percent']) > 0){
                    $discount_amount = $total * ($item['rr_Detail_Item_TotalDiscount_Percent'] / 100);
                    $o_discount_amount += $discount_amount;
                }

                $o_total += $total;
    
                
                $net_total = $total - $discount_amount;
                $o_net_total += $net_total;

                DeliveryItems::where('rr_Detail_Item_Id', $item_id)->update([
                    'rr_Detail_Item_Qty_Convert' => $new_quantity,
                    'rr_Detail_Item_Qty_Received' => $new_quantity,
                    'rr_Detail_Item_TotalGrossAmount' => $total,
                    'rr_Detail_Item_TotalDiscount_Amount' => $discount_amount,
                    'rr_Detail_Item_TotalNetAmount' => $net_total
                ]);
            }else{
                if(floatval($item['rr_Detail_Item_TotalDiscount_Percent']) > 0){
                    $discount_amount = $total * ($item['rr_Detail_Item_TotalDiscount_Percent'] / 100);
                    $o_discount_amount += $discount_amount;
                }
                
                $o_total += $total;
    
                
                $net_total = $total - $discount_amount;
                $o_net_total += $net_total;
            }

        }
        $data->update([
            'rr_Document_TotalGrossAmount' => $o_discount_amount,
            'rr_Document_TotalDiscountAmount' => $o_total,
            'rr_Document_TotalNetAmount' => $o_net_total
        ]);

        echo('Success');
    }
}
