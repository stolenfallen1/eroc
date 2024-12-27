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

        td:nth-child(3) {
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

        .text-right {
            text-align: right !important;
            font-weight: bold;
            font-size: 13px;
        }

        .text-left {
            text-align: left !important;
        }
        .text-right-column {
            text-align: right !important;
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
        <table class="item-section">
            <thead>
                <tr>
                    <th style="width: 150px;" class="border-none text-right-column">Vendor Name</th>
                    <th style="width: 150px;" class="border-none text-left" colspan="6">: {{$pdf_data['data']['vendor']['vendor_Name']}}</th>
                    <th style="width: 130px;" class="border-none text-right-column">Returned Document No.</th>
                    <th style="width: 150px;" class="border-none text-left">: {{$pdf_data['data']['returned_document_number']}}</th>
                    <th style="width: 130px;" class="border-none text-right-column">Branch </th>
                    <th style="width: 150px;" class="border-none text-left">:{{$pdf_data['data']['branch']['abbreviation']}} </th>
                </tr>
                <tr>
                    <th style="width: 150px;" class="border-none text-right-column">Vendor Address</th>
                    <th style="width: 150px;" class="border-none text-left" colspan="6">: {{$pdf_data['data']['vendor']['vendor_Address'] ? $pdf_data['data']['vendor']['vendor_Address'] : 'N/A'}}</th>
                    <th style="width: 130px;" class="border-none text-right-column">Returned By</th>
                    <th style="width: 150px;" class="border-none text-left">: {{ucwords($pdf_data['data']['user']['name'])}}</th>
                    <th style="width: 130px;" class="border-none text-right-column">RR Document No.</th>
                    <th style="width: 150px;" class="border-none text-left">: {{$pdf_data['data']['returned_document_number']}}</th>
                </tr>
                <tr>
                    <th style="width: 150px;" class="border-none text-right-column">Vendor Contact</th>
                    <th style="width: 150px;" class="border-none text-left" colspan="6">: {{$pdf_data['data']['vendor']['vendor_TelNo'] ? $pdf_data['data']['vendor']['vendor_TelNo'] : 'N/A'}}</th>
                    <th style="width: 130px;" class="border-none text-right-column">Returned Date </th>
                    <th style="width: 150px;" class="border-none text-left">: {{date('m-d-Y h:i:s A',strtotime($pdf_data['data']['returned_date']))}}</th>
                    <th style="width: 130px;" class="border-none text-right-column">PO Document No.</th>
                    <th style="width: 150px;" class="border-none text-left">: {{$pdf_data['data']['po_document_number']}}</th>
                </tr>
                <tr>
                    <th style="width: 150px;" class="border-none text-right-column">Remarks</th>
                    <th style="width: 150px;" class="border-none text-left" colspan="8">: {{$pdf_data['data']['remarks']}}</th>
                    <th style="width: 130px;" class="border-none text-right-column">Invoice No.</th>
                    <th style="width: 150px;" class="border-none text-left">: {{$pdf_data['data']['rr_Document_Invoice_No']}}</th>
                </tr>
                
            </thead>
        </table>
        <br>
        @php
        $grandTotalVat =0;
        $grandTotalDiscount =0;
        $grandTotalGross =0;
        $grandTotalNet =0;
        @endphp
        <table class="item-section">
            <thead>
                <th style="width: 50px;">Code</th>
                <th style="width: 150px;">Item Name</th>
                <th style="width: 60px;">Unit</th>
                <th style="width: 60px;">Qty</th>
                <th style="width: 60px;">Unit Price</th>
                <th style="width: 60px;">Batch</th>
                <th style="width: 60px;">VAT</th>
                <th style="width: 60px;">Discount</th>
                <th style="width: 70px;">Gross Amount</th>
                <th style="width: 60px;">Net Amount</th>
            </thead>
            <tbody>
                @foreach ($pdf_data['data']['items'] as $row)
                @php
                $grandTotalVat += $row['returned_item_vat_amount'];
                $grandTotalDiscount += $row['returned_item_discount'];
                $grandTotalGross += $row['returned_item_total_gross'];
                $grandTotalNet += $row['returned_item_total_net_amount'];
                @endphp
                <tr>
                    <td>{{$row['returned_item_id']}}</td>
                    <td>{{$row['details']['item_name'] }} {{$row['details']['item_Description']}}</td>
                    <td>{{$row['unit']['name']}}</td>
                    <td>{{$row['returned_item_qty']}}</td>
                    <td>{{number_format($row['returned_item_price'],2)}}</td>
                    <td>{{$row['batch']? $row['batch']['batch_Number'] : ''}}</td>
                    <td>{{number_format($row['returned_item_vat_amount'],2)}}</td>
                    <td>{{number_format($row['returned_item_discount'],2)}}</td>
                    <td>{{number_format($row['returned_item_total_gross'],2)}}</td>
                    <td>{{number_format($row['returned_item_total_net_amount'],2)}}</td>
                </tr>
                @endforeach
                <!-- <tr class="border-none">
                    <td colspan="11" class="border-none"><br></td>
                </tr>
                <tr>
                    <td class="border-none" colspan="7"></td>
                    <td class="border-none text-right" colspan="2">Total VAT: </td>
                    <td class="border-none text-right" colspan="2">{{number_format($grandTotalVat,2)}}</td>
                </tr>
                <tr>
                    <td class="border-none" colspan="7"></td>
                    <td class="border-none  text-right" colspan="2">Total Discount: </td>
                    <td class="border-none text-right" colspan="2">{{number_format($grandTotalDiscount,2)}}</td>
                </tr>
                <tr>
                    <td class="border-none" colspan="7"></td>
                    <td class="border-none text-right" colspan="2">Gross Amount : </td>
                    <td class="border-none text-right" colspan="2">{{number_format($grandTotalGross,2)}}</td>
                </tr> -->
                <tr>
                    <td class="border-none" colspan="6"></td>
                    <td class="border-none text-right" colspan="3">TOTAL AMOUNT: </td>
                    <td class="border-none text-center" >{{number_format($grandTotalNet,2)}}</td>
                </tr>
                <tr class="border-none">
                    <td colspan="10" class="border-none"><br></td>
                </tr>
            </tbody>
        </table>
        <!-- <table class="item-section">
            <tr class="border-none">
                <td class="border-none" style="width: 100px;font-weight:bold;">Returned Date</td>
                <td class="border-none" style="font-weight:bold;">: {{date('m-d-Y H:i:s A')}}</td>
            </tr>
            <tr class="border-none">
                <td class="border-none" style="width: 100px;font-weight:bold;">Print Date</td>
                <td class="border-none" style="font-weight:bold;">: {{date('m-d-Y H:i:s A')}}</td>
            </tr>
        </table> -->
    </div>
    <div>

    </div>
</body>

</html>