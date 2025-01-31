<!DOCTYPE html>
<html>

    <head>
        <title>Service Record PDF</title>
        <style>
            body {
                font-family: 'Montserrat', sans-serif;
                margin: 0;
                padding: 0;
            }

            .logo {
                position: absolute;
                top: 0;
                left: 120;
                right: 0;
                bottom: 0;
            }

            .header-text {
                text-align: center;
                line-height: 2px;
            }

            .header-text h1 {
                font-size: 24px;
            }

            .header-text p {
                font-size: 18px;
            }

            .sub-header-text {
                text-align: center;
            }

            .sub-header-text h1 {
                margin: 0;
                font-size: 16px
            }

            .container {
                width: 100%;
            }

            .styled-table {
                width: 100%;
                border-collapse: collapse;
            }
            .styled-table thead tr {
                /* background-color: #107dac !important; */
                color: #000;
                text-align: left;
            }

            .styled-table th,
            .styled-table td {
                padding: 4px 8px;
                font-size: 11px;
                text-align: left;
            }

            .styled-table tbody tr {
                border-bottom: 1px solid #dddddd;
            }

            .styled-table tbody tr:nth-of-type(even) {
                background-color: #f3f3f3;
            }

            .styled-table tbody tr:last-of-type {
                border-bottom: 2px solid transparent;
            }

            .styled-table tbody tr.active-row {
                font-weight: bold;
                color: #009879;
            }

            .day-cell {
                text-align: center;
                vertical-align: top;
            }
            .page-break {
                page-break-after: always;
            }

            .page-break-before {
                page-break-before: always;
            }
            .date-label {
                font-weight: bold;
                margin-bottom: 5px;
            }

            .date-item {
                margin-left: 20px;
            }

            .employee-header-info {
                display: flex;
                margin-top: 30px;
                margin-bottom: 15px;
                text-align: center;
            }

            .employee-detail {
                display: inline-block;
                margin: 0 68px;
            }

            .employee-header-info p {
                font-size: 11px;
                text-align: center;
                margin: 0;
                line-height: 1.5;
            }

            .employed-info {
                font-size: 11px;
                font-weight: bold;
                margin: 0 68px !important;

            }

            .parent-div .employee-header-info {
                margin-top: 5px;
                text-align: center;
            }

            .parent-div .employee-header-info .employee-detail p{
                font-size: 11px;
                /* margin: 0 20px !important; */
            }


            .employee-record-container {
                width: 100%;
                margin: 1rem;
            }

            .employee-record {
                display: grid;
                justify-content: space-between;
                align-items: flex-start;
                padding: 0 20px;
            }

            .employee-record-detail {
                display: inline-block;
                box-sizing: border-box;
                width: 100%;
            }

            .column{
                width: 48%;
                display: inline-block;
                margin-top: 10px;
            }

            .column-table {
                height: 40%;
            }

            .column-table p {
                line-height: 1px;
                font-size: 12px;
                text-transform: uppercase;
            }
            .employee-record div {
                /* margin-top: 2rem; */
            }

            .employee-record div p {
                text-transform: uppercase;
                margin: 0;
                /* padding: 0 10px; */
            }

            .table-height {
                height: 25%;
            }

            .header-info {
                height: 20%;
            }

            .header-info p {
                font-size: 1rem;
                font-weight: bold;
                text-transform: capitalize;
            }

            .header-info p span {
                font-weight: bold;
            }

            .employment-info {
                position: relative;
                top:-20;
                left:160;
            }
            .total-column {
                padding: 4px 8px;
            }
        </style>
    </head>

    <body>
        @php
            $monthName = array(
                'January', 'February', 'March', 'April', 'May', 'June', 'July',
                'August', 'September', 'October', 'November', 'December'
            );
            $groupedAbsences = collect($sumOfAbsences)->groupBy('Department');
            $totalPerMonth = array_fill_keys($monthName, 0);
        @endphp
        <div class="container">
            <div class="table-height">
                <div class="header-text">
                    <img src="../public/images/CDUH_logo.jpg" width="80" height="80" class="logo" />
                    <h1>Cebu Doctors University Hospital</h1>
                    <p>Osmeña Blvd., Capitol Site, Cebu City</p>
                </div>
                <div class="sub-header-text">
                    <h1>Total Absences Per Department</h1>
                </div>
                <div class="employee-record-detail">
                    <div class="column">
                        <div class="header-info">
                            <p>Year : {{ $Year }}</p>
                        </div>
                    </div>
                </div>
                <table border="1" class="styled-table">
                    <thead>
                        <tr>
                            <th rowspan="2" style="text-align: center">Department</th>
                            <th colspan="12" style="text-align: center">Month</th>
                            <th rowspan="2">Total</th>
                        </tr>
                        <tr>
                            @foreach($monthName as $month)
                                <th>{{ $month }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody
                        @foreach($groupedAbsences as $department => $records)
                            @php
                                $absencesByMonth = array_fill_keys($monthName, 0);
                                $totalDepartmentAbsences = 0;
                            @endphp

                            @foreach($records as $absence)
                                @php
                                    $absencesByMonth[$absence->Month] = $absence->AbsentCount;
                                    $totalDepartmentAbsences += $absence->AbsentCount;
                                    $totalPerMonth[$absence->Month] += $absence->AbsentCount;
                                @endphp
                            @endforeach

                            <tr>
                                <td>{{ $department }}</td>
                                @foreach($monthName as $month)
                                    <td style="text-align: center">{{ $absencesByMonth[$month] }}</td>
                                @endforeach
                                <td style="text-align: center"><strong>{{ $totalDepartmentAbsences }}</strong></td>
                            </tr>
                        @endforeach
                        <tr style="border-bottom: 1px solid #000000;">
                            <td><strong>Total</strong></td>
                            @foreach($monthName as $month)
                                <td style="text-align: center"><strong>{{ $totalPerMonth[$month] }}</strong></td>
                            @endforeach
                            <td style="text-align: center"><strong>{{ array_sum($totalPerMonth) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </body>
</html>
