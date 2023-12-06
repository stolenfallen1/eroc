<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MMIS\inventory\Delivery;
use App\Models\MMIS\inventory\DeliveryItems;

class RemoveVatInRR extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:rr-vat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $rr_number = $this->ask('What it the pr number');

        $data = Delivery::with('items')->where('rr_Document_Number', 'like', '%'. $rr_number)->first();
        $total = 0;
        $total_discount = 0;
        $net_total = 0;
        foreach ($data->items as $item) {
            $discount_amount = 0;
            $details = DeliveryItems::where('id', $item['id'])->first();
            $itotal = $item['rr_Detail_Item_ListCost'] * $item['rr_Detail_Item_Qty_Received'];
            $total += $itotal;

            if(floatval($item['rr_Detail_Item_TotalDiscount_Percent']) > 0){
                $discount_amount = $itotal * ($item['rr_Detail_Item_TotalDiscount_Percent'] / 100);
                $total_discount += $discount_amount;
            }

            $inet_total = $itotal - $discount_amount;
            $net_total += $inet_total;

            $details->update([
                'rr_Detail_Item_TotalGrossAmount' => $itotal,
                'rr_Detail_Item_TotalDiscount_Amount' => $discount_amount,
                'rr_Detail_Item_TotalNetAmount' => $inet_total,
                'rr_Detail_Item_Vat_Rate' => 0,
                'rr_Detail_Item_Vat_Amount' => 0,
            ]);
        }
        $data->update([
            'rr_Document_TotalGrossAmount' => $total,
            'rr_Document_TotalDiscountAmount' => $total_discount,
            'rr_Document_TotalNetAmount' => $net_total,
            'rr_Document_Vat_Inclusive_Rate' => 0
        ]);
        echo('success');
    }
}
