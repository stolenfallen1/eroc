<!DOCTYPE html>
<html>
    <head>
        <title>Statement Of Account Summary</title>
        <style>
            * {
                font-family: 'Times New Roman', Times, serif;
            }
            .container {
                width: 100%;
                margin-top: 3rem;
            }
            .header {
                display: inline-block;
                box-sizing: border-box;
                width: 100%;

            }
            .header-column {
                display: inline-block;
                text-align: center;
                align-items: space-evenly;
            }
            .header-column 
            .upper-center-text, 
            .lower-center-text {
                text-align: center;
                text-transform: uppercase;
                font-size : 12px;
                line-height: 11px;
            }

            .header-column 
            .left-text {
                text-align: left;
                padding-left: 70px;
                font-size : 12px;
                line-height: 11px;
                width: 100%;
            }
            .header-column 
            .center-text-sub-content {
                letter-spacing: 1px;
                font-size: 12px;
                line-height: 4px;
            }
            .header-position {
                position: relative;
                top: -20;
                left: 50;
                bottom: 50;
                text-align: right;
            }
            .header-position-logo {
                position: relative;
                margin-bottom: 20px;
                text-align: left;
            }

            .body-content {
                display: inline-block;
                position: relative;
                left: 0;
                right: 0;
                box-sizing: border-box;
                width: 100%;
                align-items: space-between;
            }

            .content-column {
                margin-top: 30px;
                width: 49%;
                display: inline-block;
            }
            .custom-content-column {
                margin-top: 30px;
                width: 49%;
                display: inline-block;
                line-height: 1px;
                position: relative;
                top: -30;
                text-align: center;
                font-size: 12px;
            }
            .sub-column {
                display: inline-block;
                flex-direction: column;
                line-height: 4px;
                align-items: space-around;
            }
            .sub-column:nth-child(odd){
                padding-left: 10px;
                letter-spacing: 2px;
                font-size: 12px;
            }
            .sub-column:nth-child(even){
                text-transform: uppercase;
                letter-spacing: 2px;
                font-size: 12px;
            }
            .content-left-position {
                position: relative;
                left: 50;
            }
            .line-postion {
                position: relative;
                top: 30;
            }
            .full-width-line {
                border-top: 1px solid #000;
                width: 80%;
                text-align: left;
            }
            table {
                width: 100%;
                position: relative;
                top: -25px;
                margin-bottom: 5px;
            }
            thead {
                border-bottom: 1px solid #000;
                font-size: 12px;
            }
            thead tr th{
                text-align: left;
                font-size: 12px;
                font-weight: normal;
            }
            tbody {
                border-bottom: 1px solid #000;
            }
            tbody tr td {
                text-transform: uppercase;
                text-align: left;
                font-size: 12px;
            }
            tbody tr th {
                font-weight: normal;
                text-align: left;
                font-size: 14px;
            }

            .custom-table {
                width: 100%;
                border-collapse: collapse;
            }

            .custom-table .dot-cell {
                position: relative;
                white-space: nowrap;
            }

            .custom-table .dot-cell,
            .solid-line span {
                position: relative;
                z-index: 1;
                padding-right: 10px;
            }

            .custom-table .dot-cell::after {
                content: "";
                position: absolute;
                left: 80;
                right: 0;
                top: 9;
                transform: translateY(-50%);
                border-bottom: 1px dotted black;
                z-index: 0;
            }

            .custom-table .dot-cell
            .solid-line span::after {
                content: "";
                position: absolute;
                left: 100%;
                right: 0;
                top: 50%;
                border-bottom: 1px dotted black;
                transform: translateY(-50%);
                z-index: 0;
            }

            .custom-table .solid-line {
                position: relative;
                white-space: nowrap;
            }
            .solid-line {
                width: 20%;
                text-align: right;
            }
            .solid-line-last {
                width: 20%;
                text-align: right;
                border-bottom: 1px solid #000;
            }

            .page-break {
                page-break-after: always;
            }

            .page-break-before {
                page-break-before: always;
            }

        </style>
    </head>
    <body>
        @php
            $grossTotal = 0;
            $lessAmount = 0;
            $lessDiscount = 0;
            $netAmount = 0;
            $doctorsFee = 0;
            $count = 0;
            $isFirst = true;
            $isLast = true;
            $prevID = '';
            $currentID = '';
            $currentID = '';
            $sub_total = 0;
            $totalPHIC = 0;
            $totalDiscount = 0;
            $totalPayment = 0;

            $mainHeader = function($Run_Date, $Run_Time) {
                return "
                    <div class='header'>
                        <div class='header-column' style='width: 25%;'>
                            <div class='header-position-logo'>
                                <img src='../public/images/CDUH_logo.jpg' width='80' height='80' class='logo' />
                            </div>
                        </div>
                        <div class='header-column' style='width: 40%;'>
                            <p class='upper-center-text'>Cebu Doctors University Hospital, Inc.</p>
                            <p class='center-text-sub-content'>Osmeña Boulevard. Cebu City 6000</p>
                            <p class='center-text-sub-content'>000-309-308-000-NV</p>
                            <p class='center-text-sub-content'>Tel#: 2555555 Fax#: 2536021</p>
                            <p class='lower-center-text'>Patient`s Statement of Account</p>
                        </div>
                        <div class='header-column' style='width: 25%;'>
                            <div class='header-position'>
                                <p class='left-text'>Page No: Page</p>
                                <p class='left-text'>Run Date: {$Run_Date}</p>
                                <p class='left-text'>Run Time: {$Run_Time}</p>
                            </div>
                        </div>
                    </div>
                ";
            };

            $pageHeaderPatientInfo = function() use($Patient_Info) {
                $patient = $Patient_Info[0];
                return "
                    <table>
                        <tbody style='border-bottom: 1px solid #000;'>
                            <tr>
                                <th>Patient  </th>
                                <td style='width: 40%;'>: {$patient['Patient_Name']}</td>
                                <th>Admission #</th>
                                <td style='width: 10%;'>: {$patient['Admission_No']}</td>
                                <th>Room</th>
                                <td style='width: 20%;'>: EMERGENCY</td>
                            </tr>

                            <tr>
                                <th>Address  </th>
                                <td>:  {$patient['Patient_Address']}</td>
                                <th>Hospital #</th>
                                <td>: {$patient['Hospital_Name']}</td>
                                <th>Rate</th>
                                <td>: 1000.00</td>
                            </tr>

                            <tr>
                                <th>Company</th>
                                <td style='width: 40%;'>:  {$patient['Guarantor']}</td>
                                <th style='width: 20%;'>Discharged Date</th>
                                <td>:</td>
                                <th>Time</th>
                                <td>:</td>
                            </tr>
                            
                            <tr>
                                <th>Acct. #  </th>
                                <td>:  {$patient['Account_No']}</td>
                                <th>Admitted</th>
                                <td>: 1136918</td>
                                <th>Time</th>
                                <td>:  {$patient['Time_Admitted']}</td>
                            </tr>

                            <tr>
                                <th>Credit L  </th>
                                <td>:  {$patient['Credit_Limit']}</td>
                                <th>Billed Date</th>
                                <td>: {$patient['Billed_Date']}</td>
                                <th>Time</th>
                                <td>:  {$patient['Billed_Time']}</td>
                            </tr>
                        </tbody>
                    </table>
                ";
            };

            $sectionTotalSummary = function($bill) {
                return "
                    <tr>
                        <td class='dot-cell' style='text-indent: 20px; width: 60%;' colspan='2'>
                            <span>{$bill['Description']}</span>
                        </t>
                        <td class='solid-line'>
                            " . number_format($bill['Total'], 2) ."
                        </td>
                        <td style='width: 30%;'></td>
                    </tr>
                ";
            };

            $creditTotalSummary = function($bill) {
                return "
                    <tr>
                        <td class='dot-cell' style='text-indent: 20px;' colspan='2'>
                            {$bill['Description']}
                        </t>
                        <td class='solid-line'>
                            ( " . number_format($bill['Credit'], 2) . " )
                        </td>
                        <td style='width: 30%;'></td>
                    </tr>
                ";
            };

            $renderElement = function($bill) {
                return "
                    <tr>
                        <td>{$bill['Date']}</td>
                        <td>{$bill['Reference_No']}</td>
                        <td>{$bill['Description']}</td>
                        <td style='text-align:right;'>{$bill['Quantity']}</td>
                        <td style='text-align:right;'>{$bill['Charges']}</td>
                        <td style='text-align:right;'>{$bill['Credit']}</td>
                        <td style='text-align:right;'>{$bill['Balance']}</td>
                    </tr>
                ";
            };

        @endphp
        <div class="container">

            {!! $mainHeader($Run_Date, $Run_Time) !!}
            {!! $pageHeaderPatientInfo() !!}

            <table class="custom-table">
                <thead style=" border: none;">
                    <tr>
                        <th colspan="4" style="text-align: center;font-size: 14px; font-weight: bold; text-transform: uppercase;">*** Summary ***</th>
                    </tr>
                </thead>
                <tbody style="border-bottom: none;">
                    <tr>
                        <td colspan="4">Description</td>
                    </tr>
                    @foreach ($PatientBilSummary as $bill)
                        {{ $grossTotal += floatval($bill['Total']) }}
                        @if (($bill['AccountType'] === 'D' || $bill['AccountType'] === 'P') && $bill['RevenueID'] !== 'MD')
                            {!! $sectionTotalSummary($bill) !!}
                        @else
                            {{ $grossTotal -= floatval($bill['Total']) }}
                            @continue
                        @endif
                    @endforeach
                    <tr>
                        <td style="text-indent: 20px;" colspan="2"></td>
                        <td class="solid-line-last"></td>
                        <td style="width: 30%;"></td>
                    </tr>
                </tbody>
            </table>
            <table class="custom-table">
                <tbody style="border: none;">
                    <tr>
                        <td style="width: 100%; text-align: right; font-weight: bold;">
                            <span class="solid-line">
                                {{ number_format($grossTotal, 2)}}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <table class="custom-table">
                <tbody style="border-bottom: none;">
                    @foreach ($PatientBilSummary as $bill)
                        @if ($bill['AccountType'] === 'C')
                            <tr>
                                <td colspan="4">LESS :</td>
                            </tr>
                        @else
                            @continue
                        @endif
                    @endforeach

                    @foreach ($PatientBilSummary as $bill)
                        @if ($bill['AccountType'] === 'C')

                            {{ $lessAmount += floatval($bill['Credit']) }}
                            {!! $creditTotalSummary($bill) !!}

                        @else
                            @continue
                        @endif
                    @endforeach

                    @foreach ($PatientBilSummary as $bill)
                        @if ($bill['AccountType'] === 'C')
                            <tr>
                                <td style="text-indent: 20px;" colspan="2"></td>
                                <td class="solid-line-last"></td>
                                <td style="width: 30%;"></td>
                            </tr>
                        @else
                            @continue
                        @endif
                    @endforeach
                </tbody>
            </table>
            <table class="custom-table">
                <tbody style="border: none;">
                    <tr>
                        <td style="text-indent: 20px;" colspan="2"></td>
                        <td style="width: 30%;"></td>
                        <td class="solid-line-last" style="font-weight: bold;">
                            ( {{ number_format($lessAmount, 2)}} )
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">Net Amount :</td>
                        <td style="text-align: right; font-weight: bold;">
                            @php
                                $netAmount = floatval($grossTotal) - floatval($lessAmount)
                            @endphp
                            {{ number_format($netAmount, 2)}}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3"></td>
                        <td class="solid-line-last"></td>
                    </tr>
                    <tr>
                        <td colspan="3"></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="3"></td>
                        <td class="solid-line-last"></td>
                    </tr>
                </tbody>
            </table>
            <table class="custom-table">
                <thead style=" border: none;">
                    <tr>
                        <th colspan="4" style="text-align: center;font-size: 14px; font-weight: bold; text-transform: uppercase;">*** Medical Fees ***</th>
                    </tr>
                </thead>
            </table>
            <table class="custom-table">
                <tbody style="border-bottom: none;">
                    <tr>
                        <td colspan="4">Doctor</ta>
                    </tr>
                </d>
            </table>
            <table class="custom-table">
                <thead style=" border: none;">
                    <tr>
                        <th style="width: 40%"></th>
                        <th style="text-align: right;">CHARGES</th>
                        <th style="text-align: right;">PHIC</th>
                        <th style="text-align: right;">DISCOUNT</th>
                        <th style="text-align: right;">PAYMENT</th>
                        <th style="text-align: right;">BALANCE</th>
                    </tr>
                </thead>
                <tbody style="border-bottom: none;">
                    @foreach ($DoctorsFee as $bill)
                        @if ($bill->RevenueID === "MD")
                            @php   
                                $doctorsFee += floatval($bill->Charges);
                                $totalPHIC += floatval($bill->PHIC_MD);
                                $totalDiscount += floatval($bill->Discount);
                                $totalPayment += floatval($bill->Credit);
                                $lessDiscount += floatval($bill->PHIC_MD) + floatval($bill->Discount) + floatval($bill->Credit_MD);
                            @endphp
                            <tr>
                                <td style="text-indent: 20px;">{{ $bill->Description }}</td>
                                <td style="text-align: right;">{{ number_format($bill->Charges,2) }}</td>
                                <td style="text-align: right;">{{ number_format($bill->PHIC_MD, 2) }}</td>
                                <td style="text-align: right;">{{ number_format($bill->Discount, 2) }}</td>
                                <td style="text-align: right;">{{ number_format($bill->Credit, 2)}}</td>
                            </tr>
                        @endif
                    @endforeach
                     <tr>
                            <td style="text-indent: 20px;"></td>
                            <td style="border-top: 1px solid #000; text-align: right;">{{ number_format($doctorsFee,2) }}</td>
                            <td style="border-top: 1px solid #000; text-align: right;">{{ number_format($totalPHIC, 2) }}</td>
                            <td style="border-top: 1px solid #000; text-align: right;">{{ number_format($totalDiscount, 2) }}</td>
                            <td style="border-top: 1px solid #000; text-align: right;">{{ number_format($totalPayment, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="5"></td>
                        <td style="border-bottom: 1px solid #000; text-align: right;">{{ number_format(($doctorsFee - $lessDiscount), 2) }}</td>
                    </tr>
                </tbody>
            </table>
            <table>
                <tbody style="border-bottom: none;">
                    <tr>
                        <td style="width: 70%; text-align: right;">
                            Please Pay This Amount =====================
                            <span style="font-weight: bold; position: relative; left: 10;">
                                P
                            </span>
                        </td>
                        <td style="width: 30%; text-align: right; border-bottom: 1px solid #000;"><span style="font-weight: bold;">{{ number_format($netAmount + ($doctorsFee - $lessDiscount), 2) }}</span></td>
                    </tr>
                    <tr>
                        <td style="width: 70%; text-align: right;"></td>
                        <td style="width: 30%; text-align: right; border-bottom: 1px solid #000;"></td>
                    </tr>
                </tbody>
            </table>
            <div class="body-content">
                <div class="content-column">
                    <p style="text-transform: uppercase; font-size: 12px;">Received statement of account</p>
                    <p style="text-transform: uppercase; font-size: 12px;">with complete supporting papers</p>
                    <br/>
                    <div class="line-position">
                        <div class="full-width-line"></div>
                    </div>
                </div>
                <div class="content-column" style="tr">
                    <p style="text-transform: uppercase; font-size: 12px;">Certified Correct</p>
                    <br/>
                    <div class="line-position">
                        <div class="full-width-line" style="width: 100%"></div>
                    </div>
                </div>
            </div>
            <div class="body-content">
                <div class="content-column">
                    <p>DATE</p>
                    <div class="line-position">
                        <div class="full-width-line"></div>
                    </div>
                </div>
                <div class="custom-content-column">
                    <p>Ms. Bettina Anne L. Velloso</p>
                    <p>Controller</p>
                </div>
            </div>
            <p>NOTE: An interest of 3% per month will be cahrged if account us not paid within 15 days from date of receipt of Statement of Account</p>

            <div class="page-break"></div>
        </div>
        <div class="container">
            {!! $mainHeader($Run_Date, $Run_Time) !!}
            {!! $pageHeaderPatientInfo() !!}

            <table>
                <thead>
                    <tr>
                        <th style="10%">Date</th>
                        <th style="15%">Ref #</th>
                        <th style="30%">Description</th>
                        <th style="5%;">Qty</th>
                        <th style="10%;">Charges</th>
                        <th style="15%;">Credit</th>
                        <th style="15%;">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($Patient_Bill as $bill)
                        @if ($isFirst && $bill['RevenueID'])
                            @php
                                $isFirst = false;
                                $isLast = false;
                                $currentID = $bill['RevenueID'];
                            @endphp
                            <tr>
                                <td colspan="7" style="font-weight: bold; padding-top: 5px;">{{ $bill['RevenueID'] . ' - ' . $bill['RevenueDesc'] }}</td>
                            </tr>
                            {!! $renderElement($bill) !!}
                        @elseif($bill['RevenueID'] === $prevID)
                            {!! $renderElement($bill) !!}
                        @else
                            <tr>
                                <td colspan="5" style="text-align: right; font-weight: bold;">Sub Total : </td>
                                <td style="text-align: right;">=====></td>
                                <td style="border-top: 1px solid #000; text-align: right; font-weight: bold;"">{{ $sub_total }}</td>
                            </tr>
                            <tr>
                                <td colspan="7" style="font-weight: bold; padding-top: 5px;">{{ $bill['RevenueID'] . ' - ' . $bill['RevenueDesc'] }}</td>
                            </tr>
                            {!! $renderElement($bill) !!}
                        @endif
                        @php
                            $prevID = $bill['RevenueID'];
                            $sub_total = $bill['Balance'];
                            $count++;
                        @endphp
                        @if ($count % 26 == 0)
                                </tbody>
                            </table>

                            <div class="page-break"></div>
                            {!! $mainHeader($Run_Date, $Run_Time) !!}

                            <table style="paddting-top: 10px;">
                                <thead>
                                    <tr>
                                        <th>PATIENT : </th>
                                        <td colspan="4">: {{ $Patient_Info[0]['Patient_Name'] }}</td>
                                        <td> ADMISSION : {{ $Patient_Info[0]['Admission_No'] }}</td>
                                        <td style="text-align: right;"> HOSPITAL : {{ $Patient_Info[0]['Hospital_Name'] }}</td>
                                    </tr>
                                </thead>
                            </table>
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width:10%;">Date</th>
                                        <th style="width:15%;">Ref #</th>
                                        <th style="width:30%;">Description</th>
                                        <th style="width:5%;">Qty</th>
                                        <th style="width:10%;">Charges</th>
                                        <th style="width:15%;">Credit</th>
                                        <th style="width:15%;">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                        @endif
                    @endforeach
                    <tr>
                        <td colspan="5" style="text-align: right; font-weight: bold;">Sub Total : </td>
                        <td style="text-align: right;">=====></td>
                        <td style="border-top: 1px solid #000; text-align: right; font-weight: bold;"">{{ $sub_total }}</td>
                    </tr>
                </tbody>
            </table>
            <table>
                <tbody style="border: none;">
                    <tr>
                        <td style="width: 100%; text-align: right; font-weight: bold;">
                            Total Due to Hospital : =========> 
                            <span style="font-weight: bold; padding-right: 10px; position: relative; left: 10;">
                                P
                            </span>
                            {{ $Total_Charges }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </body>
</html>