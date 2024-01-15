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
                <h2 style="margin:  15px 0px 0px 0px;">OUT-PATIENT DAILY CENSUS REPORT</h2>
            </div>
            <div class="company-address">
                <h3 style="margin:  15px 0px 0px 0px;">Report Date : {{ date('m/d/Y', strtotime($startdate)) }}</h3>
            </div>
        </div>
    </div>
    <div class="content-section">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th class="borderbottom" width="5%">NO.</th>
                    <th class="borderbottom" width="25%">NAME OF PATIENT</th>
                    <th class="borderbottom" width="10%">ROOM #</th>
                    <th class="borderbottom" width="10%">SLIP</th>
                    <th class="borderbottom" width="26%">EXAM</th>
                    <th class="borderbottom" width="10%">ACCOUNT</th>
                    <th class="borderbottom" width="25%">PHYSICIAN</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $total_companypatient  = 1;
                    $total_cashpatient = 1;
                    $counter = 1;
                @endphp
                @php 
                    $patient_type = array('CASH' => array(), 'COMPANY' => array()); 
                @endphp
                @foreach($results as $type)
                    <?php
                        // Check if the condition is met
                        if ($type['pid'] == $type['accountnum']) {
                            // If the condition is true, add the item to the 'CASH' array
                            $patient_type['CASH'][] = $type;
                        } else {
                            // If the condition is false, add the item to the 'COMPANY' array
                            $patient_type['COMPANY'][] = $type;
                        }
                    ?>
                @endforeach
                @if(count($patient_type['COMPANY']) > 0)
                    <tr>
                        <td class="text-left" colspan="7">
                           <b> C/O PATIENTS</b>
                        </td>
                    </tr>
                    @foreach ($patient_type['COMPANY'] as $result)
                        <tr>
                            <td class="text-center" width="5%">{{ $total_companypatient++ }}</td>
                            <td class="text-left" width="25%"><small>{{ $result['patient_registry']['patient_details']['patient_name'] }}</small></td>
                            <td class="text-left" width="10%">{{ $result['inpatient_datails'] ? $result['inpatient_datails']['inpatient_datails']['station_details']['room_id'] : '' }}</td>
                            <td class="text-left" width="10%">{{ $result['refnum'] }}</td>
                            <td class="text-left" width="26%"><small>{{ $result['items'] ?  $result['items']['exam_description'] : 'Dr. '. $result['doctor_details']['doctor_name']   }}</small></td>
                            <td width="20%"><small>{{ substr($result['patient_registry']['patient_details']['patient_registry_details']['guarantor_name'], 0, 15) }}</small></td>
                            <td width="23%"><small>{{ substr($result['requesting_doctor_details']['doctor_name'], 0, 30) }}</small></td>
                        </tr>
                    @endforeach
                @endif
                    @if(count($patient_type['CASH']) > 0)
                    <tr>
                        <td class="text-left" colspan="7">
                           <br>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-left" colspan="7">
                           <b> CASH BASIS PATIENTS</b>
                        </td>
                    </tr>
                    @foreach ($patient_type['CASH'] as $result)
                        <tr>
                            <td class="text-center" width="5%">{{ $total_cashpatient++ }}</td>
                            <td class="text-left" width="25%"><small>{{ $result['patient_registry']['patient_details']['patient_name'] }}</small></td>
                            <td class="text-left" width="10%">{{ $result['inpatient_datails'] ? $result['inpatient_datails']['inpatient_datails']['station_details']['room_id'] : '' }}</td>
                            <td class="text-left" width="10%">{{ $result['refnum'] }}</td>
                            <td class="text-left" width="26%"><small>{{ $result['items'] ?  $result['items']['exam_description'] : 'Dr. '. $result['doctor_details']['doctor_name']   }}</small></td>
                            <td width="20%"><small>{{ substr($result['patient_registry']['patient_details']['patient_registry_details']['guarantor_name'], 0, 15) }}</small></td>
                            <td width="23%"><small>{{ substr($result['requesting_doctor_details']['doctor_name'], 0, 30) }}</small></td>
                        </tr>
                    @endforeach
                @endif
                <tr>
                    <td class="text-center" colspan="7">
                        <br>
                        <br>
                        <center>*** SUMMARY ***</center>
                        <br>
                    </td>
                </tr>
                 <tr>
                    <td class="text-right" colspan="4">TOTAL COMPANY</td>
                    <td class="text-left" width="10%">
                        <div style="width:80px;text-align:right;"><u>&nbsp;&nbsp;{{count($patient_type['COMPANY'])}}</u></div>
                    </td>
                    <td colspan="2"></td>
                </tr>
                 <tr>
                    <td class="text-right" colspan="4">TOTAL CASH BASIS PATIENT</td>
                    <td class="text-left"  width="10%">
                         <div style="width:80px;text-align:right;"><u>&nbsp;&nbsp;{{count($patient_type['CASH'])}}</u> </div>
                    </td>
                    <td colspan="2"></td>
                </tr>
                 <tr>
                    <td class="text-right" colspan="4">GRAND TOTAL</td>
                    <td class="text-left">
                        <div style="width:80px;text-align:right;"><u><u>&nbsp;&nbsp;{{count($patient_type['CASH']) + count($patient_type['COMPANY'])}}</u></u> </div>
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>
