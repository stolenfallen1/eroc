<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Request for Quotation</title>
  <style>
    .header-section {
      width: 100%;
      position: relative;
    }

    .header-text {
      position: absolute;
      text-align: center;
      width: 69%;

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
      text-transform: uppercase;
    }

    .right-width {
      width: 110px;
      text-align: right;
      text-transform: uppercase;
    }

    .mid-width {
      text-transform: uppercase;
      width: 320px;
    }

    .underline {
      border-bottom: 1px black solid;
    }

    td {
      font-size: 12px;
      text-transform:uppercase;
    }

    th {
      font-size: 12px;
    }

    .item-td {
      text-align: center;
    }

    .spacer {
      margin-top: 2px;
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
      float: left;
    }

    .signatory-section2 {
      float: right;
    }

    .comptroller {
      padding-top: 25px !important;
    }

    .item-td:nth-child(3) {
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

    .text-center {
      text-align: center !important;
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
      <h3>{{$pdf_data['rfq_header']['branch']}}</h3>
      <h5 style="margin: -20px !important;">{{$pdf_data['rfq_header']['address']}}</h5>
      <h5>TIN {{$pdf_data['rfq_header']['TIN']}}</h5>
    </div>
  </div>
  <div class="title-section">
    <h3>Request for Quotation</h3>
  </div>
  <table class="info-section">
    <tbody>
      <tr>
        <td class="left-width">Department</td>
        <td class="mid-width underline">:{{$pdf_data['rfq_header']['warehouse']}}</td>
        <td class="right-width">Date Required</td>
        <td class="underline">:{{$pdf_data['rfq_header']['rfq_document_Date_Required']}}</td>
      </tr>
      <tr>
        <td class="left-width">RFQ No.</td>
        <td class="mid-width underline">:{{$pdf_data['rfq_header']['rfq_document_Reference_Number']}}</td>
        <td class="right-width">Date Issued</td>
        <td class="underline">:{{$pdf_data['rfq_header']['rfq_document_Issued_Date']}}</td>
      </tr>
      <tr>
        <td class="left-width">PR No.</td>
        <td class="mid-width underline">:{{$pdf_data['rfq_header']['pr_number']}}</td>
        <td class="right-width">Issued By</td>
        <td class="underline">:{{ucwords($pdf_data['rfq_header']['rfq_document_IssuedBy'])}}</td>
      </tr>
      <tr>
        <td class="left-width"> Supplier Name</td>
        <td class="mid-width underline">:{{$pdf_data['rfq_header']['rfq_document_Vendor_Id']}}</td>
        <td class="right-width">Lead Time</td>
        <td class="underline">:{{$pdf_data['rfq_header']['rfq_document_LeadTime']}}</td>
      </tr>
      <tr>
        <td class="left-width">Tel No</td>
        <td class="mid-width underline">:{{$pdf_data['rfq_header']['rfq_document_Vendor_telno']}}</td>
        <td class="right-width"></td>
      </tr>
      <tr>
        <td class="left-width">Address</td>
        <td class="mid-width underline" colspan="3">:{{$pdf_data['rfq_header']['rfq_document_Vendor_address']}}</td>
      </tr>

      <tr>
        <td class="left-width">Remarks</td>
        <td class="mid-width underline" colspan="3">:{{$pdf_data['rfq_header']['rfq_document_IntructionToBidders']}}</td>
      </tr>
    </tbody>
  </table>
  <table class="item-section">
    <thead>
      <th>#</th>
      <th>CODE</th>
      <th>ITEM DESCRIPTION</th>
      <th>UOM</th>
      <th>QTY</th>
      <th>UNIT PRICE</th>
      <th>DISC. %</th>
      <th>VAT</th>
      <th>NET AMOUNT</th>
    </thead>
    <tbody>
      @php $counter = 1 @endphp
      @foreach ($pdf_data['rfq_request'] as $detail)
      <tr>
        <td class="text-center">{{$counter++}}</td>
        <td class="text-center">{{$detail['pr_Document_Item_Id']}}</td>
        <td>{{$detail['item']['item_name']}}</td>
        <td class="text-center">{{$detail['unit']['name']}}</td>
        <td class="text-center">{{$detail['pr_Document_Item_Approved_Qty']}}</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
      </tr>
      @endforeach
      <tr>
        <td colspan="8">
        </td>
        <td colspan="1">
          <div><br></div>
        </td>
      </tr>
    </tbody>
  </table>
  <div class="spacer"></div>

  <div style="letter-spacing: 1px;">
    <p style="font-size:12px;">Deadline of submission : <b>{{$pdf_data['rfq_header']['rfq_document_Date_Required']}}</b></p>
    <ul style="font-size:12px;text-decoration:none;">
      <li style="text-decoration:none; list-style:none;">
        <b> INSTRUCTION TO SUPPLIER :</b><br><br>
        <ol>
          <li style="margin-bottom: 5px;">Prices and specifications should be valid for 45 CALENDAR DAYS</li>
          <li style="margin-bottom: 5px;">Indicate the following information in your mind :
            <ul style="text-decoration:none; list-style:none;">
              <li style="margin-bottom: 5px;">a. Company name. address, tel/fax nos.;</li>
              <li style="margin-bottom: 5px;">b. Supplier's Offer (technical specification / detailed descriptions and clear pictures of items offered/brand) per item;</li>
              <li style="margin-bottom: 5px;">c. Name of the supplier's authorized representative;</li>
              <li style="margin-bottom: 5px;">d. Signature and date.;</li>
            </ul>
          </li>
          <li style="margin-bottom: 5px;">Supplier must submit their bids together with the following requirements;
            <ul style="text-decoration:none; list-style:none;">
              <li>a.<b> Valid and Current Mayor's / Business Permit {{date('Y')}};</b></li>
            </ul>
          </li>
          <li style="margin-bottom: 5px;">Bids / Qoutation may be submitted thru fax, email or directly to Cebu Doctors University Hospital - Business Office, Purchasing Section</li>
          <li style="margin-bottom: 5px;">DELIVERY PERIOD: __________, upon receipt of P.O <br>
            (Pls. state reason / justification if delivery cannot be made within the period herien stated); __________________________________
          </li>
          <li style="margin-bottom: 5px;">Terms of Payment : <b>30 days net upon delivery,</b> unless specified.</li>
          <li>Total price qouted above subject to withholding tax and payable check</li>
        </ol>
      </li>
    </ul>
  </div><br><br>
</body>

</html>