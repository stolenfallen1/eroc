<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Purchase Order</title>
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
            width: 70px;
          }
          .mid-width{
            width: 370px;
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
          .item-td-total{
            text-align: right;
            padding-right: 25px; 
            font-weight: 800;
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
            float: left;
          }
          .signatory-section2{
            float: right;
          }
          .comptroller{
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

        </style>
    </head>
    <body>
      <div class="header-section">
        <img src="{{ $pdf_data['logo'] }}" alt="Example Image" width="100" height="100">
        <div class="header-text">
          <h3>{{$pdf_data['purchase_order']?$pdf_data['purchase_order']['branch']?$pdf_data['purchase_order']['branch']['name']:'':''}}</h3>
          <h5 style="margin: -20px !important;">OSMEÑA BLVD, CEBU CITY, 6000 CEBU</h5>
          <h5>TIN 000-309-308-000</h5>
        </div>
        <img class="qr-code" src="{{ $pdf_data['qr'] }}" alt="Example Image" width="100" height="100">
      </div>
      <div class="title-section">
        <h3>PURCHASE ORDER</h3>
      </div>
      <table class="info-section">
        <tbody>
          <tr>
            <td class="left-width">Supplier Name</td>
            <td class="mid-width underline">{{$pdf_data['purchase_order']['vendor']?$pdf_data['purchase_order']['vendor']['vendor_Name']:''}}</td>
            <td class="right-width">Date</td>
            <td class="underline">{{$pdf_data['transaction_date']}}</td>
          </tr>
          <tr>
            <td class="left-width">Contact Person</td>
            <td class="mid-width underline">{{$pdf_data['purchase_order']['vendor']['vendor_ContactPerson']??''}}</td>
            <td class="right-width">Invoice No.</td>
            <td class="underline"></td>
          </tr>
          <tr>
            <td class="left-width">Address</td>
            <td class="mid-width underline">{{$pdf_data['purchase_order']['vendor']['vendor_Address']??''}}</td>
            <td class="right-width">PO. No</td>
            <td class="underline">{{$pdf_data['purchase_order']['code']??''}}</td>
          </tr>
          <tr>
            <td class="left-width">Tel No.</td>
            <td class="mid-width underline">{{$pdf_data['purchase_order']['vendor']['vendor_TelNo']??''}}</td>
            <td class="right-width">Via</td>
            <td class="underline">{{$pdf_data['purchase_order']['purchaseRequest']['code']??''}}</td>
          </tr>
          <tr>
            <td class="left-width">Terms</td>
            <td class="mid-width underline">{{$pdf_data['purchase_order']['vendor']['term']['description']??''}}</td>
          </tr>
          <tr>
            <td class="left-width">Remarks</td>
            <td class="mid-width underline">{{$pdf_data['purchase_order']['purchaseRequest']['pr_Justication']??''}}</td>
          </tr>
        </tbody>
      </table>
      <table class="item-section">
        <thead>
          <th>Code</th>
          <th>Item Description</th>
          <th>Qty</th>
          <th>Unit</th>
          <th>Unit Cost</th>
          <th>Discount</th>
          <th>Tax</th>
          <th>Amount</th>
        </thead>
        <tbody>
          @foreach ($pdf_data['purchase_order']['details'] as $detail)
              <tr>
                <td class="item-td" >{{ $detail['item']['id'] ?? '' }}</td>
                <td class="item-td" >{{ $detail['item']['item_name'] ?? '' }}</td>
                <td class="item-td" >{{ (float)$detail['po_Detail_item_qty'] ?? 0 }}</td>
                <td class="item-td" >{{ $detail['unit']?$detail['unit']['name']:'...' }}</td>
                <td class="item-td" >{{ number_format($detail['purchaseRequestDetail']['recommendedCanvas']['canvas_item_amount'] ?? 0, 2) }}</td>
                <td class="item-td" >{{ number_format($detail['purchaseRequestDetail']['recommendedCanvas']['canvas_item_discount_amount'] ?? 0, 2) }}</td>
                <td class="item-td" >{{ number_format($detail['purchaseRequestDetail']['recommendedCanvas']['canvas_item_vat_amount'] ?? 0, 2) }}</td>
                <td class="item-td" >{{ number_format($detail['purchaseRequestDetail']['recommendedCanvas']['canvas_item_net_amount'] ?? 0, 2) }}</td>
              </tr>
          @endforeach
              <tr>
                <td colspan="7" class="item-td-total" >Total amount</td>
                <td class="item-td" >{{ number_format($pdf_data['total_amount'] ?? 0, 2) }}</td>
              </tr>
        </tbody>
      </table>
      <p class="csstransforms">THIS DOCUMENT IS NOT VALID <br/> FOR CLAIM OF INPUT TAXES</p>
      <div class="spacer"></div>
      <p class="note">Please enter out order subject to following conditions. Purchase Order number must appear on all invoices. When price are not stated, order must not be filled at
        a price higher than charged on last purchase without notifying the hospital.
      </p>
      <div class="title-section">
        <h5 class="reminder">** This Form is Electronically Signed Out **</h>
      </div>
      <table class="signatory-section1">
        <tbody>
          <tr><td class="underline item-td">{{$pdf_data['purchase_order']['administrator']['name']}}</td></tr>
          <tr><td class="item-td">Administrator</td></tr>
          @if ($pdf_data['purchase_order']['comptroller'] != null)
          <tr><td class=" comptroller underline item-td">{{$pdf_data['purchase_order']['comptroller']['name']}}</td></tr>
          @endif
          <tr><td class="item-td">Purchasing Comptroller</td></tr>
          @if ($pdf_data['purchase_order']['corporateAdmin'] != null)
            <tr><td class=" comptroller underline item-td">{{$pdf_data['purchase_order']['corporateAdmin']['name']}}</td></tr>
            <tr><td class="item-td">Corporate admin</td></tr>
          @endif
          @if ($pdf_data['purchase_order']['president'] != null)
            <tr><td class=" comptroller underline item-td">{{$pdf_data['purchase_order']['president']['name']}}</td></tr>
            <tr><td class="item-td">President</td></tr>
          @endif
        </tbody>
      </table>
      <table class="signatory-section2">
        <tbody>
          <tr><td>ORDERED BY :</td></tr>
          <tr><td style="padding-top:10px; text-transform: uppercase;" class="item-td underline">{{$pdf_data['canvaser']}}</td></tr>
          <tr><td> ( ) Central Supply </td></tr>
          <tr><td> ( ) Pharmacy </td></tr>
          <tr><td> ( ) Others Engineering Department </td></tr>
        </tbody>
      </table>
    </body>
</html>
