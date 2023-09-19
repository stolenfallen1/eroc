<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daily Transaction Report</title>
    <style>
        .header-section {
            display: block;
            width: 100% !important;
            margin-bottom: 2%;
        }

        .company-details {
            width: 100% !important;
            text-align: center !important;
        }

        table {
            font-family: serif;
            border-collapse: collapse;
            width: 100%;
        }

        .content-section {
            width: 100%;
        }

        table>thead>tr>th {
            text-align: center;
            text-transform: uppercase;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bordertop {
            border-top: 1px solid black;
        }

        .borderbottom {
            border-bottom: 1px solid black;
        }


        h2 {
            margin: 0px;
        }

        h3 {
            margin: 0px;
        }

        .double-underline {
            position: relative;
            text-decoration: underline;
        }

        .double-underline::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 1px;
            /* Adjust the height for the double underline effect */
            background-color: #000;
            /* Change the color of the double underline */
            content: "";
        }

        @page {
            font-size: 12px;
            margin: 2%;
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="header-section">
        <div class="company-details">
            <div class="company-name">
                <h2>CEBU DOCTORS` UNIVERSITY HOSPITAL, INC.</h2>
            </div>
            <div class="company-address">
                <h3>Osmeña Boulevard, Cebu City 6000</h3>
            </div>
            <div class="company-address">
                <h2 style="margin:  15px 0px 0px 0px;">Daily Transaction Report</h2>
            </div>
            <div class="company-address">
                <h3 style="margin:  15px 0px 0px 0px;">{{ date('m/d/Y', strtotime($startdate)) }} -
                    {{ date('m/d/Y', strtotime($enddate)) }}</h3>
            </div>
        </div>
    </div>

    <div class="content-section">
        <table>
            <thead>
                <tr>
                    <th class="borderbottom" width="10%">DATE</th>
                    <th class="borderbottom" width="10%">REF. #</th>
                    <th class="borderbottom" width="8%">OR #</th>
                    <th class="borderbottom" width="8%">ROOM #</th>
                    <th class="borderbottom" width="5%">AMOUNT</th>
                    <th class="borderbottom" width="5%">QTY</th>
                    <th class="borderbottom" width="8%">CODE</th>
                    <th class="borderbottom" width="33%">DESCRIPTION</th>
                    <th class="borderbottom" width="30%">NAME OF PATIENT</th>
                    <th class="borderbottom" width="5%">BY</th>
                </tr>
            </thead>
            <tbody>

                @php
                    $total_inpatient  = 0;
                    $total_outpatient = 0;
                    $grand_total_amount = 0;
                @endphp
                @foreach ($results as $result)
                    @php
                        $counter = 1;
                        if(strtoupper($result->RoomID) == 'OPD'){
                            $total_outpatient += $result->Amount;
                        }else{
                            $total_inpatient += $result->Amount;
                        }
                        $grand_total_amount = 0;
                    @endphp
                    <tr>
                        <td class="text-center" width="10%">{{ date('m/d/Y', strtotime($result->Transdate)) }}</td>
                        <td class="text-center" width="10%">{{ $result->ChargeSlip }}</td>
                        <td class="text-center" width="5%">{{ $result->ReferenceNum }}</td>
                        <td class="text-center" width="5%">{{ $result->RoomID }}</td>
                        <td class="text-right" width="5%">{{ number_format($result->Amount, 2) }}</td>
                        <td class="text-center" width="5%">{{ (int) $result->Quantity }}</td>
                        <td class="text-center" width="5%">{{ $result->ItemID }}</td>
                        <td width="30%"><small>{{ $result->Description }}</small></td>
                        <td width="30%"><small>{{ $result->Patient }}</small></td>
                        <td class="text-center"width="5%">{{ $result->Initial }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td class="text-center" colspan="10">
                        <br>
                        <br>
                        <center>*** SUMMARY ***</center>
                        <br>
                    </td>
                </tr>
                 <tr>
                    <td class="text-right" colspan="7">TOTAL IN PATIENT</td>
                    <td class="text-left" width="10%">
                        <div style="width:80px;text-align:right;">&nbsp;&nbsp;{{number_format($total_inpatient,2)}} </div>
                    </td>
                    <td colspan="2"></td>
                </tr>
                 <tr>
                    <td class="text-right" colspan="7">TOTAL OUT PATIENT</td>
                    <td class="text-left"  width="10%">
                         <div style="width:80px;text-align:right;">  <u>&nbsp;&nbsp;{{number_format($total_outpatient,2)}}</u> </div>
                    </td>
                    <td colspan="2"></td>
                </tr>
                 <tr>
                    <td class="text-right" colspan="7">GRAND TOTAL</td>
                    <td class="text-left">
                        <div style="width:80px;text-align:right;"> <u><u>&nbsp;&nbsp;{{number_format(($total_outpatient + $total_inpatient),2)}}</u></u> </div>
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>
