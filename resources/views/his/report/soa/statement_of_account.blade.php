<!DOCTYPE html>
<html>
    <head>
        <title>Statement Of Account</title>
        <style>
            * {
                font-family: 'Times New Roman', Times, serif;
            }
            .container {
                width: 100%;
            }
            .header, 
            .body-content {
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

            .content-column {
                margin-top: 30px;
                width: 48%;
                display: inline-block;
                line-height: 1px;
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
                top: -40;
            }
            .full-width-line {
                border-top: 3px solid #000;
                width: 100%;
                margin: 0 auto;
            }
            table {
                width: 100%;
                position: relative;
                top: -25px;
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
                text-align: left;
                font-size: 12px;
            }
            tbody tr th {
                font-weight: normal;
                text-align: left;
                font-size: 14px;
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
                <div class="header-column" style="width: 45%;">
                    <p class="upper-center-text">Cebu Doctors University Hospital, Inc.</p>
                    <p class="center-text-sub-content">Osmeña Boulevard. Cebu City 6000</p>
                    <p class="center-text-sub-content">000-309-308-000-NV</p>
                    <p class="center-text-sub-content">Tel#: 2555555 Fax#: 2536021</p>
                    <p class="lower-center-text">Patient's Statement of Account</p>
                </div>
                <div class="header-column" style="width: 25%;">
                    <div class="header-position">
                        <p class="left-text">Page No:</p>
                        <p class="left-text">Run Date: {{ $Run_Date }}</p>
                        <p class="left-text">Run Time: {{ $Run_Time }}</p>
                    </div>
                </div>
            </div>
            <table>
                <tbody style="border-bottom: 2px solid #000;">
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
            <table>
                <thead>
                    <tr>
                        <th style="10%">Date</th>
                        <th style="15%">Ref #</th>
                        <th style="30%">Description</th>
                        <th style="5%">Qty</th>
                        <th style="10%">Charges</th>
                        <th style="15%">Credit</th>
                        <th style="15%">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($Patient_Bill as $bill) 
                        <tr>
                            <td>{{ $bill['Date'] }}</td>
                            <td>{{ $bill['Reference_No'] }}</td>
                            <td>{{ $bill['Description'] }}</td>
                            <td>{{ $bill['Quantity'] }}</td>
                            <td>{{ $bill['Charges'] }}</td>
                            <td>{{ $bill['Credit'] }}</td>
                            <td>{{ $bill['Balance'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <table>
                <tbody style="border: none;">
                    <tr>
                        <td style="width: 50%"></td>
                        <td style="width: 20%">Total Due to Hospital : </td>
                        <td style="width: 10%; text-align: left"">=========></td>
                        <td style="width: 10%; text-align: left">{{ $Total_Charges }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </body>
</html>