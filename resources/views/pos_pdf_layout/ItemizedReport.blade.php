<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Itemized Report</title>
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
                <div class="company-address"><h4  style="margin:  15px 0px 0px 0px;">ITEMIZED SALES REPORT</h4></div>
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
                        <th>Category</th>
                        <th>Item Name / Description </th>
                        <th class="text-center">Qty</th>
                        <th >Price</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @if(count($data) > 0)
                    <?php
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
                        $totalamount = 0;
                        foreach ($groupedItems as $category => $items) {
                            ?>
                                <tr >
                                    <td  colspan="5">{{$category}}</td>
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
                                $subtotalamount = 0;  
                                $count =1;
                                foreach ($itemGroup as  $itemname => $itemval) {
                                    foreach($itemval as $key =>$row) {
                                        $subtotalamount +=(float)$row['price'] * (float)$row['qty'];
                                        ?>
                                            <tr>
                                                <td  class="text-center">{{$count++}}</td>
                                                <td >{{$itemname}}</td>
                                                <td class="text-center">{{(float)$row['qty']}}</td>
                                                <td>{{number_format((float)$row['price'], 2)}}</td>
                                                <td >{{number_format(((float)$row['price'] * (float)$row['qty']), 2)}}</td>
                                            </tr>
                                        <?php
                                    }
                                }
                                $totalamount +=$subtotalamount;
                                ?>
                                 <tr>
                                    <td colspan="4" style="text-align:right"><b>Sub Total: </b></td>
                                    <td>{{number_format($subtotalamount, 2)}}</td>
                                </tr>
                                <tr  style="border-bottom:1px dashed #ddd;">
                                    <td  colspan="5"></td>
                                </tr>
                                <?php
                        }
                        
                    ?>
                    <tr style="border-bottom:1px solid black;">
                        <td colspan="4" style="text-align:right"><b>Total Amount: </b></td>
                        <td> {{number_format($totalamount, 2)}}</td>
                    </tr>
                    @else
                        <tr  style="border-bottom:1px solid black;">
                            <td colspan="5">NO RECORD FOUND!</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </body>
</html>
