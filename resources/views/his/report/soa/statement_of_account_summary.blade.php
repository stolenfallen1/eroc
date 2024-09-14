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
                font-size : 12px;
                line-height: 11px;
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
                top: 30%;
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
                border-top: 1px solid #000;
                padding-top: 2px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="header-column" style="width: 25%;">
                    <div class="header-position-logo">
                        <img src="../public/images/CDUH_logo.jpg" width="80" height="80" class="logo" />
                    </div>
                </div>
                <div class="header-column" style="width: 40%;">
                    <p class="upper-center-text">Cebu Doctors University Hospital, Inc.</p>
                    <p class="center-text-sub-content">Osmeña Boulevard. Cebu City 6000</p>
                    <p class="center-text-sub-content">000-309-308-000-NV</p>
                    <p class="center-text-sub-content">Tel#: 2555555 Fax#: 2536021</p>
                    <p class="lower-center-text">Patient's Statement of Account</p>
                </div>
                <div class="header-column" style="width: 25%;">
                    <div class="header-position">
                        <p class="left-text">Page No: Page</p>
                        <p class="left-text">Run Date: {{ $Run_Date }}</p>
                        <p class="left-text">Run Time: {{ $Run_Time }}</p>
                    </div>
                </div>
            </div>
            <table>
                <tbody style="border-bottom: 1px solid #000;">
                    <tr>
                        <th>Patient  </th>
                        <td style="width: 40%;">: {{ $Patient_Info[0]['Patient_Name'] }}</td>
                        <th>Admission #</th>
                        <td style="width: 15%;">: {{ $Patient_Info[0]['Admission_No'] }}</td>
                        <th>Room</th>
                        <td style="width: 15%;">: EMERGENCY</td>
                    </tr>

                    <tr>
                        <th>Address  </th>
                        <td>:  {{ $Patient_Info[0]['Patient_Address'] }}</td>
                        <th>Hospital #</th>
                        <td>: {{ $Patient_Info[0]['Hospital_Name'] }}</td>
                        <th>Rate</th>
                        <td>: 1000.00</td>
                    </tr>

                    <tr>
                        <th>Company</th>
                        <td colspan="5">:  {{ $Patient_Info[0]['Guarantor'] }}</td>
                    </tr>
                    
                    <tr>
                        <th>Acct. #  </th>
                        <td>:  {{ $Patient_Info[0]['Account_No'] }}</td>
                        <th>Admitted</th>
                        <td>: 1136918</td>
                        <th>Time</th>
                        <td>:  {{ $Patient_Info[0]['Time_Admitted'] }}</td>
                    </tr>

                    <tr>
                        <th>Credit L  </th>
                        <td>:  {{ $Patient_Info[0]['Credit_Limit'] }}</td>
                        @if ($Patient_Info[0]['Billed_Date'])
                            <th>Billed Date</th>
                            <td>: {{ $Patient_Info[0]['Billed_Date'] }}</td>
                            <th>Time</th>
                            <td>:  {{ $Patient_Info[0]['Billed_Time'] }}</td>
                        @endif
                    </tr>
                </tbody>
            </table>
            @php
                $grossTotal = 0;
                $lessAmount = 0;
            @endphp
            <table class="custom-table">
                <thead style=" border: none;">
                    <tr>
                        <th colspan="4" style="text-align: center;">*** Summary ***</th>
                    </tr>
                </thead>
                <tbody style="border-bottom: none;">
                    <tr>
                        <td colspan="4">Description :</td>
                    </tr>
                    @foreach ($PatientBilSummary as $bill)
                        {{ $grossTotal += floatval($bill['Total']) }}
                        @if ($bill['AccountType'] === 'D' || $bill['AccountType'] === 'P')
                            <tr>
                                <td class="dot-cell" style="text-indent: 20px;" colspan="3">
                                <span>{{ $bill['Description'] }}</span>
                                </t>
                                <td style="width: 5%;">
                                    {{ number_format($bill['Total'], 2) }}
                                </td>
                            </tr>
                        @else
                            {{ $grossTotal -= floatval($bill['Total']) }}
                            @continue
                        @endif
                    @endforeach
                </tbody>
            </table>
            <table class="custom-table">
                <tbody style="border: none;">
                    <tr>
                        <td style="width: 100%; text-align: right;">
                            <span class="solid-line">
                                Total Due : {{ number_format($grossTotal, 2)}}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <table class="custom-table">
                <tbody style="border-bottom: none;">
                    <tr>
                        <td colspan="4">LESS :</td>
                    </tr>
                    @foreach ($PatientBilSummary as $bill)
                        @if ($bill['AccountType'] === 'C')
                            {{ $lessAmount += floatval($bill['Total']) }}
                            <tr>
                                <td class="dot-cell" style="text-indent: 20px;" colspan="3">
                                    {{ $bill['Description'] }}
                                </t>
                                <td style="width: 5%;">
                                    {{ number_format($bill['Total'], 2) }}
                                </td>
                            </tr>
                        @else
                            @continue
                        @endif
                    @endforeach
                </tbody>
            </table>
            <br/>
            <table class="custom-table">
                <tbody style="border: none;">
                    <tr>
                        <td style="width: 100%; text-align: right;">
                            <span class="solid-line">
                                Net Due Amount : {{ number_format(floatval($grossTotal) - floatval($lessAmount), 2)}}
                            </span>
                        </td>
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
            <p style="position: relative; top: 30%;">NOTE: An interest of 3% per month will be cahrged if account us not paid within 15 days from date of receipt of Statement of Account</p>
        </div>
    </body>
</html>