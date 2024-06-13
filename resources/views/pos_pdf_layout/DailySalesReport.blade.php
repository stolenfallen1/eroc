<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daily Sales Report</title>
    <style>
        .header-section {
            display: block;
            width: 100% !important;
            margin-bottom: 2%;
            
        }

        .company-details {
            width: 100% !important;
            text-align: center !important;
        }

        table {
            font-family: serif;
            border-collapse: collapse;
            width: 100%;
        }

        .content-section {
            width: 100%;
        }

        table>thead>tr>th {
            border-top: 1px solid black;
            border-bottom: 1px solid black;
            text-align: left;
        }

        table>tbody>tr>td {
            text-transform: initial !important;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
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
            font-size: 16px !important;
        }
    </style>
</head>

<body>
    <div class="header-section">
        <div class="company-details">
            <div class="company-name">{{ $possetting->company_name }}</div>
            <div class="company-address">{{ $possetting->company_address_bldg }}
                {{ $possetting->company_address_streetno }}</div>
            <div class="company-address">
                <h4 style="margin:  15px 0px 0px 0px;">DAILY SALES REPORT</h4>
            </div>
        </div>
    </div>
    </div>
    <div style="border-bottom:1px solid black;">
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 68.5% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                    <div class="text-right">Terminal ID</div>
                </div>
                <div style="width: 58.5% !important; display:inline-block;">
                    <div class="">: {{ $terminalid }}</div>
                </div>
            </div>
            <div style="width: 29.5% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                    <div class="text-right">Print Date</div>
                </div>
                <div style="width: 58.5% !important; display:inline-block;">
                    <div class="">: {{ $printdate }}</div>
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
    </div>
    <div class="content-section">
       
        <table>
            <thead>
                <tr>
                    <th>Invoice No.</th>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                    <th class="text-center">Mode of Payment</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $totalamount = 0;
                @endphp
                @foreach ($data as $row)
                    @php $totalamount += (float)$row->totalamount; @endphp
                    <tr>
                        <td >{{$row->invnno}}</td>
                        <td >{{$row->itemname}}</td>
                        <td class="text-right" >{{number_format($row->totalamount,2)}}</td>
                        <td class="text-center">{{$row->Method}}</td>
                    </tr>
                @endforeach
                <tr  style="border-top:1px dashed #ddd;">
                    <td  colspan="4"></td>
                </tr>
                <tr style="border-bottom:1px solid black;">
                    <td  colspan="2" class="text-right"><b>GRAND TOTAL ==>></b></td>
                    <td class="text-right">{{number_format($totalamount,2) }}</td>
                    <td  colspan="1"></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
