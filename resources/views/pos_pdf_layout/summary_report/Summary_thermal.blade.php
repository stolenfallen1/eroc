<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SUMMARY SALES REPORT</title>
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
        .receipt-footer {
            border-top: 1px dashed black;
            border-bottom: 1px dashed black;
            padding: 2px;
        }
    </style>
</head>
    @php 
        $payment_cash_data = array();
        $payment_creditcard_data = array();
        $payment_debitcard_data = array();
        $cashier_data = array();
        $total_sales_amount=0;
        $total_transaction = 0;
        // Iterate through the items using foreach
        foreach($data as $item) {
            $total_transaction++;
            $total_sales_amount += (float)$item->price * $item->qty;
            if($item->method == 'Cash'){
                $cash_payment = $item->method;
                // Check if the category exists in the groupedItems array
                if (isset($payment_cash_data[$cash_payment])) {
                    // If the category_data exists, add the item to the category_data's array
                    $payment_cash_data[$cash_payment][] = $item;
                } else {
                    // If the category doesn't exist, create a new array for the category and add the item
                    $payment_cash_data[$cash_payment] = array($item);
                }
            }
            if($item->method =='Credit Card'){
                $credit_payment = $item->method;
                // Check if the category exists in the groupedItems array
                if (isset($payment_creditcard_data[$credit_payment])) {
                    // If the category_data exists, add the item to the category_data's array
                    $payment_creditcard_data[$credit_payment][] = $item;
                } else {
                    // If the category doesn't exist, create a new array for the category and add the item
                    $payment_creditcard_data[$credit_payment] = array($item);
                }
            }
            if($item->method =='Debit Card'){
                $debit_payment = $item->method;
                // Check if the category exists in the groupedItems array
                if (isset($payment_debitcard_data[$debit_payment])) {
                    // If the category_data exists, add the item to the category_data's array
                    $payment_debitcard_data[$debit_payment][] = $item;
                } else {
                    // If the category doesn't exist, create a new array for the category and add the item
                    $payment_debitcard_data[$debit_payment] = array($item);
                }
            }
            $cashier_name = $item->cashier_name;
            // Check if the category exists in the groupedItems array
            if (isset($cashier_data[$cashier_name])) {
                // If the category_data exists, add the item to the category_data's array
                $cashier_data[$cashier_name][] = $item;
            } else {
                // If the category doesn't exist, create a new array for the category and add the item
                $cashier_data[$cashier_name] = array($item);
            }
        }    
    @endphp
<body>
    <div class="header-section">
        <div class="company-details">
            <div class="company-address">
                <h4>SUMMARY SALES REPORT </h4>
            </div>
        </div>
    </div>
    <br>
    <div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                    <div class="text-left">Terminal ID</div>
                </div>
                <div style="width: 30% !important; display:inline-block;">
                    <div class="">: {{ $terminalid }}</div>
                </div>
            </div>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                    <div class="text-left">Date</div>
                </div>
                <div style="width: 30% !important; display:inline-block;">
                    <div class="">: {{ $printdate }}</div>
                </div>
            </div>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                    <div class="text-left">Time Range</div>
                </div>
                <div style="width: 60% !important; display:inline-block;">
                    <div class="">: {{ $printtime }} - {{ $printtime }}</div>
                </div>
            </div>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 43% !important; display:inline-block;">
                    <div class="text-left">Total Sales</div>
                </div>
                <div style="width: 30% !important; display:inline-block;">
                    <div class="">: {{number_format($total_sales_amount,2)}}</div>
                </div>
            </div>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 43% !important; display:inline-block;">
                    <div class="text-left">Total Transactions</div>
                </div>
                <div style="width: 30% !important; display:inline-block;">
                    <div class="">: {{ $total_transaction }}</div>
                </div>
            </div>
        </div>

        <div class="dflex" style="width: 100% !important;">
            <div style="width: 100% !important;display:inline-block;">
                <div style="width: 55% !important; display:inline-block;">
                    <div class="text-left">Avg. Transaction Value</div>
                </div>
                <div style="width: 30% !important; display:inline-block;">
                    <div class="">: {{number_format( ($total_sales_amount / $total_transaction) ,2)}}</div>
                </div>
            </div>
        </div>
        <br>
        <br>
    </div>
    <div class="content-section">
        <div class="receipt-header">
            <strong>PRODUCT SALES</strong>
        </div>
        <table class="summary">
            @foreach ($data as $item)
                <tr>
                    <td colspan="2">{{$item->itemname }}</td>
                    <td class="text-right">{{ number_format((float) $item->price * $item->qty, 2) }}</td>
                </tr>
            @endforeach
        </table>
        <br>
        <div class="receipt-header">
            <strong>Sales by Category</strong>
        </div>
        @php 
            $category_data = array();
            // Iterate through the items using foreach
            foreach($data as $item) {
                $category = $item->categories;
                // Check if the category exists in the groupedItems array
                if (isset($category_data[$category])) {
                    // If the category_data exists, add the item to the category_data's array
                    $category_data[$category][] = $item;
                } else {
                    // If the category doesn't exist, create a new array for the category and add the item
                    $category_data[$category] = array($item);
                }
            }    
        @endphp
        <table class="summary">
            @foreach ($category_data as $name=>$items)
                <tr>
                    <td colspan="2">{{ $name }}</td>
                    <td class="text-right">
                        @php 
                        $total_amount=0;
                        @endphp
                        @foreach($items as $item)
                            @php 
                                $total_amount += (float)$item->price * $item->qty;
                            @endphp
                        @endforeach
                        {{number_format($total_amount,2)}}
                    </td>
                </tr>
            @endforeach
        </table>
        <br>
        <div class="receipt-header">
            <strong>PAYMENT METHOD BREAKDOWN</strong>
        </div>
        <table class="summary">
            <tr>
                <td colspan="2">Cash:</td>
                <td class="text-right">
                    @foreach ($payment_cash_data as $name=>$items)
                        @php 
                            $total_amount=0;
                        @endphp
                        @foreach($items as $item)
                            @php 
                                $total_amount += (float)$item->price * $item->qty;
                            @endphp
                        @endforeach
                        {{number_format($total_amount,2)}}
                    @endforeach 
                </td>
            </tr>
            <tr>
                <td colspan="2">Debit Card:</td>
                <td class="text-right">
                    @foreach ($payment_debitcard_data as $name=>$items)
                        @php 
                            $total_amount=0;
                        @endphp
                        @foreach($items as $item)
                            @php 
                                $total_amount += (float)$item->price * $item->qty;
                            @endphp
                        @endforeach
                        {{number_format($total_amount,2)}}
                    @endforeach 
                </td>
            </tr>
            <tr>
                <td colspan="2">Credit Card:</td>
                <td class="text-right">
                    @foreach ($payment_creditcard_data as $name=>$items)
                    @php 
                        $total_amount=0;
                    @endphp
                    @foreach($items as $item)
                        @php 
                            $total_amount += (float)$item->price * $item->qty;
                        @endphp
                    @endforeach
                    {{number_format($total_amount,2)}}
                @endforeach 
                </td>
            </tr>
            <tr>
                <td colspan="2">Check:</td>
                <td class="text-right">0</td>
            </tr>
        </table>

        <br>
        <div class="receipt-header">
            <strong>Sales by Time of Day</strong>
        </div>
        <table class="summary">
            <tr>
                <td colspan="2">Morning:</td>
                <td class="text-right">123</td>
            </tr>
            <tr>
                <td colspan="2">Afternoon:</td>
                <td class="text-right">123</td>
            </tr>
            <tr>
                <td colspan="2">Evening:</td>
                <td class="text-right">123</td>
            </tr>
        </table>

        <br>
        <div class="receipt-header">
            <strong>Sales by Employee</strong>
        </div>
        <table class="summary">
            @foreach ($cashier_data as $name=>$items)
                <tr>
                    <td colspan="2">{{ $name }}</td>
                    <td class="text-right">
                        @php 
                            $total_amount=0;
                        @endphp
                        @foreach($items as $item)
                            @php 
                                $total_amount += (float)$item->price * $item->qty;
                            @endphp
                        @endforeach
                        {{number_format($total_amount,2)}}
                    </td>
                </tr>
            @endforeach
        </table>
        <br>
        <div class="receipt-footer"><br>
            <strong>Thank you for choosing us!</strong><br><br>
            <div class="company-name">Store : {{$possetting->company_name}}</div>
            <div class="company-address">Address : {{$possetting->company_address_bldg}} {{$possetting->company_address_streetno}}</div>
            <div class="company-address">Contact : {{$possetting->company_address_bldg}}</div><br>
        </div>
    </div>
</body>

</html>
