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
            font-size: 12px;
        }

        th {
            font-size: 12px;
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

        td {
            text-align: center;
        }

        td:nth-child(2) {
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
        $grandTotalqty =0;
        $grandTotalCost =0;
        @endphp
        @foreach ($pdf_data['data'] as $category => $items)
        <div class="category">
            <div>{{$category}}</div>
        </div>
        <table class="item-section">
            <thead>
                <th style="width: 50px;">Code</th>
                <th style="width: 250px;">Item Name</th>
                <th style="width: 150px;">Description</th>
                <th style="width: 60px;">Qty</th>
                <th style="width: 60px;">Unit</th>
                <th style="width: 60px;">Unit Cost</th>
                <th style="width: 60px;">Total Cost</th>
                <th style="width: 100px;">Batch Number</th>
                <th style="width: 100px;">Batch Expiry Date</th>
            </thead>
            <tbody>
                @php
                $totalqty =0;
                $totalCost =0;
                @endphp
                @foreach ($items as $row)
                @php
                $totalqty += $row['itemonhand'];
                $totalCost += ($row['item_Selling_Price_Out'] * $row['itemonhand']);
                @endphp
                <tr>
                    <td>{{$row['id']}}</td>
                    <td>{{$row['item_name']}}</td>
                    <td>{{$row['item_Description']}}</td>
                    <td>{{$row['itemonhand']}}</td>
                    <td>{{$row['unit']}}</td>
                    <td>{{number_format($row['item_Selling_Price_Out'],2)}}</td>
                    <td>{{number_format(($row['item_Selling_Price_Out'] * $row['itemonhand']),2)}}</td>
                    <td>{{$row['batch_Number']}}</td>
                    <td>{{date("m-d-Y",strtotime($row['item_Expiry_Date']))}}</td>
                </tr>
                @endforeach
                @php
                $grandTotalqty +=$totalqty;
                $grandTotalCost +=$totalCost;
                @endphp
                <tr>
                    <td class="border-none" ></td>
                    <td class="border-none"></td>
                    <td class="border-none"></td>
                    <td class="border-none">{{(float)$totalqty}}</td>
                    <td class="border-none"></td>
                    <td class="border-none"></td>
                    <td class="border-none">{{number_format($totalCost,2)}}</td>
                    <td class="border-none"></td>
                    <td class="border-none"></td>
                </tr>
                <tr class="border-none">
                    <td colspan="9" class="border-none"><br></td>
                </tr>
            </tbody>
        </table>
        @endforeach
        <table class="item-section">
            <tr class="border-none">
                <td class="border-none" style="width: 100px;font-weight:bold;">Total Qty</td>
                <td class="border-none" style="font-weight:bold;">: {{$grandTotalqty}}</td>
            </tr>
            <tr class="border-none">
                <td class="border-none" style="width: 100px;font-weight:bold;">Total Cost</td>
                <td class="border-none" style="font-weight:bold;">: {{number_format($grandTotalCost,2)}}</td>
            </tr>

            <tr class="border-none">
                <td class="border-none" style="width: 100px;font-weight:bold;">Print Date</td>
                <td class="border-none" style="font-weight:bold;">: {{date('m-d-Y H:i:s A')}}</td>
            </tr>

        </table>
    </div>
    <div>
       
    </div>
</body>

</html>