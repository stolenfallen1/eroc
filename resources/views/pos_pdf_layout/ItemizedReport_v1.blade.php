<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Itemized Report</title>
        <style>
             body{
                font-family: arial, sans-serif;
            }
            .header-section{
              display: block;
              width: 100% !important;
              margin-bottom: 2%;
            }
            .company-details{
                width: 100% !important;
                text-align: center !important;
            }
           
            table{
                font-family:serif;
                border-collapse: collapse;
                width: 100%;
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
             .text-left{
                text-align: left !important;
             }
            @page {
                 margin: 15px !important;
                width: 100%;
                font-size: 16px !important;
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
        <div style="border-bottom:1px solid black;">
            <div class="dflex" style="width: 100% !important;">
                <div style="width: 68.5% !important;display:inline-block;">
                    <div style="width: 30% !important; display:inline-block;">
                        <div class="text-right">Terminal ID</div>
                    </div>
                    <div style="width: 58.5% !important; display:inline-block;">
                        <div class="">: {{$terminalid}}</div>
                    </div>
                </div>
                <div style="width: 29.5% !important;display:inline-block;">
                    <div style="width: 30% !important; display:inline-block;">
                        <div class="text-right">Print Date</div>
                    </div>
                    <div style="width: 58.5% !important; display:inline-block;">
                        <div class="">: {{$printdate}}</div>
                    </div>
                </div>
            </div>
            <div class="dflex" style="width: 100% !important;">
                <div style="width: 68.5% !important;display:inline-block;">
                    <div style="width: 30% !important; display:inline-block;">
                        <div class="text-right">User ID</div>
                    </div>
                    <div style="width: 58.5% !important; display:inline-block;">
                        <div class="">: {{$userid}}</div>
                    </div>
                </div>
                <div style="width: 29.5% !important;display:inline-block;">
                    <div style="width: 30% !important; display:inline-block;">
                        <div class="text-right">Print Time</div>
                    </div>
                    <div style="width: 58.5% !important; display:inline-block;">
                        <div class="">: {{$printtime}}</div>
                    </div>
                </div>
            </div>
            <div class="dflex" style="width: 100% !important;">
                <div style="width: 68.5% !important;display:inline-block;">
                    <div style="width: 30% !important; display:inline-block;">
                        <div class="text-right">Shift ID</div>
                    </div>
                    <div style="width: 58.5% !important; display:inline-block;">
                        <div class="">: {{$shift == 0 ? 'All' : $shift}}</div>
                    </div>
                </div>
                <div style="width: 29.5% !important;display:inline-block;">
                    <div style="width: 30% !important; display:inline-block;">
                    </div>
                    <div style="width: 58.5% !important; display:inline-block;">
                    </div>
                </div>
            </div>
            <div class="dflex" style="width: 100% !important;">
                <div style="width: 68.5% !important;display:inline-block;">
                    <div style="width: 30% !important; display:inline-block;">
                        <div class="text-right">Transaction Date</div>
                    </div>
                    <div style="width: 58.5% !important; display:inline-block;">
                        <div class="">:{{$transdate}}</div>
                    </div>
                </div>
               
            </div>
        </div>
        <div class="content-section">
            <table>
                <tbody>
                   
                    @if(count($data) > 0)
                        @php 
                           
                            $groupedperShift = array();
                            // Iterate through the items using foreach
                            foreach ($data as $item) {
                                $shift = $item->shift_description;
                                if (isset($groupedperShift[$shift])) {
                                    // If the category exists, add the item to the category's array
                                    $groupedperShift[$shift][] = $item;
                                } else {
                                    // If the category doesn't exist, create a new array for the category and add the item
                                    $groupedperShift[$shift] = array($item);
                                }
                            }    
                        $grandtotalamount = 0;
                        @endphp
                      
                       @foreach($groupedperShift as $shift => $shiftitem)
                            <tr>
                                <td colspan="9"><?php echo $shift;?></td>
                            </tr>
                            @php 
                                $groupedstatus = array();
                                foreach ($shiftitem as $item) {
                                    $status = $item->statusdesc;
                                    $shift = $item->shift_description;
                                    // Check if the status exists in the groupedItems array
                                    if (isset($groupedstatus[$status])) {
                                        // If the status exists, add the item to the status's array
                                        $groupedstatus[$status][] = $item;
                                    } else {
                                        // If the status doesn't exist, create a new array for the status and add the item
                                        $groupedstatus[$status] = array($item);
                                    }
                                }    
                           
                            $subtotal = 0; 
                            @endphp
                            @foreach ($groupedstatus as $statusname => $invgroupitem)
                                <tr>
                                        <td colspan="9"><?php echo $statusname;?></td>
                                </tr>
                                @php 
                                 $invgroupdata = array(); 
                               
                                @endphp
                                @foreach($invgroupitem as $invgroup)
                                        <?php
                                            $invgroupname = $invgroup->invgroup;
                                            // Check if the category exists in the groupedItems array
                                            if (isset($invgroupdata[$invgroupname])) {
                                                // If the invgroupname exists, add the item to the invgroupname's array
                                                $invgroupdata[$invgroupname][] = $invgroup;
                                            } else {
                                                // If the invgroupname doesn't exist, create a new array for the invgroupname and add the item
                                                $invgroupdata[$invgroupname] = array($invgroup);
                                            }
                                        ?>
                                @endforeach
                               
                                @foreach($invgroupdata as $invgroupname => $categories)
                                        <tr>
                                            <td style="width: 2%;"></td>
                                            <td  style="width: 90%;" colspan="8" class="text-left">{{$invgroupname}}</td>
                                        </tr>
                                        @php $categorygroupdata = array(); @endphp
                                        @foreach ($categories as $category)
                                            <?php
                                                $categoriesgroupname = $category->categories;
                                                // Check if the category exists in the groupedItems array
                                                if (isset($categorygroupdata[$categoriesgroupname])) {
                                                    // If the categoriesgroupname exists, add the item to the invgroupname's array
                                                    $categorygroupdata[$categoriesgroupname][] = $category;
                                                } else {
                                                    // If the invgroupname doesn't exist, create a new array for the invgroupname and add the item
                                                    $categorygroupdata[$categoriesgroupname] = array($category);
                                                }
                                            ?>
                                        @endforeach
                                      
                                        @foreach($categorygroupdata as $categoryname => $items)
                                                
                                            <tr>
                                                <td style="width:1%;"></td>
                                                <td style="width:1%;"></td>
                                                <td  style="width: 90%;" colspan="7" class="text-left">{{$categoryname}}</td>
                                            </tr>
                                            <tr>
                                                <td style="width:1%;"></td>
                                                <td style="width:2%;"></td>
                                                <td style="width:1%;"></td>
                                                <td style="width:4%;"><u><b>Order ID</u></b></td>
                                                <td style="width:4%;"><u><b>Item ID</u></b></td>
                                                <td style="width:4%;"><u><b>Item Name</u></b></td>
                                                <td style="width:4%;"><u><b>Qty</u></b></td>
                                                <td style="width:4%;"><u><b>Price</u></b></td>
                                                <td style="width:4%;"><u><b>Total</u></b></td>
                                            </tr>
                                            
                                            @php  
                                               $totalamount = 0; 
                                            @endphp
                                            @foreach ($items as $item)
                                                @php  
                                                    $totalamount += ((float)$item->price * $item->qty) 
                                                @endphp
                                                <tr>
                                                    <td style="width:1%;"></td>
                                                    <td style="width:1%;"></td>
                                                    <td style="width:1%;"></td>
                                                    <td >{{$item->orderid}}</td>
                                                    <td >{{$item->itemid}}</td>
                                                    <td style="width:20%;" >{{$item->itemname}}</td>
                                                    <td >{{(int)$item->qty}}</td>
                                                    <td style="width:2%;">{{number_format(((float)$item->price),2)}}</td>
                                                    <td style="width:2%;">{{number_format(((float)$item->price * $item->qty),2)}}</td>
                                                </tr>
                                            @endforeach
                                            @php  
                                                $subtotal += ((float)$totalamount) 
                                            @endphp
                                            <tr>
                                                <td  class="text-right" colspan="8"><u><b>SubTotal ==> </b></u></td>
                                                <td class="text-left" style="width:2%;">{{number_format($totalamount,2) }}</td>
                                            </tr>
                                            <tr >
                                                <td  colspan="9"></td>
                                            </tr>
                                        @endforeach
                                        <tr >
                                            <td  colspan="9"></td>
                                        </tr>
                                        <tr>
                                            <td  class="text-right" colspan="8"><u><b>Total ==> </b></u></td>
                                            <td class="text-left" style="width:2%;">{{number_format($subtotal,2) }}</td>
                                        </tr>
                                        <tr >
                                            <td  colspan="9"></td>
                                        </tr>
                                        
                                @endforeach
                                
                            @endforeach
                            @php  
                                $grandtotalamount += $subtotal
                            @endphp
                        @endforeach
                        <tr >
                            <td  colspan="9"></td>
                        </tr>
                        <tr>
                            <td  class="text-right" colspan="8"><u><b>Grand Total ==> </b></u></td>
                            <td class="text-left" style="width:2%;">{{number_format($grandtotalamount,2) }}</td>
                        </tr>
                    @else 
                        <tr style="border-bottom:1px solid black;">
                            <td colspan="9">No Record found!</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </body>
</html>
