<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Z Reading Report - Daily Summary</title>
        <style>
            body {
                font-family: arial, sans-serif;
                font-size: 13px !important;
            }
    
            .header-section {
                font-family: arial, sans-serif;
                display: block;
                width: 100% !important;
                margin-bottom: 2%;
            }
    
            .company-details {
                border-top: 1px dashed black;
                border-bottom: 1px dashed black;
                width: 100% !important;
                height: 50px;
                text-align: center !important;
            }
            .content-section {
                width: 100%;
            }
            .text-center {
                text-align: center;
            }
    
            .text-right {
                text-align: right !important;
            }
    
            .dflex {
                display: inline-block;
                width: 100%;
            }
    
            .flex {
                width: 45% !important;
                display: inline-block;
            }
    
            .flex-50 {
                width: 30% !important;
                display: inline-block;
            }
    
            .flex-80 {
                width: 60% !important;
                display: inline-block;
            }
    
            .flex-10 {
                width: 40% !important;
                display: inline-block;
            }
    
            .flex-5 {
                width: 30% !important;
                display: inline-block;
            }
    
            @page {
                margin: 15px !important;
                width: 100%;
                font-size: 14px !important;
            }
            .summary {
                width: 100% !important;
            }
    
            .summary table {
                font-family: arial, sans-serif;
                border-collapse: collapse;
                width: 100%;
            }
    
            .summary th {
                text-align: left !important;
                padding: 2px !important;
            }
    
            .summary td {
                font-size: 11px !important;
                padding: 2px !important;
            }
    
            .summary td.text-center {
                text-align: right !important;
            }
    
            .receipt-header {
                border-bottom: 1px dashed black;
                padding-top: 5px;
                padding-bottom: 5px;
                margin-bottom: 5px;
            }
    
            .total {
                margin-top: 2px;
                padding-top: 5px;
                border-top: 1px dashed black;
            }
    
            .receipt-footer {
                border-top: 1px dashed black;
                border-bottom: 1px dashed black;
                padding: 2px;
            }
        </style>
    </head>
    <body>
        <div class="printout-company-details text-center">
            <div class="printout-company-name">{{$possetting->company_name}}</div>
            <div class="printout-company-address">{{$possetting->company_address_bldg}} {{$possetting->company_address_streetno}}</div>
        </div>
        <div class="printout-company-bir-details text-center">
            <div class="printout-company-tin">Vat Reg TIN: {{$possetting->company_tin}}</div>
            <div class="printout-company-min">MIN : {{$terminaldetails->terminal_Machine_Identification_Number}}</div>
            <div class="printout-seriesno">SN : {{$terminaldetails->terminal_serial_number}}</div>
        </div>
        <br>
        <div class="header-section">
            <div class="company-details">
                <div class="company-address">
                    <h4>Z Reading Report</h4>
                </div>
            </div>
        </div>
        <br>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Terminal ID</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{ $terminalid }}</div>
                </div>
            </div>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Start Invoice #</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{ $startinvoice }}</div>
                </div>
            </div>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">End Invoice #</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{ $endinvoice }}</div>
                </div>
            </div>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Report Date</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right">{{$report_date}}</div>
                </div>
            </div>
        </div>


        <div class="header-section">
            <div class="company-details">
                <div class="company-address">
                    <h4>Daily Summary</h4>
                </div>
            </div>
        </div>
        
        <div class="receipt-header text-center">
            <strong>Tendered  </strong>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Cash</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right">{{number_format($summary_total_cash,2)}}</div>
                </div>
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Debit Card</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right">{{number_format($summary_total_debitcard,2)}}</div>
                </div>
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Credit Card</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right">{{number_format($summary_total_creditcard,2)}}</div>
                </div>
            </div>
            <div style="width: 100% !important;display:inline-block;" class="total">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Total Sales</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right">{{number_format($summary_total_sales,2)}}</div>
                </div>
            </div>
        </div>
        <div class="receipt-header text-center">
            <strong>Taxes </strong>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 58% !important; display:inline-block;">
                    <div class="text-left">Vat Exempt Amount</div>
                </div>
                <div style="width: 40% !important; display:inline-block;">
                    <div class="text-right">{{number_format($summary_vat_exempt,2)}}</div>
                </div>
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Vat Amount</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right">{{number_format($summary_vat_sales,2)}}</div>
                </div>
            </div>
            <div style="width: 100% !important;display:inline-block;" class="total">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Total</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right">{{number_format($summary_total_vat_sales,2)}}</div>
                </div>
            </div>
        </div>
        <div class="receipt-header text-center">
            <strong>Discount  </strong>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 58% !important; display:inline-block;">
                    <div class="text-left">Senior / PWD Discount</div>
                </div>
                <div style="width: 40% !important; display:inline-block;">
                    <div class="text-right">{{number_format($summary_discount,2)}}</div>
                </div>
            </div>
            <div style="width: 100% !important;display:inline-block;" class="total">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Total</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right">{{number_format($summary_discount,2)}}</div>
                </div>
            </div>
        </div>
        <div class="receipt-header text-center">
            <strong>Refund  </strong>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 58% !important; display:inline-block;">
                    <div class="text-left">Cash</div>
                </div>
                <div style="width: 40% !important; display:inline-block;">
                    <div class="text-right">{{number_format($summary_refund,2)}}</div>
                </div>
            </div>
            <div style="width: 100% !important;display:inline-block;" class="total">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Total</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right">{{number_format($summary_refund,2)}}</div>
                </div>
            </div>
        </div>

         <div class="receipt-header text-center">
            <strong>Expected Counts </strong>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 50% !important; display:inline-block;">
                    <div class="text-left">Cash</div>
                </div>
                <div style="width: 48% !important; display:inline-block;">
                    <div class="text-right">{{number_format($summary_total_opening,2)}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 50% !important; display:inline-block;">
                    <div class="text-left">Total Sales</div>
                </div>
                <div style="width: 48% !important; display:inline-block;">
                    <div class="text-right">{{number_format($summary_sales,2)}}</div>
                </div> 
            </div>
           
            <div style="width: 100% !important;display:inline-block;" class="total">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Total</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{ number_format(($summary_total_opening + $summary_sales),2)}}</div>
                </div> 
            </div>
        </div>
        <div class="receipt-header text-center">
            <strong>Closing Counts </strong>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 50% !important; display:inline-block;">
                    <div class="text-left">Cash</div>
                </div>
                <div style="width: 48% !important; display:inline-block;">
                    <div class="text-right">{{number_format($summary_total_opening,2)}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 50% !important; display:inline-block;">
                    <div class="text-left">Total Sales</div>
                </div>
                <div style="width: 48% !important; display:inline-block;">
                    <div class="text-right">{{number_format($summary_total_closing,2)}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;" class="total">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Total</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{number_format(($summary_total_opening + $summary_total_closing),2)}}</div>
                </div> 
            </div>
        </div>
        <div class="receipt-header text-center">
            <strong>Variance </strong>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 50% !important; display:inline-block;">
                    <div class="text-left">Cash</div>
                </div>
                <div style="width: 48% !important; display:inline-block;">
                    <div class="text-right">{{number_format(($summary_sales + $summary_total_opening) - ($summary_total_opening + $summary_total_closing) ,2)}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;" class="total">
                <div style="width: 50% !important; display:inline-block;">
                    <div class="text-left">Total</div>
                </div>
                <div style="width: 48% !important; display:inline-block;">
                    <div class="text-right">
                        @if(($summary_sales + $summary_total_opening) - ($summary_total_opening + $summary_total_closing) < 0)
                            <span>Over ==></span>
                        @endif 
                        @if(($summary_sales + $summary_total_opening) - ($summary_total_opening + $summary_total_closing) > 0)
                            <span>Short ==></span>
                        @endif
                        @if(($summary_sales + $summary_total_opening) - ($summary_total_opening + $summary_total_closing) == 0)
                            
                        @endif
                        {{number_format(($summary_sales + $summary_total_opening) - ($summary_total_opening + $summary_total_closing) ,2)}}
                    </div>
                </div> 
            </div>
        </div>
        <div class="receipt-header text-center">
            
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 80% !important; display:inline-block;">
                    <div class="text-left">Number of Cash Transaction</div>
                </div>
                <div style="width: 18% !important; display:inline-block;">
                    <div class="text-right">{{(int)$summary_cash_transaction}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 80% !important; display:inline-block;">
                    <div class="text-left">Number of Credit Card Transaction</div>
                </div>
                <div style="width: 18% !important; display:inline-block;">
                    <div class="text-right">{{(int)$summary_credit_transaction}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 80% !important; display:inline-block;">
                    <div class="text-left">Number of Debit Card Transaction</div>
                </div>
                <div style="width: 18% !important; display:inline-block;">
                    <div class="text-right">{{(int)$summary_debit_transaction}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;" class="total">
                <div style="width: 80% !important; display:inline-block;">
                    <div class="text-left">Total Number Transaction</div>
                </div>
                <div style="width: 18% !important; display:inline-block;">
                    <div class="text-right">{{(int)$summary_sales_transaction}}</div>
                </div> 
            </div>
        </div>
        <br>
        <br>
        <div class="header-section">
            <div class="company-details">
                <div class="company-address">
                    <h4>Per Shift Summary</h4>
                </div>
            </div>
        </div>
        @php 
            $count =0;
        @endphp
        @foreach ($data as $shift => $items)
        
            @php 
                $count++;
                $total_sales =0;
                $sales_invoice_group = [];
                $return_invoice_group = [];
                $times = explode(" - ", $shift);
                $startTime = $times[0];
                $endTime = $times[1];

                $total_cash_sales =0;
                $total_creditcard_sales =0;
                $total_debitcard_sales =0;


                $total_cash_transaction =0;
                $total_creditcard_transaction =0;
                $total_debitcard_transaction =0;

               
                $sales_item_group = [];
                $return_item_group = [];
                $total_refund = 0;
                foreach($items as $item){
                   
                   
                    if ($item->statusdesc == 'POS -  Completed Order Sales') {
                        if ($item->method == 'Cash') {
                            $total_cash_sales += (float) $item->totalamount;
                        }
                        if ($item->method == 'Credit Card') {
                            $total_creditcard_sales += (float) $item->totalamount;
                        }
                        if ($item->method == 'Debit Card') {
                            $total_debitcard_sales += (float) $item->totalamount;
                        }
                        $total_sales +=(float) $item->totalamount;
                        $itemname = $item->itemname;
                        $invoice = $item->invnno;
                        if (isset($sales_invoice_group[$invoice])) {
                            // If the category_data exists, add the item to the category_data's array
                            $sales_invoice_group[$invoice][] = $item;
                        } else {
                        // If the category doesn't exist, create a new array for the category and add the item
                            $sales_invoice_group[$invoice] = [$item];
                        }
                        if (isset($sales_item_group[$itemname])) {
                            // If the sales_item_group exists, add the item to the sales_item_group's array
                            $sales_item_group[$itemname][] = $item;
                        } else {
                                // If the itemname doesn't exist, create a new array for the itemname and add the item
                            $sales_item_group[$itemname] = [$item];
                        }
                    }
                    if ($item->statusdesc == 'Refunds') {
                        $itemname = $item->itemname;
                        $total_refund +=(float) $item->totalamount;
                        $invoice = $item->invnno;
                        if (isset($return_invoice_group[$invoice])) {
                            // If the category_data exists, add the item to the category_data's array
                            $return_invoice_group[$invoice][] = $item;
                        } else {
                        // If the category doesn't exist, create a new array for the category and add the item
                            $return_invoice_group[$invoice] = [$item];
                        }
                        if (isset($return_item_group[$itemname])) {
                            // If the return_item_group exists, add the item to the return_item_group's array
                            $return_item_group[$itemname][] = $item;
                        } else {
                                // If the itemname doesn't exist, create a new array for the itemname and add the item
                            $return_item_group[$itemname] = [$item];
                        }
                    }
                    
                }
                $totaldiscount = 0;
                $totalvatexempt =0;
                $totalvat =0;
                $totalvatamount=0;
                $total_return_transaction = 0;
                $total_sales_transaction = 0;

                $count_cash_transaction = 0;
                $count_credit_transaction = 0;
                $count_debit_transaction = 0;
                $count_total_transaction = 0;
                $sales_count = 0;

                foreach ($sales_invoice_group as $key => $invoices) {
                    if($invoices[0]->method == 'Cash'){
                        $count_cash_transaction++;
                    }
                    if($invoices[0]->method == 'Credit Card'){
                        $count_credit_transaction++;
                    }
                    if($invoices[0]->method == 'Debit Card'){
                        $count_debit_transaction++;
                    }
                    $count_total_transaction++;
                    $totaldiscount += (float) $invoices[0]->discount;
                    $total_sales_transaction++;
                    $totalvatexempt += (float) $invoices[0]->vatexempt;
                    $totalvat += (float) $invoices[0]->vatamount;
                    $totalvatamount += (float) $invoices[0]->vatexempt +  $invoices[0]->vatamount;
                }
                foreach ($return_invoice_group as $key => $invoices) {
                    $total_return_transaction++;
                }
            @endphp

        <div class="header-section">
            <div class="company-details">
                <div class="company-address">
                    <h4>{{$shift}}</h4>
                </div>
            </div>
        </div>
                
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Shift Start Time</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{$startTime}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Shift End Time</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{$endTime}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Cashier</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{$items[$count]->cashier_name}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Opening Cash</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right">{{number_format($items[$count]->opening_amount,2)}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Closing Cash</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{number_format($items[$count]->closing_amount,2)}}</div>
                </div> 
            </div>
        </div>

        <div class="receipt-header text-center">
            <strong>Tendered  </strong>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Cash</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{number_format($total_cash_sales,2)}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Debit Card</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{number_format($total_debitcard_sales,2)}}</div>
                </div> 
            </div>

            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Credit Card</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{number_format($total_creditcard_sales,2)}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;" class="total">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Total</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{number_format($total_sales,2)}}</div>
                </div> 
            </div>
        </div>

        <div class="receipt-header text-center">
            <strong>Taxes </strong>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 40% !important; display:inline-block;">
                    <div class="text-left">Vat Exempt Sales</div>
                </div>
                <div style="width: 58% !important; display:inline-block;">
                    <div class="text-right"> {{number_format($totalvatexempt,2)}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 40% !important; display:inline-block;">
                    <div class="text-left">Vat Amount</div>
                </div>
                <div style="width: 58% !important; display:inline-block;">
                    <div class="text-right"> {{number_format($totalvat,2)}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;" class="total">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Total</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{number_format($totalvatamount,2)}}</div>
                </div> 
            </div>
        </div>


        <div class="receipt-header text-center">
            <strong>Discount </strong>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 50% !important; display:inline-block;">
                    <div class="text-left">Senior / PWD Discount</div>
                </div>
                <div style="width: 48% !important; display:inline-block;">
                    <div class="text-right"> {{number_format($totaldiscount,2)}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;" class="total">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Total</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{number_format($totaldiscount,2)}}</div>
                </div> 
            </div>
        </div>

        <div class="receipt-header text-center">
            <strong>Refund </strong>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 50% !important; display:inline-block;">
                    <div class="text-left">Cash</div>
                </div>
                <div style="width: 48% !important; display:inline-block;">
                    <div class="text-right"> {{number_format($total_refund,2)}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;" class="total">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Total</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{number_format($total_refund,2)}}</div>
                </div> 
            </div>
        </div>

        <div class="receipt-header text-center">
            <strong>Expected Counts </strong>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 50% !important; display:inline-block;">
                    <div class="text-left">Opening Cash</div>
                </div>
                <div style="width: 48% !important; display:inline-block;">
                    <div class="text-right">{{number_format($items[$count]->opening_amount,2)}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 50% !important; display:inline-block;">
                    <div class="text-left">Total Sales</div>
                </div>
                <div style="width: 48% !important; display:inline-block;">
                    <div class="text-right">{{number_format($total_sales,2)}}</div>
                </div> 
            </div>
           
            <div style="width: 100% !important;display:inline-block;" class="total">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Total</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{number_format((($items[$count]->opening_amount + $total_sales)),2)}}</div>
                </div> 
            </div>
        </div>
        <div class="receipt-header text-center">
            <strong>Closing Counts </strong>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 50% !important; display:inline-block;">
                    <div class="text-left">Opening Cash</div>
                </div>
                <div style="width: 48% !important; display:inline-block;">
                    <div class="text-right">{{number_format($items[$count]->opening_amount,2)}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 50% !important; display:inline-block;">
                    <div class="text-left">Total Sales</div>
                </div>
                <div style="width: 48% !important; display:inline-block;">
                    <div class="text-right">{{number_format($items[$count]->closing_amount,2)}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;" class="total">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Total</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{number_format((($items[$count]->opening_amount + $items[$count]->closing_amount)),2)}}</div>
                </div> 
            </div>
        </div>
        <div class="receipt-header text-center">
            <strong>Variance </strong>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 50% !important; display:inline-block;">
                    <div class="text-left">Cash</div>
                </div>
                <div style="width: 48% !important; display:inline-block;">
                    <div class="text-right">{{number_format(($total_sales - $items[$count]->closing_amount),2)}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;" class="total">
                <div style="width: 50% !important; display:inline-block;">
                    <div class="text-left">Total</div>
                </div>
                <div style="width: 48% !important; display:inline-block;">
                    <div class="text-right">
                        @if((($total_sales + $items[$count]->opening_amount) - ( $items[$count]->closing_amount +  $items[$count]->opening_amount)) < 0)
                            <span>Over ==></span>
                        @endif 
                        @if((($total_sales + $items[$count]->opening_amount) - ( $items[$count]->closing_amount +  $items[$count]->opening_amount)) > 0)
                            <span>Short ==></span>
                        @endif
                        @if((($total_sales + $items[$count]->opening_amount) - ( $items[$count]->closing_amount +  $items[$count]->opening_amount)) == 0)
                            
                        @endif
                        {{number_format((($total_sales + $items[$count]->opening_amount) - ( $items[$count]->closing_amount +  $items[$count]->opening_amount)),2)}}
                    </div>
                </div> 
            </div>
        </div>
        <div class="receipt-header text-center">
            <strong>Stats</strong>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 80% !important; display:inline-block;">
                    <div class="text-left">Number of Cash Transaction</div>
                </div>
                <div style="width: 18% !important; display:inline-block;">
                    <div class="text-right">{{(int)$count_cash_transaction}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 80% !important; display:inline-block;">
                    <div class="text-left">Number of Credit Card Transaction</div>
                </div>
                <div style="width: 18% !important; display:inline-block;">
                    <div class="text-right">{{(int)$count_credit_transaction}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 80% !important; display:inline-block;">
                    <div class="text-left">Number of Debit Card Transaction</div>
                </div>
                <div style="width: 18% !important; display:inline-block;">
                    <div class="text-right">{{(int)$count_debit_transaction}}</div>
                </div> 
            </div>
            <div style="width: 100% !important;display:inline-block;" class="total">
                <div style="width: 80% !important; display:inline-block;">
                    <div class="text-left">Total Number Transaction</div>
                </div>
                <div style="width: 18% !important; display:inline-block;">
                    <div class="text-right">{{(int)$count_total_transaction}}</div>
                </div> 
            </div>
        </div>
        <br>
        <div class="receipt-header text-center">
            <strong>Sales Transaction</strong>
        </div>
        <table class="summary">
            @php
                $totalsales =0;
                $totalsalescount =0;
            @endphp
            @foreach ($sales_item_group as $key => $items)
                <tr>
                    <td colspan="2">{{ $key }}</td>
                    <td colspan="1">
                        @php $qty =0; @endphp
                        @foreach ($items as $item)
                            @php
                                $qty +=$item->qty;
                        @endphp
                        
                        @endforeach
                        @php $totalsalescount +=$qty ; @endphp
                        {{$qty}}
                    </td>
                    <td class="text-right">
                        @php $price =0; @endphp
                            @foreach ($items as $item)
                                @php
                                    $price +=$item->price * $item->qty;
                            @endphp
                        @endforeach
                        @php
                            $totalsales +=$price;
                        @endphp
                        {{ number_format($price, 2) }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td class="total" colspan="2">Total</td>
                <td class="total" >{{$totalsalescount}}</td>
                <td class="total text-right" >{{number_format($totalsales,2)}}</td>
            </tr>
        </table>
        @if($return_item_group < 0)
        <div class="receipt-header text-center">
            <strong>Return Transaction</strong>
        </div>
        <table class="summary">
            @php
                $totalrefund =0;
                $totalcount =0;
            @endphp
            @foreach ($return_item_group as $key => $items)
            <tr>
                <td colspan="2">{{ $key }}</td>
                <td colspan="1">
                    @php $qty =0; @endphp
                    @foreach ($items as $item)
                        @php
                            $qty +=$item->qty;
                    @endphp
                    
                    @endforeach
                    @php $totalcount +=$qty ; @endphp
                    {{$qty}}
                </td>
                <td class="text-right">
                    @php $price =0; @endphp
                        @foreach ($items as $item)
                            @php
                                $price +=$item->price * $item->qty;
                        @endphp
                    @endforeach
                    @php
                        $totalrefund +=$price;
                    @endphp
                    {{ number_format($price, 2) }}
                </td>
            </tr>
            @endforeach
            <tr>
                <td class="total" colspan="2">Total</td>
                <td class="total" >{{$totalcount}}</td>
                <td class="total text-right">{{number_format($totalrefund,2)}}</td>
            </tr>
            
        </table>
        @endif
        <br>
        
        @endforeach
        <div class="receipt-footer text-center"><br>
            <strong>Thank you for choosing us!</strong><br><br>
        </div>
        <table class="print-item-table text-center">
            <tfoot  class="tablefooter">
                <tr>
                    <td class="textcenterfooter" colspan="2">SUPPLIER,</td>
                </tr>  
                <tr>
                    <td class="textcenterfooter" colspan="2"> {{$possetting->bir_settings->pos_supplier_company_name}}</td>
                </tr> 
                <tr>
                    <td class="textcenterfooter" colspan="2"> {{$possetting->bir_settings->pos_supplier_address_bldg}}  {{$possetting->bir_settings->pos_supplier_address_streetno}}</td>
                </tr>
                <tr>
                    <td class="textcenterfooter" colspan="2"> {{$possetting->bir_settings->pos_supplier_tin}}</td>
                </tr>
                <tr>
                    <td class="textcenterfooter" colspan="2">Acc. No: {{$possetting->bir_settings->bir_accreditation_number}}</td>
                </tr> 
                <tr>
                    <td class="textcenterfooter" colspan="2">Date of Accreditation :{{date('m/d/Y',strtotime($possetting->bir_settings->bir_accreditation_date))}}</td>
                </tr> 
                <tr>
                    <td class="textcenterfooter" colspan="2">Valid until :{{date('m/d/Y',strtotime($possetting->bir_settings->bir_accreditation_valid_until_date))}}</td>
                </tr> 
                <tr>
                    <td class="textcenterfooter" colspan="2">PTU : {{$possetting->bir_settings->bir_permit_to_use_number}}</td>
                </tr> 
                <tr>
                    <td class="textcenterfooter" colspan="2">Date Issued :{{date('m/d/Y',strtotime($possetting->bir_settings->bir_permit_to_use_issued_date))}}</td>
                </tr> 
            </tfoot>
        </table>
        <br>
    </body>
</html>
