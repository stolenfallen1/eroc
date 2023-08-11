<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Summary Report</title>
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
                <div class="company-address"><h4  style="margin:  15px 0px 0px 0px;">SUMMARY SALES REPORT</h4></div>
            </div>
        </div>
    </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 68.5% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                    <div class="text-right">Prepared By</div>
                </div>
                <div style="width: 58.5% !important; display:inline-block;">
                    <div class="">: {{ $cashiername }}</div>
                </div>
            </div>
            <div style="width: 29.5% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                    <div class="text-right">Print Time</div>
                </div>
                <div style="width: 58.5% !important; display:inline-block;">
                <div class="">: {{ $printtime }}</div>
                </div>
            </div>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 68.5% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                    <div class="text-right">User ID</div>
                </div>
                <div style="width: 58.5% !important; display:inline-block;">
                    <div class="">: {{ $userid }}</div>
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
                    <div class="text-right">Shift ID</div>
                </div>
                <div style="width: 58.5% !important; display:inline-block;">
                    <div class="">: {{ $shift }}</div>
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
                    <div class="">:{{ $transdate }}</div>
                </div>
            </div>
        </div>
        <div class="content-section">
            <table>
                <thead>
                    <tr>
                        <th>Invoice No.</th>
                        <th>Name </th>
                        <th  class="text-center">Amount</th>
                        <th  class="text-center">Mode of Payment</th>
                    </tr>
                </thead>
                <tbody>
                    @if(count($data) > 0)
                        @php $totalamount =0; @endphp
                        @foreach ($data as $item)
                            @php $totalamount += (float)$item['payment_total_amount']; @endphp
                            <tr >
                                <td>{{$item['sales_invoice_number']}}</td>
                                <td>{{ucfirst($item['customername'])}}</td>
                                <td class="text-center">{{number_format($item['payment_total_amount'],2)}}</td>
                                <td  class="text-center">{{$item['payment_method']}}</td>
                            </tr>
                        @endforeach
                        <tr  style="border-bottom:1px dashed #ddd;">
                            <td  colspan="4"></td>
                        </tr>
                        <tr>
                            <td  colspan="2"><b>OFFICIAL RECEIPTS SUB TOTAL</b></td>
                            <td class="text-center">{{number_format($totalamount,2) }}</td>
                            <td  colspan="1"></td>
                        </tr>
                        <tr style="border-bottom:1px solid black;">
                            <td  colspan="2"><b>GRAND TOTAL ==>></b></td>
                            <td class="text-center">{{number_format($totalamount,2) }}</td>
                            <td  colspan="1"></td>
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
