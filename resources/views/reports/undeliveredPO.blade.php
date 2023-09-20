<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Delivery</title>
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
      {{-- <div class="header-section">
        <img src="{{ $pdf_data['logo'] }}" alt="Example Image" width="100" height="100">
        <div class="header-text">
          <h3>{{$pdf_data['delivery']['branch']['name']}}</h3>
          <h5 style="margin: -20px !important;">OSMEÑA BLVD, CEBU CITY, 6000 CEBU</h5>
          <h5>TIN 000-309-308-000</h5>
        </div>
        <img class="qr-code" src="{{ $pdf_data['qr'] }}" alt="Example Image" width="100" height="100">
      </div> --}}
      {{-- <div class="title-section">
        <h3>RECEIVING REPORT</h3>
      </div> --}}
      <table class="info-section">
        <tbody>
          <tr>
            <td class="left-width">
              <h3>{{$pdf_data['branch_name']}}</h3>
            </td>
            <tr>
              <td class="left-width">
                <h3>
                  Undelivered Purchase Order <br> {{$pdf_data['warehouse_name']}}
                </h3>
              </td>
              {{-- <td class="mid-width underline">{{$pdf_data['delivery']['vendor']['vendor_ContactPerson']}}</td>
              <td class="right-width">PR No.</td>
              <td class="underline">{{$pdf_data['delivery']['purchaseOrder']['purchaseRequest']['code']}}</td> --}}
            </tr>
            {{-- <td class="mid-width underline">{{$pdf_data['delivery']['vendor']['vendor_Name']}}</td> --}}
            {{-- <td class="right-width">Date Received</td>
            <td class="underline">{{$pdf_data['transaction_date']}}</td> --}}
          {{-- </tr>
          <tr>
            <td class="left-width">Address</td>
            <td class="mid-width underline">{{$pdf_data['delivery']['vendor']['vendor_ContactPerson']}}</td>
            <td class="right-width">PR No.</td>
            <td class="underline">{{$pdf_data['delivery']['purchaseOrder']['purchaseRequest']['code']}}</td>
          </tr>
          <tr>
            <td class="left-width">RR No.</td>
            <td class="mid-width underline">{{$pdf_data['delivery']['code']}}</td>
            <td class="right-width">Ref. PO No.</td>
            <td class="underline">{{$pdf_data['delivery']['po_number']}}</td>
          </tr>
          <tr>
            <td class="left-width">Tel No.</td>
            <td class="mid-width underline">{{$pdf_data['delivery']['vendor']['vendor_TelNo']}}</td>
            <td class="right-width">Date of PO.</td>
            <td class="underline">{{$pdf_data['po_date']}}</td>
          </tr> --}}
        </tbody>
      </table>
      <table class="item-section">
        <thead>
          <th>PO#</th>
          <th>Pr date</th>
          <th>Item name</th>
          <th>Qty</th>
          <th>List Cost</th>
          <th>Vendor</th>
        </thead>
        <tbody>
          @foreach ($pdf_data['items'] as $detail)
              <tr>
                <td class="item-td" >{{ $detail['purchaseOrder']['code'] }}</td>
                <td class="item-td" >{{ date_format(date_create($detail['purchaseOrder']['purchaseRequest']['pr_Transaction_Date']), "Y/m/d H:i:s") }}</td>
                <td class="item-td" >{{ $detail['item']['item_name'] }}</td>
                <td class="item-td" >{{ (int)$detail['po_Detail_item_qty'] }}</td>
                <td class="item-td" >{{ $detail['po_Detail_item_listcost'] }}</td>
                <td class="item-td" >{{ $detail['purchaseRequestDetail']['recommendedCanvas']['vendor']['vendor_Name'] }}</td>
              </tr>
          @endforeach
          {{-- <tr>
            <td colspan="5"></td>
            <td class="item-td">Total Amount</td>
            <td class="item-td">{{number_format($pdf_data['delivery']['rr_Document_TotalNetAmount'], 4)}}</td>
          </tr> --}}
        </tbody>
      </table>
      <p class="csstransforms">THIS DOCUMENT IS NOT VALID <br/> FOR CLAIM OF INPUT TAXES</p>
      <div class="spacer"></div>
      <div class="title-section">
      </div>
      <table class="signatory-section1">
        <tbody>
          {{-- <tr><td class="underline item-td">{{$pdf_data['delivery']['receiver']['name']}}</td></tr> --}}
          {{-- <tr><td class="item-td">Received by</td></tr> --}}
        </tbody>
      </table>
    </body>
</html>
