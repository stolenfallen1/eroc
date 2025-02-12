<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Purchase Request</title>
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
            width: 70px;
            text-transform: uppercase;
          }
          .right-width{
            width: 110px;
            text-align: right;
            text-transform: uppercase;
          }
          .mid-width{
            text-transform: uppercase;
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
            margin-top: 2px;
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
            float: left;
          }
          .signatory-section2{
            float: right;
          }
          .comptroller{
            padding-top: 25px !important;
          }
          .item-td:nth-child(3){
            text-align: left;
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
          @page {
            margin: 20px 20px 20px 20px !important;
            width: 100%;
          }
        </style>
    </head>
    <body>
      <div class="header-section">
        <img src="{{ $pdf_data['logo'] }}" alt="Example Image" width="100" height="100">
        <div class="header-text">
          <h3>{{$pdf_data['purchase_request']['branch']['name']}}</h3>
          <h5 style="margin: -20px !important;">{{$pdf_data['purchase_request']['branch']['address']}}</h5>
          <h5>TIN {{$pdf_data['purchase_request']['branch']['TIN']}}</h5>
        </div>
      </div>
      <div class="title-section">
        <h3>PURCHASE REQUESTS</h3>
      </div>
      <table class="info-section">
        <tbody>
          <tr>
            <td class="left-width">PR No.</td>
            <td class="mid-width underline">:{{$pdf_data['purchase_request']['code']}}</td>
            <td class="right-width">Requested by</td>
            <td class="underline">:{{ucwords($pdf_data['purchase_request']['user']['name'])}}</td>
          </tr>
          <tr>
            <td class="left-width">Inv Group</td>
            <td class="mid-width underline">:{{$pdf_data['purchase_request']['itemGroup']['name']}}</td>
            <td class="right-width">Department</td>
            <td class="underline">:{{$pdf_data['purchase_request']['warehouse']['warehouse_description']}}</td>
          </tr>
          <tr>
            <td class="left-width">Category</td>
            <td class="mid-width underline">:{{$pdf_data['purchase_request']['category']['name']}}</td>
            <td class="right-width">Date Requested</td>
            <td class="underline">:{{$pdf_data['requested_date']}}</td>
          </tr>
          <tr>
            <td class="left-width"></td>
            <td class="mid-width"></td>
            <td class="right-width">Date Required</td>
            <td class="underline">:{{$pdf_data['Required_date']}}</td>
          </tr>
          <tr>
            <td class="left-width">Remarks</td>
            <td class="mid-width underline">:{{$pdf_data['purchase_request']['pr_Justication']}}</td>
          </tr>
        </tbody>
      </table>
      <table class="item-section">
        <thead>
          <th>#</th>
          <th>Code</th>
          <th>Item Name</th>
          <th> Description</th>
          <th>Qty</th>
          <th>Unit</th>
          <th>Unit Cost</th>
          <th>Amount</th>
        </thead>
        <tbody>
          @php $counter = 1 @endphp
          @foreach ($pdf_data['purchase_request']['purchaseRequestDetails'] as $detail)
              <tr>
                <td class="item-td" >{{$counter++ }}</td>
                <td class="item-td" >{{ $detail['itemMaster']['id'] }}</td>
                <td class="item-td" >{{ $detail['itemMaster']['item_name'] }}</td>
                <td class="item-td" >{{ $pdf_data['purchase_request']['ismedicine'] ? $detail['itemMaster']['item_Description'] : '' }}</td>
                <td class="item-td" >{{ (int)$detail['item_Branch_Level1_Approved_Qty'] }}</td>
                <td class="item-td" >{{ $detail['unit']['name']??$detail['unit2']['name'] }}</td>
                <td class="item-td" >{{ number_format($detail['item_ListCost'],2) }}</td>
                <td class="item-td" >{{ number_format(($detail['item_ListCost'] * $detail['item_Branch_Level1_Approved_Qty']),2) }}</td>
              </tr>
          @endforeach
        </tbody>
      </table>
      <div class="spacer"></div>
      <div class="title-section">
        <h5 class="reminder">** This Form is Electronically Signed Out **</h>
      </div>
      <table class="signatory-section1">
        <tbody>
          <tr><td class="underline item-td">{{ $pdf_data['purchase_request']['ismedicine'] ?  ucwords($pdf_data['purchase_request']['consultantApprovedBy']['name']) :  ucwords($pdf_data['purchase_request']['administrator']['name']) }}</td></tr>
          <tr><td class="item-td">{{ $pdf_data['purchase_request']['ismedicine'] ? 'Consultant':'Administrator'}}</td></tr>
        </tbody>
      </table>
    </body>
</html>
