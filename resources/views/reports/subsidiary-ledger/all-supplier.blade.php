<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delivery</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
        }

        .header-section {
            width: 100%;
            position: relative;
        }

        .header-text {
            text-align: center;
        }

        .qr-code {
            /* margin-left: 485px; */
        }

        .title-section {
            width: 100%;
            text-align: center;
        }

        .info-section {
            width: 100%;
        }

        .item-section th {
            border: 1px solid;
        }

        table {
            width: 100%;
            border-collapse: collapse !important;
        }

        .item-section td {
            border: 0.5px solid;
        }

        .item-section {
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
        }

        h3 {
            font-weight: normal;
            letter-spacing: 2px;
            margin: 5px 5px 5px 5px;
            font-size: 22px;
        }

        h5 {
            font-weight: normal;
            letter-spacing: 1px;
            margin: -5px 5px 5px 5px;
            font-size: 12px;
        }

        .left-width {
            width: 130px;
        }

        .right-width {
            width: 100px;
            text-transform: uppercase;
        }

        .mid-width {
            width: 370px;
            text-transform: uppercase;
        }

        .underline {
            border-bottom: 1px black solid;
        }


        .item-td1 {
            width: 300px;
        }

        .item-td2 {
            width: 100px;
        }

        .item-td {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .spacer {
            margin-top: 1px;
            width: 100%;
            /* border-bottom: 2px solid; */
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
            position: fixed;
            top: 200;
            left: 200;
            color: rgb(234, 223, 223);
            transform: rotate(330deg);
            text-transform: uppercase;
            font-size: 32px;
            z-index: -10;
        }

        .item-border-bottom {
            border-left: none !important;
            border-right: none !important;
            border-top: none !important;
            border-bottom: 1px solid black;
        }

        @page {
            margin: 20px 20px 30px 20px !important;
            width: 100%;
        }

        .item-td:nth-child(2) {
            text-align: left;
        }

        .item-section th {
            text-transform: uppercase !important;
            font-size: 10px !important;
            font-family: 'DejaVu Sans', sans-serif;
        }

        td {
            font-size: 12px !important;
        }

        .item-section td {
            text-transform: uppercase !important;
            font-size: 10px !important;
            padding: 4px;
        }

        .border-bottom-none {
            border-bottom: none !important;
        }

        .border-top-none {
            border-top: none !important;
        }

        .border-none {
            border: none !important;
        }

        .border-bottom {
            border-bottom: 1px dotted !important;
        }

        .double-underline {
            text-decoration: underline;
            text-decoration-style: double;
        }
    </style>
</head>

<body>
    <div class="header-section">
        <table style="width: 100% !important;margin-bottom:20px;">
            <tr>
                <td colspan="6" style="width:100% !important;">
                    <div class="header-text">
                        <h3>{{$pdf_data['branch']['name']}}</h3>
                        <h5>{{$pdf_data['branch']['address']}}</h5>
                        <h5>TIN {{$pdf_data['branch']['TIN']}}</h5>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%">Purchase Subsidiary Ledger</td>
                <td width="25%">:{{$pdf_data['Type']}}</td>
                <td width="30%"></td>
                <td class="text-right">Run Date</td>
                <td>: {{date("m-d-Y")}}</td>
            </tr>
            <tr>
                <td>Source Location</td>
                <td width="20%">:{{$pdf_data['warehouse']}}</td>
                <td></td>
                <td class="text-right">Run Time</td>
                <td>: {{date("h:i s A")}}</td>
            </tr>
            <tr>
                <td>Report Period</td>
                <td>:{{$pdf_data['dateFrom']}} to {{$pdf_data['dateTo']}}</td>
                <td></td>
                <td class="text-right"></td>
                <td></td>
            </tr>
        </table>
    </div>
    <table class="item-section">
        <tbody>
            @php
                $grandTotal = 0; // Initialize grand total
                $supplierTypeTotals = []; // Initialize an array to hold totals for each supplier type
            @endphp

            @foreach($pdf_data['groupedSuppliers'] as $group)
                <tr style="border: none;">
                    <td colspan="10" style="border: none; font-size: 15px !important;">
                        <div class="double-underline">{{ $group['supplierType'] }}</div>
                    </td>
                </tr>
                <thead>
                    <tr style="border: none;">
                        <th class="border-none text-left border-bottom" width="40">Code</th>
                        <th class="border-none text-left border-bottom">Item Description</th>
                        <th class="border-none text-left border-bottom">PO #</th>
                        <th class="border-none text-left border-bottom">RR #</th>
                        <th class="border-none text-left border-bottom">INV #</th>
                        <th class="border-none text-left border-bottom">DATE</th>
                        <th class="border-none text-left border-bottom">PACKING</th>
                        <th class="border-none text-left border-bottom">QUANTITY</th>
                        <th class="border-none text-left border-bottom">LIST COST</th>
                        <th class="border-none text-left border-bottom">NETCOST</th>
                    </tr>
                </thead>

                @php
                    $supplierTypeSubtotal = 0; // Initialize subtotal for supplier type
                @endphp

                @foreach ($group['suppliers'] as $supplier)
                    @php
                        $supplierSubtotal = 0; // Initialize supplier subtotal
                    @endphp

                    <tr style="border: none;">
                        <td class="border-none text-left border-bottom " style="font-weight: bold;" colspan="10">{{ $supplier['supplierName'] }}</td>
                    </tr>

                    @foreach($supplier['items'] as $row)
                        <tr>
                            <td class="border-none">{{ $row->code }}</td>
                            <td class="border-none">{{ $row->itemname }}  {{ $row->ismedicine ? $row->Description : '' }}</td>
                            <td class="border-none">{{ $row->ponumber }}</td>
                            <td class="border-none">{{ $row->RRNumber }}</td>
                            <td class="border-none">{{ $row->InvoiceNo }}</td>
                            <td class="border-none">{{ date('m/d/Y', strtotime($row->InvoiceDate)) }}</td>
                            <td class="border-none">1</td>
                            <td class="border-none">{{ floatval($row->qty) }}</td>
                            <td class="border-none">{{ number_format($row->price, 2) }}</td>
                            <td class="border-none">{{ number_format($row->NetCost, 2) }}</td>
                        </tr>
                        
                        @php
                            $supplierSubtotal += $row->NetCost; // Accumulate the supplier subtotal
                            $supplierTypeSubtotal += $row->NetCost; // Accumulate the supplier type subtotal
                        @endphp
                    @endforeach
                    <tr>
                        <td colspan="10" class="border-none border-bottom"></td>
                    </tr>
                    <tr>
                        <td class="border-none" colspan="7" style="text-align: right;">{{ $supplier['supplierName'] }}</td>
                        <td class="border-none" style="text-align: right; font-weight: bold;">Subtotal:</td>
                        <td class="border-none">{{ number_format($supplierSubtotal, 2) }}</td>
                        <td class="border-none"></td>
                    </tr>

                    @php
                        $grandTotal += $supplierSubtotal; // Accumulate the grand total
                    @endphp

                    <tr>
                        <td class="border-none" colspan="8"></td>
                        <td class="border-none"></td>
                        <td class="border-none"></td>
                    </tr>
                @endforeach

                @php
                    // Store the subtotal for this supplier type
                    $supplierTypeTotals[$group['supplierType']] = $supplierTypeSubtotal;
                @endphp

            @endforeach

            <tr>
                <td class="border-none" colspan="8" style="text-align: right; font-weight: bold;">Grand Total:</td>
                <td class="border-none">{{ number_format($grandTotal, 2) }}</td>
                <td class="border-none"></td>
            </tr>
        </tbody>
    </table>
    <div style="width: 30%;">
    <table >
        <tr>
            <th colspan="2" class="text-left border-bottom">Summary</th>
        </tr>
        @foreach($supplierTypeTotals as $type => $total)
            <tr>
                <td>{{ $type }}</td>
                <td>{{ number_format($total, 2) }}</td>
            </tr>
        @endforeach
        <tr>
            <td style="font-weight: bold;" class="text-right">Grand Total:</td>
            <td style="font-weight: bold;">{{ number_format($grandTotal, 2) }}</td>
        </tr>
    </table>
    </div>
   

    <!-- <p class="csstransforms">THIS DOCUMENT IS NOT VALID <br/> FOR CLAIM OF INPUT TAXES</p> -->
    <div class="spacer"></div>

    <div class="title-section">
    </div>
    <table class="signatory-section1">
        <tbody>

        </tbody>
    </table>

</body>

</html>