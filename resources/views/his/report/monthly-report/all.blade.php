<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hemodialysis Monthly Report</title>
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
            font-size: 16px;
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
                <h2 style="margin:  15px 0px 0px 0px;">Hemodialysis Monthly Report</h2>
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
                    <th width="1%" rowspan="2"></th>
                    <th width="80%" rowspan="2">Description</th>
                    <th width="10%" colspan="2">IN PATIENT</th>
                    <th width="10%" colspan="2">OUT PATIENT</th>
                    <th width="5%" colspan="2">TOTAL</th>
                </tr>
                <tr>
                    <th width="100px">NUMBER</th>
                    <th width="100px">AMOUNT</th>
                    <th width="100px">NUMBER</th>
                    <th width="100px">AMOUNT</th>
                    <th width="100px">NUMBER</th>
                    <th width="100px">AMOUNT</th>
                </tr>
            </thead>
            <tbody>

                @php
                    $counter = 1;
                    $grand_total_inpatient = 0;
                    $grand_total_amount_inpatient = 0;
                    $grand_total_outpatient = 0;
                    $grand_total_amount_outpatient = 0;
                    $grand_total_number = 0;
                    $grand_total_amount = 0;
                @endphp
                @foreach ($results as $key => $result)
                    <tr>
                        <td colspan="8">
                            <h3>{{ $key }}</h3>
                        </td>
                    </tr>
                    @php
                        $total_inpatient = 0;
                        $total_amount_inpatient = 0;
                        $total_outpatient = 0;
                        $total_amount_outpatient = 0;
                        $total_number = 0;
                        $total_amount = 0;
                    @endphp
                    @foreach ($result as $item)
                        @php
                            $total_inpatient += $item->INNUMBER;
                            $total_amount_inpatient += $item->INAMOUNT;
                            $total_outpatient += $item->OUTNUMBER;
                            $total_amount_outpatient += $item->OUTAMOUNT;
                            $total_number += $item->INNUMBER + $item->OUTNUMBER;
                            $total_amount += $item->INAMOUNT + $item->OUTAMOUNT;
                        @endphp
                        <tr>
                            <td>&nbsp;&nbsp;{{ $counter++ }}. </td>
                            <td>{{ $item->DESCRIPTION }}</td>
                            <td class="text-center">{{ (int) $item->INNUMBER }}</td>
                            <td class="text-right">{{ number_format($item->INAMOUNT, 2) }}</td>
                            <td class="text-center">{{ (int) $item->OUTNUMBER }}</td>
                            <td class="text-right">{{ number_format($item->OUTAMOUNT, 2) }}</td>
                            <td class="text-center">{{ number_format($item->INNUMBER + $item->OUTNUMBER, 2) }}</td>
                            <td class="text-right">{{ number_format($item->INAMOUNT + $item->OUTAMOUNT, 2) }}</td>
                        </tr>
                    @endforeach
                    @php
                        $grand_total_inpatient += $total_inpatient;
                        $grand_total_amount_inpatient += $total_amount_inpatient;
                        $grand_total_outpatient += $total_outpatient;
                        $grand_total_amount_outpatient += $total_amount_outpatient;
                        $grand_total_number += $total_number;
                        $grand_total_amount += $total_amount;
                    @endphp
                    <tr>
                        <td colspan="2"></td>
                        <td class="bordertop text-center">{{ number_format($total_inpatient, 2) }}</td>
                        <td class="bordertop text-right">{{ number_format($total_amount_inpatient, 2) }}</td>
                        <td class="bordertop text-center">{{ number_format($total_outpatient, 2) }}</td>
                        <td class="bordertop text-right">{{ number_format($total_amount_outpatient, 2) }}</td>
                        <td class="bordertop text-center">{{ number_format($total_number, 2) }}</td>
                        <td class="bordertop text-right">{{ number_format($total_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="8"><br></td>
                    </tr>
                @endforeach

                <tr>
                    <td class="text-right borderbottom" colspan="2"><p class="double-underline">TOTALS</p></td>
                    <td class="text-center">{{ number_format($grand_total_inpatient, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_total_amount_inpatient, 2) }}</td>
                    <td class="text-center">{{ number_format($grand_total_outpatient, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_total_amount_outpatient, 2) }}</td>
                    <td class="text-center">{{ number_format($grand_total_number, 2) }}</td>
                    <td class="text-right">{{ number_format($grand_total_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>
