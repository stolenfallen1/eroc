<?php

namespace App\Console\Commands;

use App\Models\OldMmis\PurchaseOrder;
use App\Models\OldMMIS\PurchaseRequest;
use Illuminate\Console\Command;

class TransferOldMMIS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transfer:old-mmis-to-new';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this command will transfer the old record of mmis to new mmis';

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
        $purchase_requests = PurchaseRequest::with('canvas.supplier')
        ->where('appd_admin', 'Approved')
        ->whereHas('purchaseOrders', function($q){
            $q->where(function($q){
                $q->where('appd_finance', '!=', 'Declined')->where('appd_admin', '!=', 'Declined');
            })->wheredoesnthave('deliveries')
            ->where('receivingstatus', '');
        })
        ->get();
        foreach ($purchase_requests as $key => $purchase_request) {
            dd($purchase_request);
        }
    }
}
