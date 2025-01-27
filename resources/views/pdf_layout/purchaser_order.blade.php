<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Purchase Order</title>
  <style>
    body {
      font-family: 'DejaVu Sans', sans-serif;
    }

    .header-section {
      width: 100%;
      position: relative;
    }

    .header-text {
      position: absolute;
      text-align: center;
      margin-top: -20px;
      width: 69%;

    }

    .qr-code {
      margin-left: 545px;
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
      margin-top: 20px;
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

    td {
      font-size: 12px;
    }

    th {
      font-size: 12px;
    }

    .item-td {
      text-align: center;
    }

    .item-td-total {
      text-align: right;
      padding-right: 25px;
      font-weight: 800;
    }

    .spacer {
      margin-top: 1px;
      margin-bottom: 2px;
      width: 100%;
      border-bottom: 2px solid;
    }

    .note {
      font-style: italic;
      font-size: 12px;
      vertical-align: top !important;
      margin-bottom: 20px;
    }

    .reminder {
      font-weight: 300;
    }

    .signatory-section1 {
      float: left;
    }

    .signatory-section2 {
      float: right;
    }

    .comptroller {
      padding-top: 25px !important;
    }

    .itemname {
      width: 250px;
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
      margin: 35px 15px 15px 15px !important;
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

    .text-right {
      text-align: right;
    }

    .text-left {
      text-align: left;
    }
    .approver-table{
      width: 100%;
    }
    .approver-table td:nth-child(1){
      width: 30%;
    }
    .approver-table td:nth-child(2){
      width: 30%;
    }
    .approver-table td:nth-child(3){
      width: 30%;
    }
    .text-center{
      text-align: center;
    }
  </style>
</head>

<body>
  <div class="header-section">
    <img src="{{ $pdf_data['logo'] }}" alt="Example Image" width="100" height="100">
    <div class="header-text">
      <h3>{{$pdf_data['purchase_order']['branch_name']}}</h3>
      <h5 style="margin: -20px !important;">{{$pdf_data['purchase_order']['branch_address']}}</h5>
      <h5>TIN {{$pdf_data['purchase_order']['tin']}}</h5>
    </div>
    <img class="qr-code" src="{{ $pdf_data['qr'] }}" alt="Example Image" width="100" height="100">
  </div>
  <div class="title-section">
    <h3>PURCHASE ORDER</h3>
  </div>
  <table class="info-section">
    <tbody>
      <tr>
        <td class="left-width text-right">Supplier Name :</td>
        <td class="mid-width underline">{{$pdf_data['purchase_order']['vendor_Name']}}</td>
        <td class="right-width text-right">PO Date :</td>
        <td class="underline">{{date('m/d/Y',strtotime($pdf_data['purchase_order']['poDate']))}}</td>
      </tr>
      <tr>
        <td class="left-width text-right">Contact Person :</td>
        <td class="mid-width underline">{{$pdf_data['purchase_order']['vendor_ContactPerson']??''}}</td>
        <td class="right-width text-right">Invoice No. :</td>
        <td class="underline"></td>
      </tr>
      <tr>
        <td class="left-width text-right">Address :</td>
        <td class="mid-width underline">{{$pdf_data['purchase_order']['vendor_Address']??''}}</td>
        <td class="right-width text-right">PO. No :</td>
        <td class="underline">{{$pdf_data['purchase_order']['poPrefix']??''}}-{{$pdf_data['purchase_order']['poNumber']??''}}</td>
      </tr>
      <tr>
        <td class="left-width text-right">Tel No. :</td>
        <td class="mid-width underline">{{$pdf_data['purchase_order']['vendor_TelNo']??''}}</td>
        <td class="right-width text-right">Via :</td>
        <td class="underline">{{$pdf_data['purchase_order']['prPrefix']??''}}-{{$pdf_data['purchase_order']['prNumber']??''}}</td>
      </tr>
      <tr>
        <td class="left-width text-right">Terms :</td>
        <td class="mid-width underline">{{$pdf_data['purchase_order']['description']??''}}</td>
        @if($pdf_data['consignment'])
        <td class="right-width text-right">DR No. :</td>
        <td class="underline">{{$pdf_data['consignment'] ? $pdf_data['consignment']['rr_Document_Delivery_Receipt_No'] : ''}}</td>
        @endif
      </tr>
      <tr>
        <td class="left-width text-right">Remarks :</td>
        <td class="mid-width underline">{{$pdf_data['purchase_order']['remarks']??''}}</td>
        @if($pdf_data['consignment'])
        <td class="right-width text-right">DR Date :</td>
        <td class="underline">{{$pdf_data['consignment'] ? date('m/d/Y',strtotime($pdf_data['consignment']['receivedDate'])) : ''}}</td>
        @endif
      </tr>
    </tbody>
  </table>
  <table class="item-section">
    <thead>
      <tr>
        <th >Code</th>
        <th>Item Description</th>
        <th>UOM</th>
        <th>QTY </th>
        <th class="border-bottom-none">PRICE</th>
        <th>TOTAL</th>
        <th>DISC. AMOUNT</th>
        <th>VAT AMOUNT</th>
        <th>NET AMOUNT</th>
      </tr>

    </thead>
    <tbody>
      @foreach ($pdf_data['purchase_order_items'] as $detail)
      <tr>
        <!-- <td class="item-td" width="10">
          @if($detail['vat_type'] == 1)
          <div>**</div>
          @elseif($detail['vat_type'] == 2)
          <div>*</div>
          @else
          @endif
        </td> -->
        <td class="item-td" >{{ $detail['itemcode'] }}</td>
        <td class="item-td">{{ $detail['itemname'] }}</td>
        <td class="item-td" >{{ $detail['uom'] }}</td>
        <td class="item-td" >{{ intval($detail['order_qty']) }}</td>
        <td class="item-td text-right" >{{$pdf_data['currency']}}{{ number_format($detail['price'],2) }}</td>
        <td class="item-td text-right" ">{{$pdf_data['currency']}}{{ number_format($detail['item_total_amount'],2) }}</td>
        <td class="item-td text-right" >{{$pdf_data['currency']}}{{number_format($detail['disc_amount'],2) }}</td>
        <td class="item-td text-right" >{{$pdf_data['currency']}}{{number_format(abs($detail['vat_amount']),2) }}</td>
        <td class="item-td text-right" >{{$pdf_data['currency']}}{{ number_format($detail['item_total_net_amount'],2) }}</td>
      </tr>
      @endforeach
      @if(count($pdf_data['free_goods_purchase_order_items']) > 0)
      <tr>
        <td colspan="9" class="item-td border-none text-left">
          <div><br></div>
        </td>
      </tr>
      <tr>
        <td colspan="9" class="item-td border-none text-left">
          All Free Goods
        </td>
      </tr>
      <thead>
        <tr>
          <th >Code</th>
          <th>Item Description</th>
          <th>UOM</th>
          <th>QTY </th>
          <th class="border-bottom-none">PRICE</th>
          <th>TOTAL</th>
          <th>DISC. AMOUNT</th>
          <th>VAT AMOUNT</th>
          <th>NET AMOUNT</th>
        </tr>

      </thead>

      @foreach ($pdf_data['free_goods_purchase_order_items'] as $detail)
      <tr>
        <td class="item-td"  >{{ $detail['itemcode'] }}</td>
        <td class="item-td">{{ $detail['itemname'] }}</td>
        <td class="item-td">{{ $detail['uom'] }}</td>
        <td class="item-td" >{{ intval($detail['order_qty']) }}</td>
        <td class="item-td" >{{$pdf_data['currency']}}{{ number_format($detail['price'],4) }}</td>
        <td class="item-td" >{{$pdf_data['currency']}}{{ number_format($detail['item_total_amount'],4) }}</td>
        <td class="item-td" >{{ number_format($detail['disc_amount'],4) }}</td>
        <td class="item-td" >{{ number_format(abs($detail['vat_amount']),4) }}</td>
        <td class="item-td" >{{$pdf_data['currency']}}{{ number_format($detail['item_total_net_amount'],4) }}</td>
      </tr>
      @endforeach
      @endif
      <tr>
        <td colspan="9" class="item-td border-none text-left">
          <div><br></div>
        </td>
      </tr>
      <tr>
        <td colspan="5" rowspan="7" class="border-none">
            <p class="note">
              Please enter out order subject to following conditions. Purchase Order number must appear on all invoices. When price are not stated, order must not be filled at
              a price higher than charged on last purchase without notifying the hospital.
            </p>
        </td>
        <td colspan="2" class="border-none text-right">Gross Amount :</td>
        <td colspan="2" class="item-td border-none  text-left">{{$pdf_data['currency']}}{{number_format($pdf_data['sub_total'], 4)}}</td>
      </tr>
      <tr>
        <td colspan="2" class="border-none  text-right">LESS: Discount :</td>
        <td colspan="2" class="item-td border-none border-bottom text-left">{{$pdf_data['currency']}}{{number_format($pdf_data['discount'], 4)}}</td>
      </tr>
      <tr>
        <td colspan="2" class="border-none  text-right"></td>
        <td colspan="2" class="item-td border-none text-left">{{$pdf_data['currency']}}{{number_format($pdf_data['sub_total'] - $pdf_data['discount'], 4)}}</td>
      </tr>
      <tr>
        <td colspan="2" class="border-none  text-right">
          <div style="height: 5px;"></div>
        </td>
        <td colspan="2" class="item-td border-none text-left"></td>
      </tr>
      <tr>
        <td colspan="2" class=" border-none  text-right">VAT SALES:</td>
        <td colspan="2" class="item-td border-none border-bottom text-left">{{$pdf_data['currency']}}{{number_format($pdf_data['vatablesales'], 4)}}</td>
      </tr>

      <tr>
        <td colspan="2" class=" border-none  text-right">VAT :</td>
        <td colspan="2" class="item-td border-none border-bottom text-left">{{$pdf_data['currency']}}{{number_format(abs($pdf_data['vat_amount']), 4)}}</td>
      </tr>

      <tr>
        <td colspan="2" class="border-none  text-right">TOTAL AMOUNT DUE:</td>
        <td colspan="2" class="item-td border-none border-bottom text-left">{{$pdf_data['currency']}}{{number_format($pdf_data['grand_total'], 4)}}</td>
      </tr>

    </tbody>
  </table>
  <table>
    <tbody>
      <tr>
        <td>
          <br>
        </td>
      </tr>
    </tbody>
  </table>
  <p class="csstransforms">THIS DOCUMENT IS NOT VALID <br /> FOR CLAIM OF INPUT TAXES</p>
  <div class="title-section">
    <h5 class="reminder">** This Form is Electronically Signed Out **</h>
  </div>
  <table class="signatory-section1">
    <tbody>
      @if(isset($pdf_data['purchase_order']['administrator_name']))
      @if ($pdf_data['purchase_order']['administrator_name'] != null)
      <tr>
        <td class="underline item-td">{{$pdf_data['purchase_order']['administrator_name']}}</td>
      </tr>
      <tr>
        <td class="item-td">Administrator</td>
      </tr>
      @endif
      @endif
      @if ($pdf_data['purchase_order']['comptroller_name'] != null)
      <tr>
        <td class=" comptroller underline item-td">{{$pdf_data['purchase_order']['comptroller_name']}}</td>
      </tr>
      @endif
      <tr>
        <td class="item-td">Purchasing Comptroller</td>
      </tr>
      @if ($pdf_data['purchase_order']['corporate_admin_name'] != null)
      <tr>
        <td class=" comptroller underline item-td">{{$pdf_data['purchase_order']['corporate_admin_name']}}</td>
      </tr>
      <tr>
        <td class="item-td">Corporate admin</td>
      </tr>
      @endif
      @if(isset($pdf_data['purchase_order']['president']))
      @if ($pdf_data['purchase_order']['president'] != null)
      <tr>
        <td class=" comptroller underline item-td">{{$pdf_data['purchase_order']['president_name']}}</td>
      </tr>
      <tr>
        <td class="item-td">President</td>
      </tr>
      @endif
      @endif
    </tbody>
  </table>
  <table class="signatory-section2">
    <tbody>
      <tr>
        <td>ORDERED BY :</td>
      </tr>
      <tr>
        <td style="padding-top:10px; text-transform: uppercase;" class="item-td underline">{{$pdf_data['purchase_order']['purchasedBy']}}</td>
      </tr>
      <tr>
        <td>
          <div style="display: inline-block; width: 100%;">
            <div style="display: inline-block; width: 10%; vertical-align: top;"><input type="checkbox" style="position: relative;"></div>
            <div style="display: inline-block; width: 85%; vertical-align: middle;">
              <div style="margin-top: 5px;">Central Supply </div>
            </div>
          </div>
        </td>
      </tr>
      <tr>
        <td>
          <div style="display: inline-block; width: 100%;">
            <div style="display: inline-block; width: 10%; vertical-align: top;"><input type="checkbox" style="position: relative;"></div>
            <div style="display: inline-block; width: 85%; vertical-align: middle;">
              <div style="margin-top: 5px;">Pharmacy</div>
            </div>
          </div>
        </td>
      </tr>
      <tr>
        <td>
          <div style="display: inline-block; width: 100%;">
            <div style="display: inline-block; width: 10%; vertical-align: top;"><input type="checkbox" checked style="position: relative;"></div>
            <div style="display: inline-block; width: 85%; vertical-align: middle;">
              <div style="margin-top: 5px;">Others {{$pdf_data['purchase_order']['warehouse_description']}}</div>
            </div>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</body>
</html>
