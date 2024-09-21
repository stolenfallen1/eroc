<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Expired Items</title>
    <style>
        .header-section {
            width: 100%;
            position: relative;
        }

        .header-text {
            /* position: absolute;
           
            margin-top: -20px; */
            text-align: center;
            width: 100%;
        }

        .qr-code {
            margin-left: 485px;
        }

        .title-section {
            width: 100%;
            text-align: center;
        }

        .info-section {
            width: 100%;
        }

        .item-section td,
        th {
            border: 1px solid;
        }

        .item-section {
            /* margin-top: 20px; */
            width: 100%;
            border-collapse: collapse;
        }

        h3 {
            font-weight: normal;
            letter-spacing: 2px;
        }

        h5 {
            font-weight: normal;
            letter-spacing: 1px;
        }

        .left-width {
            width: 100px;
        }

        .right-width {
            width: 80px;
        }

        .mid-width {
            width: 370px;
        }

        .underline {
            border-bottom: 1px black solid;
        }

        table {
            border-collapse: collapse !important;
            width: 100%;
        }

        td {
            font-size: 11px;
        }

        th {
            font-size: 11px;
            /* text-transform: uppercase; */
        }

        .item-td {
            text-align: center;
        }

        .spacer {
            margin-top: 1px;
            width: 100%;
            border-bottom: 2px solid;
        }

        .note {
            font-style: italic;
            font-size: 12px;
        }

        .reminder {
            font-weight: 300;
        }

        .signatory-section1 {
            margin-top: 20px;
            float: left;
        }

        .signatory-section2 {
            float: right;
        }

        .comptroller {
            padding-top: 25px !important;
        }

        .csstransforms {
            position: absolute;
            top: 200;
            left: 100;
            color: rgb(234, 223, 223);
            transform: rotate(330deg);
            text-transform: uppercase;
            font-size: 32px;
            z-index: -10;
        }

        .border-none {
            border: none !important;
        }

        .border-bottom {
            border-top: none !important;
            border-left: none !important;
            border-right: none !important;
        }

        td {
            text-align: center;
        }

        td:nth-child(4) {
            text-align: left;
        }

        td:nth-child(7) {
            text-align: left;
        }

        .category {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .pdf-header {
            width: 100%;
        }

        .inline-block-div1 {
            display: inline-block;
            width: 10px;
        }

        .inline-block-div2 {
            display: inline-block;
            width: 150px;
            background-color: blue;
            text-align: center;
        }

        .inline-block-div3 {
            display: inline-block;
            width: 10px;
        }

        .title {
            text-align: center;
            text-transform: uppercase;
        }

        @page {
            margin: 10;
            padding: 5;
        }
    </style>
</head>

<body>
    <div class="pdf-header">
        <table>
            <tr>
                <td style="width:10%;">
                    <img src="{{ $pdf_data['logo'] }}" alt="Example Image" width="110">
                </td>
                <td>
                    <div class="header-text">
                        <h1>{{$pdf_data['branch']['name']}}</h1>
                        <h4 style="margin: -10px !important;">{{$pdf_data['branch']['address']}}</h4>
                        <h4>TIN {{$pdf_data['branch']['TIN']}}</h4>
                    </div>
                </td>
                <td style="width:10%;">
                    <img src="{{ $pdf_data['qr'] }}" alt="Example Image" width="100" height="100">
                </td>
            </tr>
        </table>
    </div>
    <div class="header-section">
        <div class="title">
            <h4>{{$pdf_data['title']}}</h4>
        </div>
    </div>
    <div>
        @php
        $Grandtotalqty =0;
        $GrandtotalvatExclude =0;
        $GrandtotalgrossAmountVatEx =0;
        $GrandtotalseniorDiscount =0;
        $GrandtotalmanufactureDiscount =0;
        @endphp
        @if(count($pdf_data['data']) > 0)
        @foreach ($pdf_data['data'] as $category => $items)
        <div class="category">
            <div>{{$category}}</div>
        </div>
        <table class="item-section">
            <thead>
                <tr>
                    <th width="40">Store Branch / Hospital</th>
                    <th width="40">Date / Render Date</th>
                    <th width="30">OSCA ID No.</th>
                    <th width="70">Customer Name</th>
                    <th width="40">Receipt No.</th>
                    <th width="40">Item Code</th>
                    <th width="110">Product Name</th>
                    <th width="40">Qty Sold</th>
                    <th width="40">Retailer's Unit Price (VAT Inc)</th>
                    <th width="50">Retailer's Unit Price (VAT Ex)</th>
                    <th width="50">Gross Amount (VAT Ex)</th>
                    <th width="50">20% Senior Citizen Discount</th>
                    <th width="50">70% Manufacture Share/Unilab Share</th>
                </tr>
            </thead>
            <tbody>
                @php
                $totalqty =0;
                $totalvatExclude =0;
                $totalgrossAmountVatEx =0;
                $totalseniorDiscount =0;
                $totalmanufactureDiscount =0;
                @endphp
                @foreach ($items as $row)
                @php
                $totalqty += $row['order_item_qty'];
                $totalvatExclude += $row['vatExclude'];
                $totalgrossAmountVatEx += $row['grossAmountVatEx'];
                $totalseniorDiscount += $row['seniorDiscount'];
                $totalmanufactureDiscount += $row['manufactureDiscount'];
                @endphp
                <tr>
                    <td>{{$pdf_data['branch']['abbreviation']}}</td>
                    <td>{{$row['payment_date']}}</td>
                    <td>{{$row['oscaIDno']}}</td>
                    <td>{{$row['customerName']}}</td>
                    <td>{{$row['invoiceNo']}}</td>
                    <td>{{$row['itemID']}}</td>
                    <td>{{$row['itemdescription']}}</td>
                    <td>{{(double) round($row['order_item_qty'], 4)}}</td>
                    <td>{{number_format($row['order_item_cash_price'],4)}}</td>
                    <td>{{number_format($row['vatExclude'],4)}}</td>
                    <td>{{number_format($row['grossAmountVatEx'],4)}}</td>
                    <td>{{number_format($row['seniorDiscount'],4)}}</td>
                    <td>{{number_format($row['manufactureDiscount'],4)}}</td>
                </tr>
                @endforeach
                @php
                $Grandtotalqty +=$totalqty;
                $GrandtotalvatExclude +=$totalvatExclude;
                $GrandtotalgrossAmountVatEx +=$totalgrossAmountVatEx;
                $GrandtotalseniorDiscount +=$totalseniorDiscount;
                $GrandtotalmanufactureDiscount +=$totalmanufactureDiscount;
                @endphp
                <tr class="border-none">
                    <td class="border-bottom">Total</td>
                    <td class="border-bottom"></td>
                    <td class="border-bottom"></td>
                    <td class="border-bottom"></td>
                    <td class="border-bottom"></td>
                    <td class="border-bottom"></td>
                    <td class="border-bottom"></td>
                    <td class="border-bottom">{{(double) round($totalqty, 4)}}</td>
                    <td class="border-bottom"></td>
                    <td class="border-bottom">{{(double) round($totalvatExclude, 4)}}</td>
                    <td class="border-bottom">{{(double) round($totalgrossAmountVatEx, 4)}}</td>
                    <td class="border-bottom">{{(double) round($totalseniorDiscount, 4)}}</td>
                    <td class="border-bottom">{{(double) round($totalmanufactureDiscount, 4)}}</td>
                </tr>
                <tr class="border-none">
                    <td colspan="13" class="border-none"><br></td>
                </tr>
            </tbody>
        </table>
        @endforeach
        @else
        <table class="item-section">
            <thead>
                <tr>
                    <th width="40">Store Branch / Hospital</th>
                    <th width="40">Date / Render Date</th>
                    <th width="30">OSCA ID No.</th>
                    <th width="70">Customer Name</th>
                    <th width="40">Receipt No.</th>
                    <th width="40">Item Code</th>
                    <th width="110">Product Name</th>
                    <th width="40">Qty Sold</th>
                    <th width="40">Retailer's Unit Price (VAT Inc)</th>
                    <th width="50">Retailer's Unit Price (VAT Ex)</th>
                    <th width="50">Gross Amount (VAT Ex)</th>
                    <th width="50">20% Senior Citizen Discount</th>
                    <th width="50">70% Manufacture Share/Unilab Share</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-none">
                    <td colspan="13" class="border-none">
                        No record found
                    </td>
                </tr>
            </tbody>
        </table>
        @endif
        <table>
            <tr>
            <tr>
                <th class="border-bottom" width="40">Grand Total</th>
                <th class="border-bottom" width="40"></th>
                <th class="border-bottom" width="30"></th>
                <th class="border-bottom" width="70"></th>
                <th class="border-bottom" width="40"></th>
                <th class="border-bottom" width="40"></th>
                <th class="border-bottom" width="110"></th>
                <th class="border-bottom" width="40">{{$Grandtotalqty}}</th>
                <th class="border-bottom" width="40"></th>
                <th class="border-bottom" width="50">{{$GrandtotalvatExclude}}</th>
                <th class="border-bottom" width="50">{{$GrandtotalgrossAmountVatEx}}</th>
                <th class="border-bottom" width="50">{{$GrandtotalseniorDiscount}}</th>
                <th class="border-bottom" width="50">{{$GrandtotalmanufactureDiscount}}</th>
            </tr>
            </tr>
        </table>
    </div>
</body>

</html>