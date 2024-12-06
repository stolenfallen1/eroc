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
      /* width: 100px; */
      text-transform: uppercase;
    }

    .mid-width {
      /* width: 370px; */
      text-transform: uppercase;
    }

    .underline {
      border-bottom: 1px black solid;
    }


    .item-td1 {
      /* width: 300px; */
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
      margin: 20px 20px 20px 20px !important;
      width: 100%;
    }

    .item-td:nth-child(2) {
      text-align: left;
    }

    .item-section th {
      text-transform: uppercase !important;
      font-size: 9px !important;
      font-family: 'DejaVu Sans', sans-serif;
    }

    td {
      font-size: 10px !important;
    }

    .item-section td {
      text-transform: uppercase !important;
      font-size: 9px !important;
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
      border-bottom: 1px solid !important;
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
        <td colspan="3" style="padding:5px;">
          <center>
            <h3>{{ucwords('RECEIVING REPORT')}}</h3>
          </center>
        </td>
      </tr>
    </table>
  </div>
  <table class="info-section">
    <tbody>
      <tr>
        <td class="left-width text-right">DEPARTMENT:</td>
        <td class="mid-width underline">{{$pdf_data['delivery']['warehouse'] ? $pdf_data['delivery']['warehouse']['warehouse_description'] : ''}}</td>
        <td class="right-width  text-right"></td>
        <td class=""></td>
        <td class="right-width  text-right"></td>
        <td class=""></td>
      </tr>
      <tr>
        <td class="left-width text-right">Supplier Name. :</td>
        <td class="mid-width underline">{{$pdf_data['delivery']['vendor_Name']}}</td>
        <td class="right-width  text-right">INVOICE No. :</td>
        <td class="underline">{{$pdf_data['delivery']['rr_Document_Invoice_No']}}</td>
        <td class="right-width  text-right">INVOICE Date. :</td>
        <td class="underline">{{date('m-d-Y',strtotime($pdf_data['delivery']['rr_Document_Invoice_Date']))}}</td>
      </tr>
      <tr>
        <td class="left-width  text-right">Address. :</td>
        <td class="mid-width underline">{{$pdf_data['delivery']['vendor_ContactPerson']}}</td>
        <td class="right-width  text-right">RR No. :</td>
        <td class="underline">{{$pdf_data['delivery']['rr_Document_Number']}}</td>
        <td class="right-width  text-right">RR Date. :</td>
        <td class="underline">{{date('m-d-Y',strtotime($pdf_data['delivery']['rr_Document_Transaction_Date']))}}</td>
      </tr>

      <tr>
        <td class="left-width  text-right">Tel No. :</td>
        <td class="mid-width underline">{{$pdf_data['delivery']['vendor_TelNo']}}</td>
        <td class="right-width  text-right"> PR No. :</td>
        <td class="underline">{{$pdf_data['delivery']['pr_Document_Number']}}</td>
        <td class="right-width  text-right"> PR Date. :</td>
        <td class="underline">{{date('m-d-Y',strtotime($pdf_data['delivery']['pr_Transaction_Date']))}}</td>
        <td></td>
      </tr>
      <tr>
        <td class="left-width"></td>
        <td class="mid-width "></td>
        <td class="right-width text-right"> PO No. :</td>
        <td class="underline">{{$pdf_data['delivery']['po_Document_Number']}}</td>
        <td class="right-width  text-right"> PO Date. :</td>
        <td class="underline">{{date('m-d-Y',strtotime($pdf_data['delivery']['po_Document_transaction_date']))}}</td>
        <td></td>
      </tr>
    </tbody>
  </table>

  <table class="item-section">
    <thead>
      <tr>
        <th rowspan="2" width="40">Code</th>
        <th rowspan="2">Item Description</th>
        <th rowspan="2">UOM</th>
        <th colspan="3">Batch Information</th>
        <th colspan="4">QUANTITY</th>
        <th rowspan="2" class="border-bottom-none">PRICE</th>
        <th rowspan="2" class="border-bottom-none">DISC. AMOUNT</th>
        <th rowspan="2">VAT AMOUNT</th>
        <th rowspan="2">NET Amount</th>
      </tr>
      <tr>
        <th rowspan="1" class="border-top-none border-bottom-none">NUMBER</th>
        <th rowspan="1" class="border-top-none border-bottom-none" width="50">QTY</th>
        <th rowspan="1" class="border-top-none border-bottom-none" width="15">EXPIRY</th>
        <th rowspan="1" class="border-top-none" width="15">ORDER</th>
        <th rowspan="1" class="border-top-none" width="15">RECEIVED</th>
        <th rowspan="1" class="border-top-none" width="15">SERVED</th>
        <th rowspan="1" class="border-top-none">BALANCE</th>
      </tr>
    </thead>

    <tbody>
      @if(count($pdf_data['groupedNonFreeGoods']) > 0)
      @foreach ($pdf_data['groupedNonFreeGoods'] as $itemName => $items)
      @foreach ($items as $index => $item)
      @php
      $total = DB::connection('sqlsrv_mmis')->table('CDG_MMIS.dbo.VwDeliveryDetails')->where('po_Document_Number',$item['po_Document_Number'])->where('isFreeGoods',0)->where('itemcode',$item['itemcode'])->groupBy('po_Document_Number')->sum('served_qty');
      $batchdetails = DB::connection('sqlsrv_mmis')->table('CDG_MMIS.dbo.itemBatchModelNumberMaster')->where('delivery_item_id',$item->rr_detail_id)->get();
      $expirydate = '';
      $batchno = '';
      $qty = '';
      if($batchdetails) {
     
      }
      @endphp
      <tr>
        @if ($index == 0)
        <td class="item-td" rowspan="{{ count($items) }}">{{ $item['itemcode'] }}</td>
        <td class="item-td" rowspan="{{ count($items) }}">{{ $itemName }}</td>
        @endif
        <!-- Display individual item details -->
        <td class="item-td">{{ $item['uom'] }}</td>
        <td class="item-td">
          @foreach($batchdetails  as $batch)
            <div>{{$item['ismedicine'] ? $batch->batch_Number : ''}}</div>
          @endforeach
        </td>
        <td class="item-td">
          @foreach($batchdetails  as $batch)
            <div>{{$item['ismedicine'] ? $batch->item_Qty : ''}}</div>
          @endforeach
        </td>
        <td class="item-td">
          @foreach($batchdetails  as $batch)
            <div>{{$item['ismedicine'] ? date('m-d-Y',strtotime($batch->item_Expiry_Date)) : ''}}</div>
          @endforeach
        </td>
        <td class="item-td ">{{ $item['order_qty'] }}</td>
        <td class="item-td ">{{ $item['served_qty'] }}</td>
        <td class="item-td ">{{ $total }}</td>
        <td class="item-td ">{{ ($item['order_qty'] - $total) }}</td>
        <td class="item-td ">{{$pdf_data['currency']}}{{ number_format($item['price'],2) }}</td>
        <td class="item-td ">{{$pdf_data['currency']}}{{ number_format($item['discount'],2) }}</td>
        <td class="item-td ">{{$pdf_data['currency']}}{{ number_format($item['vat'],2) }}</td>
        <td class="item-td ">{{$pdf_data['currency']}}{{ number_format($item['net_amount'],2) }}</td>
      </tr>
      @endforeach
      @endforeach

      @else
      <tr>
        <td colspan="14"> No Record found</td>
      </tr>
      @endif
      <tr>
        <td colspan="14" class="border-none"><br></td>
      </tr>
    </tbody>
  </table>
  <table>
    <tr>
      <td class="border-none text-right"></td>
      <td class="border-none  text-left "> </td>
      <td colspan="8" class="border-none text-right">Gross Amount :</td>
      <td colspan="2" width="10" class="item-td border-none border-bottom text-left">{{$pdf_data['currency']}}{{number_format($pdf_data['sub_total'], 2)}}</td>
    </tr>

    <tr>
      <td colspan="10" width="150" class="border-none  text-right">Less : Discount :</td>
      <td colspan="2" width="10" class="item-td border-none border-bottom text-left">{{$pdf_data['currency']}}{{number_format($pdf_data['discount'], 2)}}</td>
    </tr>
    <tr>
      <td colspan="10" width="150" class="border-none  text-right"></td>
      <td colspan="2" width="10" class="item-td border-none border-bottom text-left">{{$pdf_data['currency']}}{{number_format(($pdf_data['sub_total'] - $pdf_data['discount']), 2)}}</td>
    </tr>
    <tr>
      <td colspan="10" width="150" class="border-none  text-right"></td>
      <td colspan="2" width="10" class="item-td border-none border-bottom text-left"><br></td>
    </tr>

     <tr>
      <td colspan="10" width="150" class=" border-none  text-right">VAT SALES:</td>
      <td colspan="2" width="10" class="item-td border-none border-bottom text-left">
        @if($pdf_data['delivery']['warehouse'] == '78' || $pdf_data['delivery']['warehouse'] == '66')
        {{$pdf_data['currency']}}{{number_format(($pdf_data['sub_total'] - $pdf_data['vat_amount']), 2)}}
        @else 
        {{$pdf_data['currency']}}{{number_format((($pdf_data['grand_total'] + $pdf_data['discount']) - $pdf_data['vat_amount']), 2)}}
        @endif
    </td>
    <tr>
      <td colspan="10" width="150" class=" border-none  text-right">VAT :</td>
      <td colspan="2" width="10" class="item-td border-none border-bottom text-left">{{$pdf_data['currency']}}{{number_format($pdf_data['vat_amount'], 2)}}</td>
    </tr>
    <!-- <tr>
      <td class="border-none text-right"></td>
      <td class="border-none  text-left "> </td>
      <td colspan="8" class="border-none text-right">SubTotal (Vat Exclusive):</td>
      <td colspan="2" class="item-td border-none border-bottom text-left">{{$pdf_data['currency']}}{{number_format($pdf_data['sub_total'], 2)}}</td>
    </tr> -->
    <tr>
      <td colspan="10" class="border-none  text-right">TOTAL AMOUNT DUE :</td>
      <td colspan="2" class="item-td border-none border-bottom text-left">{{$pdf_data['currency']}}{{number_format($pdf_data['grand_total'], 2)}}</td>
    </tr>
  </table>
  @if(count($pdf_data['free_goods_delivery_items']) > 0)
  <table class="item-section">
    <thead>
      <td colspan="10" class="item-td border-none text-left">
        All Free Goods
      </td>
      <tr>
        <th rowspan="2" width="40">Code</th>
        <th rowspan="2">Item Description</th>
        <th rowspan="2">UOM</th>
        <th colspan="3">Batch Information</th>
        <th>QUANTITY</th>
        <th rowspan="1" class="border-bottom-none">UNIT </th>
        <th rowspan="1" class="border-bottom-none">DISC.</th>
        <th rowspan="2">NET PRICE</th>
        <th rowspan="2">Amount</th>
      </tr>
      <tr>
        <th rowspan="1" class="border-top-none border-bottom-none">NUMBER</th>
        <th rowspan="1" class="border-top-none border-bottom-none" width="50">QTY</th>
        <th rowspan="1" class="border-top-none border-bottom-none" width="50">EXPIRY</th>
        <th rowspan="1" class="border-top-none">FREE</th>
        <th rowspan="1" class="border-top-none">PRICE</th>
        <th rowspan="1" class="border-top-none">AMOUNT</th>
      </tr>
    </thead>
    @if(count($pdf_data['groupedFreeGoods']) > 0)
    @foreach ($pdf_data['groupedFreeGoods'] as $itemName => $items)
    @foreach ($items as $index => $item)
    @php
    $total = DB::connection('sqlsrv_mmis')->table('CDG_MMIS.dbo.VwDeliveryDetails')->where('po_Document_Number',$item['po_Document_Number'])->where('itemcode',$item['itemcode'])->groupBy('po_Document_Number')->sum('served_qty');
    $batchdetails = DB::connection('sqlsrv_mmis')->table('CDG_MMIS.dbo.itemBatchModelNumberMaster')->where('delivery_item_id',$item->rr_detail_id)->get();
    $expirydate = '';
    $batchno = '';
    if($item['expirydate']) {
    $expirydate = $item['ismedicine'] ? date('m-d-Y',strtotime($item['expirydate'])) : '';
    $batchno = $item['ismedicine'] ? $item['batchno'] : '';
    }
    @endphp
    <tr>
      @if ($index == 0)
      <td class="item-td" rowspan="{{ count($items) }}">{{ $item['itemcode'] }}</td>
      <td class="item-td" rowspan="{{ count($items) }}">{{ $itemName }}</td>
      @endif
      <!-- Display individual item details -->
      <td class="item-td">{{ $item['uom'] }}</td>
      <td class="item-td">
        @foreach($batchdetails  as $batch)
          <div>{{$item['ismedicine'] ? $batch->batch_Number : ''}}</div>
        @endforeach
      </td>
      <td class="item-td">
        @foreach($batchdetails  as $batch)
          <div>{{$item['ismedicine'] ? $batch->item_Qty : ''}}</div>
        @endforeach
      </td>
      <td class="item-td">
        @foreach($batchdetails  as $batch)
          <div>{{$item['ismedicine'] ? date('m-d-Y',strtotime($batch->item_Expiry_Date)) : ''}}</div>
        @endforeach
      </td>
      <td class="item-td ">{{ $item['served_qty'] }}</td>
      <td class="item-td ">{{$pdf_data['currency']}}{{ number_format($item['price'],2) }}</td>
      <td class="item-td ">{{$pdf_data['currency']}}{{ number_format($item['discount'],2) }}</td>
      <td class="item-td ">{{$pdf_data['currency']}}{{ number_format($item['gross_amount'],2) }}</td>
      <td class="item-td ">{{$pdf_data['currency']}}{{ number_format($item['net_amount'],2) }}</td>
    </tr>
    @endforeach
    @endforeach

    @else
    <tr>
      <td colspan="11"> No Record found</td>
    </tr>
    @endif
    <tr>
      <td colspan="7" class="border-none"></td>
      <td colspan="4" class="item-td border-none"><br></td>
    </tr>
  </table>
  @endif
  <table>
    <tr>
      <td class="border-none text-left">Received By : {{ucwords($pdf_data['delivery']['receivedBy'])}}</td>
      <td class="border-none  text-left "></td>
      <td colspan="8" class="border-none text-right"></td>
      <td colspan="2" class="text-left"></td>
    </tr>

    <tr>
      <td class="border-none  text-left">Printed Date : {{ date('m-d-Y H:i:s A')}}</td>
      <td class="border-none  text-left"> </td>
      <td colspan="8" class="border-none  text-right"></td>
      <td colspan="2" class="text-left"></td>
    </tr>

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
