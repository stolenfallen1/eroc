<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Stock Transfer Transaction</title>
        <style>

          .header-section{
            width: 100%;
            position: relative;
          }

          .header-text{
            position: absolute;
            text-align: center;
            margin-top: -20px;
            width: 69%;

          }

          .qr-code{
            margin-left: 485px;
          }

          .title-section{
            width: 100%;
            text-align: center;
          }

          .info-section{
            width: 100%;
          }
          
          .item-section td, th{
            border: 1px solid;
          }

          .item-section{
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
          }

          h3{
            font-weight: normal;
            letter-spacing: 2px;
          }
          h5{
            font-weight: normal;
            letter-spacing: 1px;
          }

          .left-width{
            width: 100px;
          }
          .right-width{
            width: 80px;
          }
          .mid-width{
            width: 320px;
          }

          .underline{
            border-bottom: 1px black solid;
          }

          td{
            font-size: 12px;
          }

          th{
            font-size: 12px;
          }

          .item-td{
            text-align: center;
          }

          .spacer{
            margin-top: 1px;
            width: 100%;
            border-bottom: 2px solid;
          }

          .note{
            font-style: italic;
            font-size: 12px;
          }

          .reminder{
            font-weight: 300;
          }
          .signatory-section1{
            margin-top: 20px;
            margin-left: 10px;
            float: left;
          }
          .signatory-section2{
            margin-top: 20px;
            margin-right: 10px;
            float: right;
          }
          .comptroller{
            padding-top: 25px !important;
          }
          .text-right{
            text-align: right;
            text-transform: uppercase;
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

        </style>
    </head>
    <body>
      <div class="header-section">
        <img src="{{ $pdf_data['logo'] }}" alt="Example Image" width="100" height="100">
        <div class="header-text">
          <h3>{{$pdf_data['stock_transfer']['branch']['name']}}</h3>
          <h5 style="margin: -20px !important;">{{$pdf_data['stock_transfer']['branch']['address']}}</h5>
          <h5>TIN {{$pdf_data['stock_transfer']['branch']['TIN']}}</h5>
        </div>
        <img class="qr-code" src="{{ $pdf_data['qr'] }}" alt="Example Image" width="100" height="100">
      </div>
      <div class="title-section">
        <h3>STOCK TRANSFER REPORT</h3>
      </div>
      <table class="info-section">
        <tbody>
          <tr>
            <td class="left-width">Document No</td>
            <td class="mid-width underline">{{$pdf_data['stock_transfer']['document_number']}}</td>
            <td class="right-width">Print date</td>
            <td class="underline">{{date('Y-m-d H:i:s A')}}</td>
          </tr>
          <tr>
            <td class="left-width">Source Location</td>
            <td class="mid-width underline">{{$pdf_data['stock_transfer']['warehouseSender']['warehouse_description']}}</td>
            <td class="right-width">Transfer Date</td>
            <td class="underline">{{date('Y-m-d H:i:s A',strtotime($pdf_data['stock_transfer']['transfer_date']))}}</td>
          </tr>
          <tr>
            <td class="left-width">Target Location</td>
            <td class="mid-width underline">{{$pdf_data['stock_transfer']['warehouseReceiver']['warehouse_description']}}</td>
            <td class="right-width">Received Date</td>
            <td class="underline">{{$pdf_data['stock_transfer']['received_date'] ? date('Y-m-d H:i:s A',strtotime($pdf_data['stock_transfer']['received_date'] )) : ''}}</td>
          </tr>
         
        </tbody>
      </table>
      <table class="item-section">
        <thead>
          <tr>
            <th rowspan="2">Code</th>
            <th rowspan="2">Item Description</th>
            <th colspan="4">Transfer</th>
            <th colspan="4">Received</th>
          </tr>
          
          <tr>
            <th>Qty</th>
            <th>Unit</th>
            <th>Unit Cost</th>
            <th>Amount</th>
            <th>Qty</th>
            <th>Unit</th>
            <th>Unit Cost</th>
            <th>Amount</th>
          </tr>
        </thead>
        <tbody>
          @php 
           $total = 0;
           $receivedtotal = 0;
          @endphp
          @foreach ($pdf_data['stock_transfer']['stockTransferDetails'] as $detail)
              @php 
                $total += (float)$detail['transfer_item_total_cost'];
                $receivedtotal += (float)$detail['received_item_total_cost'];
              @endphp
              <tr>
                <td class="item-td" >{{ $detail['transfer_item_id'] }}</td>
                <td class="item-td" >{{ $detail['itemdetails']['itemMaster']['item_name'] }}</td>
                <td class="item-td" >{{ (float)$detail['transfer_item_qty'] }}</td>
                <td class="item-td" >{{ $detail['itemdetails']['itemMaster']['unit']['name'] }}</td>
                <td class="item-td" >{{ number_format($detail['transfer_item_unit_cost'],2) }}</td>
                <td class="item-td" >{{ number_format($detail['transfer_item_total_cost'],2) }}</td>
                <td class="item-td" >{{ (float)$detail['received_item_qty'] }}</td>
                <td class="item-td" >{{ $detail['received_item_qty'] ? $detail['itemdetails']['itemMaster']['unit']['name'] :'' }}</td>
                <td class="item-td" >{{ number_format($detail['received_item_unit_cost'],2) }}</td>
                <td class="item-td" >{{ number_format($detail['received_item_total_cost'],2) }}</td>
              </tr>
          @endforeach
          <tr>
            <td colspan="5" class="text-right">Total Amount</td>
            <td class="item-td">{{number_format($total, 2)}}</td>
            <td colspan="3"></td>
            <td class="item-td">{{number_format($receivedtotal, 2)}}</td>
          </tr>
        </tbody>
      </table>
      <p class="csstransforms">THIS DOCUMENT IS NOT VALID <br/> FOR CLAIM OF INPUT TAXES</p>
      <div class="spacer"></div>
      {{-- <p class="note">Please enter out order subject to following conditions. Purchase Order number must appear on all invoices. When price are not stated, order must not be filled at
        a price higher than charged on last purchase without notifying the hospital.
      </p> --}}
      <div class="title-section">
        {{-- <h5 class="reminder">** This Form is Electronically Signed Out **</h> --}}
      </div>
      <table class="signatory-section1">
        <tbody>
          <tr><td>TRANSFER BY :</td></tr>
          <tr><td style="padding-top:10px; text-transform: uppercase;" class="item-td underline">{{$pdf_data['stock_transfer']['tranferBy']['name']}}</td></tr>
          {{-- <tr><td class="underline item-td">{{$pdf_data['stock_transfer']['tranferBy']['name']}}</td></tr>
          <tr><td class="item-td">Transfer by</td></tr> --}}
          {{-- <tr><td class=" comptroller underline item-td">{{$pdf_data['delivery']['comptroller']['name']}}</td></tr> --}}
          {{-- <tr><td class="item-td">Purchasing Comptroller</td></tr> --}}
        </tbody>
      </table>
      <table class="signatory-section2">
        <tbody>
          <tr><td class="margin-bottom:10px;">RECEIVED BY :</td></tr>
          <tr><td style="padding-top:10px; text-transform: uppercase;" class="item-td underline">{{$pdf_data['stock_transfer']['receivedBy'] ? $pdf_data['stock_transfer']['receivedBy']['name'] : ''}}</td></tr>
          {{-- <tr><td> ( ) Central Supply </td></tr>
          <tr><td> ( ) Pharmacy </td></tr>
          <tr><td> ( ) Others Engineering Department </td></tr> --}}
        </tbody>
      </table>
    </body>
</html>
