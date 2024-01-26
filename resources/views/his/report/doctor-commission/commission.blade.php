<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daily Census Report</title>
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
                <h2 style="margin:  15px 0px 0px 0px;">COMMISSION REPORT</h2>
            </div>
            <div class="company-address">
                <h3 style="margin:  15px 0px 0px 0px;">Report Date : {{ date('m/d/Y', strtotime($startdate)) }} - {{ date('m/d/Y', strtotime($enddate)) }}</h3>
            </div>
        </div>
    </div>
    <div class="content-section">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th class="borderbottom" width="10%">REFNUM</th>
                    <th class="borderbottom" width="15%">CASE #</th>
                    <th class="borderbottom" width="15%">SOURCE CASE #</th>
                    <th class="borderbottom" width="20%">TRANSDATE.</th>
                    <th class="borderbottom" width="25%">NAME OF PATIENT</th>
                    <th class="borderbottom" width="10%">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $patient_type = ['IN-PATIENT' => [], 'OUT-PATIENT' => []];
                    $doctors = [];
                @endphp
                @foreach ($results as $result)
                    <?php
                    // Check if the condition is met
                    $doctor = $result['doctor_details']['doctor_name'];
                    if (isset($doctors[$doctor])) {
                        // If the doctors exists, add the item to the doctors's array
                        $doctors[$doctor][] = $result;
                    } else {
                        // If the itemname doesn't exist, create a new array for the itemname and add the item
                        $doctors[$doctor] = [$result];
                    }
                    ?>
                @endforeach
                 @php
                    $total = 0;
                    $counter = 1;
                  
                @endphp
                @if(count($doctors) > 0)
                @foreach ($doctors as $key => $doctor)
                    <tr>
                        <td colspan="6">
                           <div style="margin-top: 2px;margin-bottom:2px;min-width:250px;border-bottom:1px solid gray"> {{$counter++}}. &nbsp; {{ $key }} &nbsp; &nbsp;</div>
                        </td>
                    </tr>
                    @php
                        $sub_total = 0;
                    @endphp
                    @foreach ($doctor as $item)
                        @php $sub_total += $item['amount'] @endphp
                        <tr>
                            <td class="text-center" width="5%">{{ $item['refnum'] }}</td>
                            <td class="text-center" width="5%">{{ $item['case_no_out'] }}</td>
                            <td class="text-center" width="5%">{{ $item['case_no_in'] }}</td>
                            <td class="text-center" width="15%">{{ date("m/d/Y h:i:s A", strtotime($item['transdate'])) }}</td>
                            <td class="text-center" width="25%">{{ $item['patient_details']['patient_name'] }}</td>
                            <td class="text-center" width="5%">{{ $item['amount'] }}</td>
                        </tr>
                    @endforeach
                      @php $total += $sub_total @endphp
                        <tr>
                            <td class="text-center bordertop" width="5%"></td>
                            <td class="text-center bordertop" width="5%"></td>
                            <td class="text-center bordertop" width="5%"></td>
                            <td class="text-center bordertop" width="5%"></td>
                            <td class="text-center bordertop" width="5%"></td>
                            <td class="text-center bordertop" width="5%">{{ number_format($sub_total,2) }}</td>
                        </tr>
                        
                @endforeach
                @else
                <tr>
                    <td class="text-center bordertop" colspan="6" width="5%">No Result found!</td>
                </tr>
                @endif
                 <tr>
                    <td class="text-center" width="5%"></td>
                    <td class="text-center" width="5%"></td>
                    <td class="text-center" width="5%"></td>
                    <td class="text-center" width="5%"></td>
                    <td class="text-right" width="5%">GRAND TOTAL </td>
                    <td class="text-center" width="5%">{{ number_format($total,2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>
