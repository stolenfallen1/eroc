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
      /* width: 100%; */
      position: relative;
    }

    .header-text {
      text-align: center;
    }

    .qr-code {
      /* margin-left: 485px; */
    }

    .title-section {
      /* width: 100%; */
      text-align: center;
    }

    .info-section {
      /* width: 100%; */
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
      /* width: 130px; */
    }

    .right-width {
      /* width: 100px; */
      text-transform: uppercase;
    }

    .mid-width {
      width: 300px;
      text-transform: uppercase;
    }

    .underline {
      border-bottom: 1px black solid;
    }


    .item-td1 {
      /* width: 300px; */
    }

    .item-td2 {
      /* width: 100px; */
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
      /* width: 100%; */
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
      margin: 20px 20px 20px 20px !important;
      /* width: 100%; */
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
      border:none !important;
    }
    .border-bottom {
      border-bottom:1px solid !important;
    }
  </style>
</head>

<body>
  <div class="header-section">
    <table style="width: 100% !important;margin-bottom:10px;">
      <tr>
        <td style="width: 10% !important" class="text-center">
          <center><img src="{{ $pdf_data['logo'] }}" alt="Example Image" width="100" height="100"></center>
        </td>
        <td style="width:80% !important;">
          <div class="header-text">
            <h3>{{$pdf_data['delivery']['branch_name']}}</h3>
            <h5>{{$pdf_data['delivery']['branch_address']}}</h5>
            <h5>TIN {{$pdf_data['delivery']['tin']}}</h5>
          </div>
        </td>
        <td style="width: 10% !important">
          <center> <img class="qr-code" src="{{ $pdf_data['qr'] }}" alt="Example Image" width="100" height="100"></center>
        </td>
      </tr>
      <tr>
        <td colspan="3" style="padding:10px;">
          <center>
          <h3>{{ucwords('CONSIGNMENT DELIVERY REPORT')}}</h3>
          </center>
        </td>
      </tr>
    </table>
  </div>
 <table class="info-section">
    <tbody>
      <tr>
        <td class="left-width text-right">DEPARTMENT :</td>
        <td class="mid-width underline">{{$pdf_data['delivery']['warehouse'] ? $pdf_data['delivery']['warehouse']['warehouse_description'] : ''}}</td>
        <td class="right-width  text-right">DR NO.</td>
        <td class="underline">{{$pdf_data['delivery']['rr_Document_Delivery_Receipt_No']}}</td>
        <td class="right-width  text-right">DR DATE:</td>
        <td class="underline">{{date('m/d/Y',strtotime($pdf_data['delivery']['receivedDate']))}}</td>
        <td class="right-width  text-right">DR Rcvd Date:</td>
        <td class="underline ">{{date('m/d/Y',strtotime($pdf_data['delivery']['rr_received_date']))}}</td>
      </tr>
      <tr>
        <td class="left-width text-right">Supplier Name. :</td>
        <td class="mid-width underline">{{$pdf_data['delivery']['vendor_Name']}}</td>
        <td class="right-width  text-right">INVOICE No. :</td>
        <td class="underline">{{$pdf_data['delivery']['rr_Document_Invoice_No']}}</td>
        <td class="right-width  text-right">INVOICE Date. :</td>
        <td class="underline">{{$pdf_data['delivery']['rr_Document_Invoice_Date'] ? date('m/d/Y',strtotime($pdf_data['delivery']['rr_Document_Invoice_Date'])) : ''}}</td>
        <td class="right-width  text-right">Inv Rcvd Date:</td>
        <td class="underline ">{{date('m/d/Y',strtotime($pdf_data['delivery']['invoice_received_date']))}}</td>
      </tr>
      
      <tr>
        <td class="left-width  text-right">Address. :</td>
        <td class="mid-width underline">{{$pdf_data['delivery']['vendor_ContactPerson']}}</td>
        <td class="right-width  text-right">RR No. :</td>
        <td class="underline">{{$pdf_data['delivery']['rr_Document_Number']}}</td>
        <td class="right-width  text-right">RR Date. :</td>
        <td class="underline ">{{date('m/d/Y',strtotime($pdf_data['delivery']['rr_Document_Transaction_Date']))}}</td>
        <td class="right-width  text-right"></td>
        <td class="underline "></td>
      </tr>

      <tr>
        <td class="left-width  text-right">Tel No. :</td>
        <td class="mid-width underline">{{$pdf_data['delivery']['vendor_TelNo']}}</td>
        <td class="right-width  text-right"> PR No. :</td>
        <td class="underline">{{$pdf_data['delivery']['pr_Document_Number']}}</td>
        <td class="right-width  text-right"> PR Date. :</td>
        <td class="underline">{{$pdf_data['delivery']['pr_Transaction_Date'] ? date('m/d/Y',strtotime($pdf_data['delivery']['pr_Transaction_Date'])) : ''}}</td>
        <td></td>
      </tr>
      <tr>
        <td class="left-width"></td>
        <td class="mid-width "></td>
        <td class="right-width text-right"> PO No. :</td>
        <td class="underline">{{$pdf_data['delivery']['po_Document_Number']}}</td>
        <td class="right-width  text-right"> PO Date. :</td>
        <td class="underline">{{date('m/d/Y',strtotime($pdf_data['delivery']['po_Document_transaction_date']))}}</td>
        <td></td>
      </tr>
    </tbody>
  </table>
    
    
  <table class="item-section">
    <thead>
      <tr>
        <th rowspan="2">Code</th>
        <th rowspan="2">Item Description</th>
        <th rowspan="2">UOM</th>
        <th colspan="2">Batch Information</th>
        <th rowspan="2" class="border-bottom-none">QTY </th>
        <th rowspan="1" class="border-bottom-none">UNIT </th>
        <th rowspan="2">DISC. AMOUNT</th>
        <th rowspan="2">NET PRICE</th>
        <th rowspan="2">Amount</th>
      </tr>
      <tr>
        <th rowspan="1" class="border-top-none border-bottom-none">NUMBER</th>
        <th rowspan="1" class="border-top-none border-bottom-none">EXPIRY</th>
        <th rowspan="1" class="border-top-none">PRICE</th>
      </tr>
    </thead>
    <tbody>
      
      @if(count($pdf_data['delivery']['items']) > 0)
        @foreach ($pdf_data['delivery']['items'] as $detail)
          @php 
              $expirydate = '';
              $batchno = '';
              if($detail['expirydate']) {
                $expirydate = $detail['ismedicine'] ? date('m-d-Y',strtotime($detail['expirydate'])) : '';
                $batchno = $detail['ismedicine'] ? $detail['batchno'] : '';
              }
          @endphp
        <tr>
          <td class="item-td" >{{ $detail['itemcode'] }}</td>
          <td class="item-td">{{ $detail['itemname'] }} 
          <!-- {{ $detail['ismedicine'] ? $detail['description'] :'' }} -->
          </td>
          <td class="item-td">{{ $detail['uom'] }}</td>
          <td class="item-td">{{ $batchno }}</td>
          <td class="item-td">{{ $expirydate }}</td>
          <td class="item-td">{{ intval($detail['qty']) }}</td>
          <td class="item-td">{{$pdf_data['currency']}}{{ number_format($detail['price'],2) }}</td>
          <td class="item-td">{{$pdf_data['currency']}}{{ number_format($detail['discount'],2) }}</td>
          <td class="item-td">{{$pdf_data['currency']}}{{ number_format($detail['gross_amount'],2) }}</td>
          <td class="item-td">{{$pdf_data['currency']}}{{ number_format($detail['net_amount'],2) }}</td>
        </tr>
        @endforeach

      @else 
      <tr>
        <td colspan="10"  > No Record found</td>
      </tr>
      @endif
      <tr>
        <td colspan="8" class="border-none" ></td>
        <td colspan="2" class="item-td border-none"><br></td>
      </tr>

      <tr>
        <td class="border-none" >Received By</td>
        <td class="border-none" > : {{ucwords($pdf_data['delivery']['receivedBy'])}}</td>
        <td colspan="6" class="border-none text-right" >SubTotal :</td>
        <td colspan="2" class="item-td border-none border-bottom text-left">{{$pdf_data['currency']}}{{number_format($pdf_data['sub_total'], 2)}}</td>
      </tr>

      <tr>
        <td class="border-none" >Printed Date</td>
        <td class="border-none" > : {{ date('m-d-Y H:i:s A')}}</td>
        <td colspan="6" class="border-none  text-right" >Discount :</td>
        <td colspan="2"  class="item-td border-none border-bottom text-left">{{$pdf_data['currency']}}{{number_format($pdf_data['discount'], 2)}}</td>
      </tr>

      <tr>
        <td colspan="8" class=" border-none  text-right" >Vat :</td>
        <td colspan="2" class="item-td border-none border-bottom text-left">{{$pdf_data['currency']}}{{number_format($pdf_data['vat_amount'], 2)}}</td>
      </tr>

      <tr>
        <td colspan="8" class="border-none  text-right" >Total Amount :</td>
        <td colspan="2"  class="item-td border-none border-bottom text-left">{{$pdf_data['currency']}}{{number_format($pdf_data['grand_total'], 2)}}</td>
      </tr>

    </tbody>
  </table>
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
