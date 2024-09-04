<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Item by Batch Report</title>
        <style>
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
                <div class="company-address"><h4 style="margin: 15px 0px 0px 0px;">BATCH SALES REPORT</h4></div>
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
                        <th>Batch No.</th>
                        <th>Invoice No. </th>
                        <th>Trans. Date </th>
                        <th>Name </th>
                        <th class="text-left">Amount</th>
                        <th class="text-center">Mode of Payment</th>
                    </tr>
                </thead>
                <tbody>
                    @if(count($data) > 0)
                    <?php
                        $groupedItems = array();
                        $groupedsalesinvoiceItems = array();
                        $totalamount = 0;
                        // Iterate through the items using foreach
                        foreach ($data as $item) {
                            $sales_batch_number = $item['sales_batch_number'];
                            // Check if the category exists in the groupedItems array
                            if (isset($groupedItems[$sales_batch_number])) {
                                // If the category exists, add the item to the category's array
                                $groupedItems[$sales_batch_number][] = $item;
                            } else {
                                // If the category doesn't exist, create a new array for the category and add the item
                                $groupedItems[$sales_batch_number] = array($item);
                            }
                        } 

                        foreach ($groupedItems as $batchnumber => $invoices) {
                          
                            
                            foreach ($invoices as $invoice) {
                                $sales_invoice = $invoice['sales_invoice_number'];
                                // Check if the category exists in the groupedItems array
                                if (isset($groupedsalesinvoiceItems[$sales_invoice])) {
                                    // If the category exists, add the item to the category's array
                                    $groupedsalesinvoiceItems[$sales_invoice][] = $invoice;
                                } else {
                                    // If the category doesn't exist, create a new array for the category and add the item
                                    $groupedsalesinvoiceItems[$sales_invoice] = array($invoice);
                                }
                            } 
                           
                            foreach($groupedsalesinvoiceItems as $invoice => $item){
                                $totalamount +=  ((float)$item[0]['totalamountsales'])
                                ?>
                                    <tr  style="border-bottom:1px dashed #ddd;">
                                        <td style="width: 12%;" >{{$batchnumber}}</td>
                                        <td style="width: 12%;" >{{$invoice}}</td>
                                        <td style="width: 12%;">{{date("Y-m-d",strtotime($invoices[0]['transaction_date']))}}</td>
                                        <td style="width: 40%;" >{{$item[0]['customername']}}</td>
                                        <td class="text-left" style="width: 10%;" >{{number_format($item[0]['totalamountsales'],2)}}</td>
                                        <td class="text-center" style="width: 20%;" >{{$item[0]['payment_method']}}</td>
                                    </tr>
                                <?php
                                foreach($item as $row){
                                   
                                ?>
                                    <tr  >
                                        <td  colspan="3"></td>
                                        <td style="width: 40%;" >{{ucfirst($row['item_name'])}}</td>
                                        <td class="text-left" style="width: 10%;" >{{number_format($row['price'],2)}}</td>
                                        <td class="text-center" style="width: 20%;" ></td>
                                    </tr>
                                <?php
                                }   
                                ?>
                                 <tr  style="border-bottom:1px dashed #ddd;">
                                    <td  colspan="6"></td>
                                </tr>
                                <?php
                            } 
                        }    
                        
                    ?>
                    <tr style="border-bottom:1px solid black;">
                        <td  colspan="4" class="text-right"><b>TOTAL AMOUNT:</b></td>
                        <td class="text-left">{{number_format($totalamount,2) }}</td>
                        <td  colspan="1"></td>
                    </tr>
                    @else
                        <tr  style="border-bottom:1px solid black;">
                            <td colspan="6">NO RECORD FOUND!</td>
                        </tr>
                    @endif
                </tbody>
            </table>
            {{-- <table>
                <thead>
                    <tr>
                        <th >Batch No</th>
                        <th >Item Name / Description</th>
                        <th >Qty</th>
                        <th >Price</th>
                        <th >Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalamount = 0; @endphp
                    @foreach ($data as $item)
                        @php $totalamount +=  ((float)$item['price'] * (float)$item['qty']) @endphp
                        <tr >
                            <td>{{$item['batch_id']}}</td>
                            <td>{{ucfirst($item['item_name'])}}</td>
                            <td class="text-center">{{ (float)$item['qty'] }}</td>
                            <td class="text-center">{{number_format($item['price'],2)}}</td>
                            <td class="text-center">{{number_format(((float)$item['price'] * (float)$item['qty']), 2)}}</td>
                        </tr>
                    @endforeach
                        <tr>
                            <td  colspan="5"><br></td>
                        </tr>
                        <tr>
                            <td  colspan="3">Total Amount</td>
                            <td class="text-center">{{number_format($totalamount,2) }}</td>
                            <td class="text-center">{{number_format($totalamount,2) }}</td>
                        </tr>
                </tbody>
            </table> --}}
        </div>
    </body>
</html>
