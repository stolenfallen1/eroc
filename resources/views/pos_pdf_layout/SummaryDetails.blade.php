<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Detailed Report</title>
        <style>
            .header-section{
              display: block;
              width: 100% !important;
              margin-bottom: 2%;
              font-size: 1.2em !important;
              @if($print_layout == '2')
              font-size: 0.5em !important;
              @endif
            }
            .company-details{
                width: 100% !important;
                text-align: center !important;
            }
           
            table{
                font-family:serif;
                border-collapse: collapse;
                width: 100%;
                @if($print_layout == '2')
                font-size: 0.5em !important;
                @endif
            }
            .content-section{
                width: 100%;
            }
            table>thead>tr>th{
                border-top: 1px solid black;
                border-bottom: 1px  solid black;
                text-align: left;
            }
            table>tbody>tr>td{
              text-transform: initial !important;
            }
            .text-center{
                text-align: center;
            }
            .text-right{
                text-align: right;
            }
            .dflex{
                display: inline-block;
                width: 100%;
             }
             .flex{
                width: 45% !important;
                display: inline-block;
             }
             .flex-50{
                width: 30% !important;
                display: inline-block;
             }
            .flex-80{
                width:60% !important;
                display: inline-block;
             }
             .flex-10{
                width: 40% !important;
                display: inline-block;
             }
            .flex-5{
                width: 30% !important;
                display: inline-block;
             }
            @page {
                 margin: 15px !important;
                width: 100%;
                font-size: 14px !important;
             }
        </style>
    </head>
    <body>
        <div class="header-section">
            <div class="company-details">
                <div class="company-name">{{$possetting->company_name}}</div>
                <div class="company-address">{{$possetting->company_address_bldg}} {{$possetting->company_address_streetno}}</div>
                <div class="company-address"><h4  style="margin: 15px 0px 0px 0px;">DETAILED SALES REPORT</h4></div>
            </div>
        </div>
        <div class="dflex">
            <div class="flex">
                <div class="flex-50">Cashier Name</div>
                <div class="flex-10">: {{Request()->payload['cashiername']}}</div>
            </div>
            <div class="flex">
                <div class="flex-80 text-right">Report Date</div>
                <div class="flex-5">: {{Request()->payload['date']}}</div>
            </div>
        </div>
        <div class="dflex">
            <div class="flex">
                <div class="flex-50"></div>
                <div class="flex-10"></div>
            </div>
            <div class="flex">
                <div class="flex-80 text-right">Shift</div>
                <div class="flex-5">: {{Request()->payload['shift']}}</div>
            </div>
        </div>
        <div class="content-section">
            <table>
                <thead>
                    <tr>
                        <th>Invoice No.</th>
                        <th>Trans. Date </th>
                        <th>Name </th>
                        <th>Amount</th>
                        <th class="text-center">Mode of Payment</th>
                    </tr>
                </thead>
                <tbody>
                    @if(count($data) > 0)
                    <?php
                        $groupedItems = array();
                        // Iterate through the items using foreach
                        foreach ($data as $item) {
                            $sales_invoice_number = $item['sales_invoice_number'];
                            // Check if the category exists in the groupedItems array
                            if (isset($groupedItems[$sales_invoice_number])) {
                                // If the category exists, add the item to the category's array
                                $groupedItems[$sales_invoice_number][] = $item;
                            } else {
                                // If the category doesn't exist, create a new array for the category and add the item
                                $groupedItems[$sales_invoice_number] = array($item);
                            }
                        }    
                        $totalamount = 0;
                     
                        foreach ($groupedItems as $invoice => $items) {
                            ?>
                                <tr  style="border-bottom:1px dashed #ddd;">
                                    <td style="width: 10%;" ><b>{{$invoice}}</b></td>
                                    <td style="width: 10%;"><b>{{date("Y-m-d",strtotime($items[0]['transaction_date']))}}</b></td>
                                    <td style="width: 40%;" ><b>{{$items[0]['customername']}}</b></td>
                                    <td style="width: 10%;" ><b>{{number_format($items[0]['totalamountsales'],2)}}</b></td>
                                    <td class="text-center" style="width: 20%;" ><b>{{$items[0]['payment_method']}}</b></td>
                                </tr>
                            <?php
                                $itemGroup = array();
                                foreach ($items as $item) {
                                    $itename = $item['item_name'];
                                    // Check if the category exists in the groupedItems array
                                    if (isset($itemGroup[$itename])) {
                                        // If the itename exists, add the item to the itename's array
                                        $itemGroup[$itename][] = $item;
                                    } else {
                                        // If the itename doesn't exist, create a new array for the itename and add the item
                                        $itemGroup[$itename] = array($item);
                                    }
                                }
                                $arraydata = array();
                                
                                $count =1;
                            
                                foreach ($itemGroup as  $itemname => $itemval) {
                                    $subtotalamount = 0; 
                                    
                                    foreach($itemval as $key =>$row) {
                                        $subtotalamount +=(float)$row['order_item_total_amount']; 
                                        ?>
                                            <tr>
                                                <td ></td>
                                                <td>{{$row['itemid']}}</td>
                                                <td >{{$itemname}}</td>
                                                <td >{{number_format(((float)$row['order_item_total_amount']), 2)}}</td>
                                                <td ></td>
                                            </tr>
                                            
                                        <?php
                                       
                                    }
                                    $totalamount +=$subtotalamount;
                                }
                               
                              
                                ?>
                                <tr>
                                    <td colspan="5"><br></td>
                                </tr>
                                <?php
                        }
                        
                    ?>
                    <tr  style="border-bottom:1px dashed #ddd;">
                        <td  colspan="5"></td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align:right"><b>Total Amount</b></td>
                        <td> {{number_format($totalamount, 2)}}</td>
                        <td ></td>
                    </tr>
                    @else
                        <tr style="border-bottom:1px solid black;">
                            <td colspan="5">NO RECORD FOUND!</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </body>
</html>
