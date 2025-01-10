<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Consignment Delivery Report</title>
        <style>

          .header-section{
            width: 100%;
            position: relative;
          }

          .header-text{
            text-align: center;
          }
          
          .qr-code{
            /* margin-left: 485px; */
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
            margin:5px 5px 5px 5px;
            font-size: 22px;
          }
          h5{
            font-weight: normal;
            letter-spacing: 1px;
            margin:-5px 5px 5px 5px;
            font-size: 12px;
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
          .item-td1{
            width: 300px;
          }
          .item-td2{
            width: 100px;
          }
          th{
            font-size: 12px;
          }

          .item-td{
            text-align: center;
          }
          .text-right{
            text-align: right;
          }
          .text-left{
            text-align: left;
          }
          .text-center{
            text-align: center;
          }
          .spacer{
            margin-top: 1px;
            width: 100%;
            /* border-bottom: 2px solid; */
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
            position: fixed;
            top: 200;
            left: 200;
            color: rgb(234, 223, 223);
            transform: rotate(330deg);
            text-transform: uppercase;
            font-size: 32px;
            z-index: -10;
          }

          .item-border-bottom{
            border-left: none !important;
            border-right: none !important;
            border-top: none !important;
            border-bottom:1px solid black;
          }
          @page {
              margin:20px 20px 20px 20px !important;
              width: 100%;
          }
         
        </style>
    </head>
    <body>
      {{-- <div class="header-section">
        <img src="{{ $pdf_data['logo'] }}" alt="Example Image" width="100" height="100">
        <div class="header-text">
          <h3>{{$pdf_data['delivery']['branch']['name']}}</h3>
          <h5 style="margin: -20px !important;">{{$pdf_data['delivery']['branch']['address']}}</h5>
          <h5>TIN {{$pdf_data['delivery']['branch']['TIN']}}</h5>
        </div>
        <img class="qr-code" src="{{ $pdf_data['qr'] }}" alt="Example Image" width="100" height="100">
      </div>
      <div class="title-section">
        <h3>{{ucwords('CONSIGNMENT DELIVERY REPORT')}}</h3>
      </div> --}}
      <div class="header-section">
        <table style="width: 100% !important">
          <tr>
            <td style="width: 10% !important" class="text-center">
              <center><img src="{{ $pdf_data['logo'] }}" alt="Example Image" width="100" height="100"></center>
            </td>
            <td style="width:80% !important;">
              <div class="header-text">
                <h3>{{$pdf_data['delivery']['branch']['name']}}</h3>
                <h5 >{{$pdf_data['delivery']['branch']['address']}}</h5>
                <h5>TIN {{$pdf_data['delivery']['branch']['TIN']}}</h5>
              </div>
            </td>
            <td style="width: 10% !important">
              <center> <img class="qr-code" src="{{ $pdf_data['qr'] }}" alt="Example Image" width="100" height="100"></center>
            </td>
          </tr>
          <tr>
            <td colspan="3" style="padding:10px;">
              <center><h3>{{ucwords('CONSIGNMENT DELIVERY REPORT')}}</h3> </center>
            </td>
          </tr>
        </table>
      </div> 
      <table class="info-section">
        <tbody>
          <tr>
            <td class="left-width text-right">DEPARTMENT :</td>
            <td class="mid-width underline">{{$pdf_data['delivery']['warehouse_description']}}</td>
            <td class="right-width  text-right"></td>
            <td class=""></td>
            <td class="right-width  text-right"></td>
            <td class=""></td>
          </tr>
          <tr>
            <td class="left-width">Supplier Name</td>
            <td class="mid-width underline">{{$pdf_data['delivery']['vendor']['vendor_Name']}}</td>
            <td class="right-width">Delivery No.</td>
            <td class="underline">{{$pdf_data['delivery']['rr_Document_Delivery_Receipt_No']}}</td>
            <td class="right-width">Delivery Date.</td>
            <td class="underline">{{date('m-d-Y',strtotime($pdf_data['delivery']['rr_Document_Delivery_Date']))}}</td>
          </tr>
          <tr>
            <td class="left-width">Address</td>
            <td class="mid-width underline">{{$pdf_data['delivery']['vendor']['vendor_ContactPerson']}}</td>
            <td class="right-width">RR No</td>
            <td class="underline">{{$pdf_data['delivery']['code']}}</td>
            <td class="right-width">RR Date.</td>
            <td class="underline">{{date('m-d-Y',strtotime($pdf_data['transaction_date']))}}</td>
            {{-- <td class="underline">{{$pdf_data['delivery']['purchaseOrder']['purchaseRequest']['code']}}</td> --}}
          </tr>
          <tr>
            <td class="left-width">Tel No.</td>
            <td class="mid-width underline">{{$pdf_data['delivery']['vendor']['vendor_TelNo']}}</td>
            <td class="right-width"> PR No.</td>
            <td class="underline">{{$pdf_data['delivery']['ConsignmentPurchaseOrder']['purchaseRequest']['code']}}</td>
            <td class="right-width"> PR Date.</td>
            <td class="underline">{{date('m-d-Y H:i:s A',strtotime($pdf_data['delivery']['ConsignmentPurchaseOrder']['purchaseOrder']['created_at']))}}</td>
            <td></td>
          </tr>
          <tr>
            <td class="left-width"></td>
            <td class="mid-width "></td>
            <td class="right-width"> PO No.</td>
            <td class="underline">{{$pdf_data['delivery']['ConsignmentPurchaseOrder']['purchaseOrder']['code']}}</td>
            <td class="right-width"> PO Date.</td>
            <td class="underline">{{date('m-d-Y H:i:s A',strtotime($pdf_data['delivery']['ConsignmentPurchaseOrder']['purchaseOrder']['created_at']))}}</td>
            {{-- <td class="underline">{{$pdf_data['po_date']}}</td> --}}
            <td ></td>
          </tr>
          <tr>
            <td class="left-width"></td>
            <td class="mid-width "></td>
            <td class="right-width"> INVOICE No.</td>
            <td class="underline">{{$pdf_data['delivery']['ConsignmentPurchaseOrder']['invoice_no']}}</td>
            <td class="right-width"> INVOICE Date.</td>
            <td class="underline">{{$pdf_data['delivery']['ConsignmentPurchaseOrder']['invoice_no'] ? date('m-d-Y',strtotime($pdf_data['delivery']['ConsignmentPurchaseOrder']['invoice_date'])) : ''}}</td>
            {{-- <td class="underline">{{$pdf_data['po_date']}}</td> --}}
            <td ></td>
          </tr>
        </tbody>
      </table>
      <table class="item-section">
        <thead>
          <th>No.</th>
          <th>Code</th>
          <th>Description</th>
          <th>QTY</th>
          <th>UOM</th>
          <th>UNIT PRICE</th>
          <th>VAT</th>
          <th>GROSS AMOUNT</th>
          <th>DISCOUNT</th>
          <th>AMOUNT DUE</th>
        </thead>
        <tbody>
          @php 
            $counter =1; 
          @endphp
          
          @foreach ($pdf_data['delivery']['ConsignmentPurchaseOrder']['items'] as $detail)
              <tr>
                <td class="item-td" >{{ $counter++ }}</td>
                <td class="item-td" >{{ $detail['itemdetails']['id'] }}</td>
                <td class="item-td1" >{{ $detail['itemdetails']['item_name'] }}</td>
                <td class="item-td" >{{ (float)$detail['item_qty'] }}</td>
                <td class="item-td" >{{ $detail['unit']['name'] }}</td>
                <td class="item-td2 text-center" >{{ number_format($detail['item_listcost'], 2) }}</td>
                <td class="item-td text-center" >{{ number_format($detail['vat_amount'], 2) }}</td>
                <td class="item-td2 text-center" >{{ number_format($detail['total_gross'], 2) }}</td>
                <td class="item-td2 text-center" >{{ number_format($detail['item_discount_amount'], 2) }}</td>
                <td class="item-td2 text-right" >{{ number_format($detail['net_amount'], 2) }} &nbsp;</td>
              </tr>
          @endforeach
          <tr>
            <td colspan="9" class="text-right" style="border:none !important;">TOTAL GROSS AMOUNT :</td>
            <td class="item-td item-border-bottom text-right">{{number_format($pdf_data['delivery']['ConsignmentPurchaseOrder']['total_gross_amount'], 2)}} &nbsp;</td>
          </tr>
          <tr>
            <td colspan="9" class="text-right" style="border:none !important;">TOTAL VAT :</td>
            <td class="item-td item-border-bottom text-right">{{number_format($pdf_data['delivery']['ConsignmentPurchaseOrder']['vat_amount'], 2)}} &nbsp;</td>
          </tr>
          <tr>
            <td colspan="9" class="text-right" style="border:none !important;">TOTAL DISCOUNT :</td>
            <td class="item-td item-border-bottom text-right" >{{number_format($pdf_data['delivery']['ConsignmentPurchaseOrder']['discount_amount'], 2)}} &nbsp;</td>
          </tr>
       
          <tr>
            <td colspan="9" class="text-right" style="border:none !important;">TOTAL AMOUNT DUE : </td>
            <td class="item-td item-border-bottom text-right">{{number_format($pdf_data['delivery']['ConsignmentPurchaseOrder']['total_net_amount'], 2)}} &nbsp;</td>
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
          <tr><td class="underline item-td">{{ucwords($pdf_data['delivery']['receiver']['name'])}}</td></tr>
          <tr><td class="item-td">Received by</td></tr>
          {{-- <tr><td class=" comptroller underline item-td">{{$pdf_data['delivery']['comptroller']['name']}}</td></tr> --}}
          {{-- <tr><td class="item-td">Purchasing Comptroller</td></tr> --}}
        </tbody>
      </table>
      {{-- <table class="signatory-section2"> --}}
        {{-- <tbody> --}}
          {{-- <tr><td>ORDERED BY :</td></tr> --}}
          {{-- <tr><td style="padding-top:10px; text-transform: uppercase;" class="item-td underline">{{$pdf_data['delivery']['purchaseRequest']['user']['name']}}</td></tr> --}}
          {{-- <tr><td> ( ) Central Supply </td></tr> --}}
          {{-- <tr><td> ( ) Pharmacy </td></tr> --}}
          {{-- <tr><td> ( ) Others Engineering Department </td></tr> --}}
        {{-- </tbody> --}}
      {{-- </table> --}}
    </body>
   
</html>
