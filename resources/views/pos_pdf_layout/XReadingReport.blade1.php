<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>X Reading REPORT</title>
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

        .denominationtable {
            font-family: arial, sans-serif;
            border-collapse: collapse;
            width: 100%;
        }

        .denominationtable td {
            text-align: left;
            padding: 2px;
        }

        .denominationtable th {
            text-align: left;
            padding: 2px;
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
            padding: 2px;
        }

        .total {
            border-top: 1px dashed black;
        }

        .receipt-footer {
            border-top: 1px dashed black;
            border-bottom: 1px dashed black;
            padding: 2px;
        }
    </style>
</head>
@php
    $payment_cash_data = [];
    $payment_creditcard_data = [];
    $payment_debitcard_data = [];
    $sales_invoice_group = [];
    $return_invoice_group = [];
    
    $category_data = [];
    $sales_item_group = [];
    $return_item_group = [];

    $payment_cash_refund_data = [];
    $payment_creditcard_refund_data = [];
    $payment_debitcard_refund_data = [];
    
    $cashier_data = [];
    $total_sales_amount = 0;
    $total_transaction = 0;
    $total_tender = 0;
    $total_refund = 0;

    $total_cash_transaction =0;
    $total_creditcard_transaction =0;
    $total_debitcard_transaction =0;

    $totalvatable = 0;
    $totalvatexempt = 0;
    $totalvatamount = 0;
    $totalvatsales = 0;

    $totaldiscount = 0;

    $total_return_transaction = 0;
    $total_sales_transaction = 0;
    // Iterate through the items using foreach
    foreach ($data as $item) {
        if ($item->method == 'Cash') {
            $cash_payment = $item->method;
            // Check if the category exists in the groupedItems array
            if (isset($payment_cash_data[$cash_payment])) {
                // If the category_data exists, add the item to the category_data's array
            $payment_cash_data[$cash_payment][] = $item;
            } else {
            // If the category doesn't exist, create a new array for the category and add the item
                $payment_cash_data[$cash_payment] = [$item];
            }
        }
        if ($item->method == 'Credit Card') {
            $credit_payment = $item->method;
            // Check if the category exists in the groupedItems array
            if (isset($payment_creditcard_data[$credit_payment])) {
                // If the category_data exists, add the item to the category_data's array
            $payment_creditcard_data[$credit_payment][] = $item;
            } else {
            // If the category doesn't exist, create a new array for the category and add the item
                $payment_creditcard_data[$credit_payment] = [$item];
            }
        }
        if ($item->method == 'Debit Card') {
            $debit_payment = $item->method;
            // Check if the category exists in the groupedItems array
            if (isset($payment_debitcard_data[$debit_payment])) {
                // If the category_data exists, add the item to the category_data's array
            $payment_debitcard_data[$debit_payment][] = $item;
        } else {
            // If the category doesn't exist, create a new array for the category and add the item
                $payment_debitcard_data[$debit_payment] = [$item];
            }
        }
        if ($item->statusdesc == 'POS -  Completed Order Sales') {
            $total_transaction++;
            $total_sales_amount += (float) $item->price * $item->qty;
            
            $totaldiscount += (float) $item->discount;
            $invoice = $item->invnno;
            $category = $item->categories;
            $itemname = $item->itemname;
            // Check if the category exists in the groupedItems array
            if (isset($sales_item_group[$itemname])) {
                // If the sales_item_group exists, add the item to the sales_item_group's array
                $sales_item_group[$itemname][] = $item;
            } else {
                    // If the itemname doesn't exist, create a new array for the itemname and add the item
                $sales_item_group[$itemname] = [$item];
            }
            
            if (isset($category_data[$category])) {
                // If the category_data exists, add the item to the category_data's array
                $category_data[$category][] = $item;
            } else {
                    // If the category doesn't exist, create a new array for the category and add the item
                $category_data[$category] = [$item];
            }

            if (isset($sales_invoice_group[$invoice])) {
                // If the category_data exists, add the item to the category_data's array
                 $sales_invoice_group[$invoice][] = $item;
            } else {
            // If the category doesn't exist, create a new array for the category and add the item
                $sales_invoice_group[$invoice] = [$item];
            }

           
            $cashier_name = $item->cashier_name;
            // Check if the category exists in the groupedItems array
            if (isset($cashier_data[$cashier_name])) {
                // If the category_data exists, add the item to the category_data's array
            $cashier_data[$cashier_name][] = $item;
        } else {
            // If the category doesn't exist, create a new array for the category and add the item
                $cashier_data[$cashier_name] = [$item];
            }
        }
        if ($item->statusdesc == 'Refunds') {
            // Check if the category exists in the groupedItems array
            $itemname = $item->itemname;
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
            if ($item->method == 'Cash') {
                $cash_payment = $item->method;
                // Check if the category exists in the groupedItems array
                if (isset($payment_cash_refund_data[$cash_payment])) {
                    // If the category_data exists, add the item to the category_data's array
                $payment_cash_refund_data[$cash_payment][] = $item;
            } else {
                // If the category doesn't exist, create a new array for the category and add the item
                    $payment_cash_refund_data[$cash_payment] = [$item];
                }
            }
        }
    }
    
@endphp
@php
    // Iterate through the items using foreach
    foreach ($sales_invoice_group as $key => $invoices) {
        $total_sales_transaction++;
        $totalvatable += (float) $invoices[0]->vatable;
        $totalvatexempt += (float) $invoices[0]->vatexempt;
        $totalvatamount += (float) $invoices[0]->vatamount;
        $totalvatsales += (float) $invoices[0]->vatexempt +  $invoices[0]->vatamount;
    }
    foreach ($return_invoice_group as $key => $invoices) {
        $total_return_transaction++;
    }
@endphp
<body>
    <div class="header-section">
        <div class="company-details">
            <div class="company-address">
                <h4>X Reading Report</h4>
            </div>
        </div>
    </div>
    <br>
    <div>
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
                    <div class="text-left">Shift</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{ $shift_id }}</div>
                </div>
            </div>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Report Date</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{ $zreport->transdate }}</div>
                </div>
            </div>
        </div>
        <div class="dflex" style="width: 100% !important;">
        <div style="width: 100% !important;display:inline-block;">
            <div style="width: 38% !important; display:inline-block;">
                <div class="text-left">Opening Amount</div>
            </div>
            <div style="width: 60% !important; display:inline-block;">
                <div class="text-right"> {{ number_format($zreport->opening_amount, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 38% !important; display:inline-block;">
                    <div class="text-left">Opened At</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="text-right"> {{ date('m-d-Y H:i s', strtotime($zreport->opening_date)) }}</div>
                    </div>
                </div>
            </div>
            <div class="dflex" style="width: 100% !important;">
                <div style="width: 100% !important;display:inline-block;">
                    <div style="width: 38% !important; display:inline-block;">
                        <div class="text-left">Opened By</div>
                    </div>
                    <div style="width: 60% !important; display:inline-block;">
                        <div class="text-right">{{ $zreport->cashier_name }}</div>
                    </div>
                </div>
            </div>
            <div class="dflex" style="width: 100% !important;">
                <div style="width: 100% !important;display:inline-block;">
                    <div style="width: 38% !important; display:inline-block;">
                        <div class="text-left">Closed At</div>
                    </div>
                    <div style="width: 60% !important; display:inline-block;">
                        <div class="text-right"> {{ date('m-d-Y H:i s', strtotime($zreport->created_at)) }}</div>
                    </div>
                </div>
            </div>
            <div class="dflex" style="width: 100% !important;">
                <div style="width: 100% !important;display:inline-block;">
                    <div style="width: 38% !important; display:inline-block;">
                        <div class="text-left">Closed By</div>
                    </div>
                    <div style="width: 60% !important; display:inline-block;">
                        <div class="text-right"> {{ $closeBy }}</div>
                    </div>
                </div>
            </div>
            <br>
        </div>
        <div class="content-section">
            <div class="receipt-header text-center">
                <strong>Tendered Payments </strong>
            </div>
            <table class="summary">
                <tbody>
                    <tr>
                        <td colspan="2">Cash:</td>
                        <td class="text-right">
                            @foreach ($payment_cash_data as $name => $items)
                                @php
                                    $total_amount = 0;
                                    $total_cash_transaction++;
                                @endphp
                                @foreach ($items as $item)
                                    @php
                                        $total_amount += (float) $item->price * $item->qty;
                                    @endphp
                                @endforeach
                                @php
                                    $total_tender += $total_amount;
                                @endphp
                                {{ number_format($total_amount, 2) }}
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">Debit Card:</td>
                        <td class="text-right">
                            @php
                                $total_amount = 0;
                                $total_debitcard_transaction++;
                            @endphp
                            @foreach ($payment_debitcard_data as $name => $items)
                                @php
                                    $total_debitcard_transaction++;
                                @endphp
                                @foreach ($items as $item)
                                    @php
                                        $total_amount += (float) $item->price * $item->qty;
                                        
                                    @endphp
                                @endforeach
                                @php
                                    $total_tender += $total_amount;
                                @endphp
                              
                            @endforeach
                            {{ number_format($total_amount, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">Credit Card:</td>
                        <td class="text-right">
                            @php
                            $total_amount = 0;
                            @endphp
                            @foreach ($payment_creditcard_data as $name => $items)
                                @php
                                    $total_amount = 0;
                                    $total_creditcard_transaction++;
                                @endphp
                                @foreach ($items as $item)
                                    @php
                                        $total_amount += (float) $item->price * $item->qty;
                                        
                                    @endphp
                                @endforeach
                                @php
                                    $total_tender += $total_amount;
                                @endphp
                            @endforeach
                            {{ number_format($total_amount, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">Check:</td>
                        <td class="text-right">0.00</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="total">Total </td>
                        <td class="text-right total">{{ number_format($total_tender, 2) }}</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="receipt-header text-center">
                <strong>Taxes Report</strong>
            </div>
             <table class="summary">
                 <tr>
                     <td>Vat Exempt Sales</td>
                     <td  class="text-right">{{number_format($totalvatexempt,2)}}</td>
                 </tr>
                 <tr>
                     <td>Vat Amount</td>
                     <td  class="text-right">{{number_format($totalvatamount,2)}}</td>
                 </tr>
                 <tr>
                    <td class="total">Total</td>
                    <td  class="text-right total">{{number_format($totalvatsales,2)}}</td>
                </tr>
             </table>
             <div class="receipt-header text-center">
                <strong>Discount Report</strong>
            </div>
             <table class="summary">
                 <tr>
                     <td>Senior/ PWD Discount</td>
                     <td  class="text-right">{{number_format($totaldiscount,2)}}</td>
                 </tr>
                 <tr>
                    <td class="total">Total</td>
                    <td  class="text-right total">{{number_format($totaldiscount,2)}}</td>
                </tr>
             </table>
             <div class="receipt-header text-center">
                <strong>Refund Payments</strong>
            </div>
            <table class="summary">
                @php
                    $totalrefund =0;
                    $totalcount =0;
                @endphp
                @foreach ($return_item_group as $key => $items)
                    @php $price =0; @endphp
                        @foreach ($items as $item)
                            @php
                                $price +=$item->price * $item->qty;
                        @endphp
                    @endforeach
                    @php
                        $totalrefund +=$price;
                    @endphp
              
                @endforeach
                <tr>
                    <td colspan="2">Cash</td>
                    <td class="text-right">{{number_format($totalrefund,2)}}</td>
                </tr>
                <tr>
                    <td class="total" colspan="2">Total</td>
                    <td class="total text-right">{{number_format($totalrefund,2)}}</td>
                </tr>
            </table>

            <div class="receipt-header text-center">
                <strong>Expected Counts</strong>
            </div>
            <table class="summary">
                <tr>
                    <td colspan="2">Opening</td>
                    <td class="text-right">
                        {{number_format($zreport->opening_amount,2)}}
                    </td>
                </tr>
                <tr>
                    <td colspan="2">Sales</td>
                    <td class="text-right">
                        {{number_format($total_sales_amount,2)}}
                    </td>
                </tr>
                <tr>
                    <td class="total" colspan="2">Total</td>
                    <td class="total text-right">{{number_format(($zreport->opening_amount + $total_sales_amount),2)}}</td>
                </tr>
            </table>
            <div class="receipt-header text-center">
                <strong>Closing Counts</strong>
            </div>
            <table class="summary">
                <tr>
                    <td colspan="2">Opening</td>
                    <td class="text-right">
                        {{number_format($zreport->opening_amount,2)}}
                    </td>
                </tr>
                <tr>
                    <td colspan="2">Sales</td>
                    <td class="text-right">
                        {{number_format($zreport->closing_amount,2)}}
                    </td>
                </tr>
                <tr>
                    <td class="total" colspan="2">Total</td>
                    <td class="total text-right">{{number_format(($zreport->opening_amount + $zreport->closing_amount),2)}}</td>
                </tr>
            </table>
            <div class="receipt-header text-center">
                <strong>Variance</strong>
            </div>
            <table class="summary">
                <tr>
                    <td colspan="2">Cash</td>
                    <td class="text-right">
                        {{number_format(($total_sales_amount - $zreport->closing_amount),2)}}
                    </td>
                </tr>
               
                <tr>
                    <td class="total" colspan="2">Total</td>
                    <td class="total text-right">
                        @if(($total_sales_amount - $zreport->closing_amount) < 0)
                            <span>Over ==></span>
                        @endif 
                        @if(($total_sales_amount - $zreport->closing_amount) > 0)
                            <span>Short ==></span>
                        @endif
                        @if(($total_sales_amount - $zreport->closing_amount) == 0)
                            
                        @endif
                        {{number_format(($total_sales_amount - $zreport->closing_amount),2)}}</td>
                </tr>
            </table>
            <div class="receipt-header text-center">
                <strong>Stats</strong>
            </div>
            <table class="summary">
                <tr>
                    <td colspan="2">Total Transaction</td>
                    <td class="text-right">
                        {{(int)$total_sales_transaction}}
                    </td>
                </tr>
                <tr>
                    <td class="total" colspan="2">Total Return</td>
                    <td class="total text-right">{{(int)$total_return_transaction}}</td>
                </tr>
            </table>
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
          
            
            <div class="receipt-footer text-center"><br>
                <strong>Thank you for choosing us!</strong><br><br>
                <div class="company-name">Store : {{ $possetting->company_name }}</div>
                <div class="company-address">Address : {{ $possetting->company_address_bldg }}
                    {{ $possetting->company_address_streetno }}</div>
                <div class="company-address">Contact : {{ $possetting->company_address_bldg }}</div><br>
            </div>
        </div>
    </div>
</body>

</html>
