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
                font-size: 20px
            }

            .container {
                width: 100%;
            }

            .styled-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                margin-bottom: 10px;
            }

            .styled-table thead tr {
                background-color: #107dac !important;
                color: #ffffff;
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

            .sp-td {
                border: 2px solid #ff0000;
                background: yellow
            }

            .sp-text {
                color: red;
            }

            .page-break {
                page-break-after: always;
            }

            .page-break-before {
                page-break-before: always;
            }

            .employee-creds-text {
                width: 100%;
                display: inline-flex;
                justify-content: space-evenly;
                flex-wrap: wrap;
            }

            .employee-creds-text p {
                margin: 0 10px;
            }

            .full-width-line {
                border-top: 0.5px solid #009879;
                width: 100%;
                margin: 0 auto;
            }

            .date-info {
                font-family: Arial, sans-serif;
                font-size: 14px;
                line-height: 1.6;
                text-align: left;
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
                margin-top: 1.5rem;
            }

            .column{
                width: 48%;
                display: inline-block;
                margin-top:30px;
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
                height: 90%;
            }

            .employee-data {
                height: 20%;
            }

            .employee-data p {
                font-size: 12px;
                text-transform: capitalize;
            }

            .employee-data p span {
                font-weight: bold;
            }

            .employment-info {
                position: relative;
                top:-20;
                left:160;
            }
        </style>
    </head>

    <body>
        @php
            $monthName = array(
                'January', 'February', 'March', 'April', 'May', 'June', 'July',
                'August', 'September', 'October', 'November', 'December'
            );

            function convertMinutesToTimeString($minutes) {
                $hours = floor($minutes / 60);
                $remainingMinutes = $minutes % 60;
                return "{$hours} Hrs {$remainingMinutes} Mins";
            }

            function formatTime($time) {
                if (is_int($time) || ctype_digit($time)) {
                    $time = convertMinutesToTimeString($time);
                }
                preg_match('/(\d+\.?\d*) Hr[s]? (\d+\.?\d*) Min[s]?/', $time, $matches);
                $hourValue = isset($matches[1]) ? floatval($matches[1]) : 0;
                $minuteValue = isset($matches[2]) ? floatval($matches[2]) : 0;

                if ($minuteValue > 0) {
                    return $hourValue > 0 ? intval($matches[1]) . " Hrs " . intval($matches[2]) . " Mins" : intval($matches[2]) . " Mins";
                } elseif ($hourValue > 0) {
                    return intval($matches[1]) . " Hrs";
                } else {
                    return '0';
                }
            }

        @endphp
        <div class="container">
            <div class="table-height">
                <div class="header-text">
                    <img src="../public/images/CDUH_logo.jpg" width="80" height="80" class="logo" />
                    <h1>Cebu Doctors University Hospital</h1>
                    <p>Osmeña Blvd., Capitol Site, Cebu City</p>
                </div>
                <div class="sub-header-text">
                    <h1>Service Record {{ $year }}</h1>
                </div>
                <div class="employee-record-detail">
                    <div class="column">
                        <div class="employee-data">
                            <p><span>Employee No.</span> : {{ $employeeId }}</p>
                            <p><span>Employee Name</span> :  {{ $employeeName }}</p>
                            <p><span>Job Description</span> :  {{ $Position }}</p>
                            <p><span>Section</span> : {{ $Section }}</p>
                            <p><span>Department</span> :  {{ $Department }}</p>
                        </div>
                    </div>
                    <div class="column">
                        <div class="employee-data">
                            <div class="employment-info">
                                <p><span>Probationary Date</span> : {{ $dateEmployed }}</p>
                                <p><span>Regularization Date</span> : {{ $Regularization }}</p>
                                <p><span>Resignation Date</span> : {{ $dateResigned }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Shift</th>
                            @for ($i = 1; $i <= 15; $i++)
                                <th>{{ $i }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groupedData as $g)
                            <tr>
                                <td>{{ implode(', ', $g['monthDesc']) }}</td>
                                <td>{{ implode(', ', $g['codes']) }}</td>
                                @for ($day = 1; $day <= 15; $day++)
                                    @php
                                        $late = 0;
                                        $undertime = 0;
                                        $entries = $g['days'][$day] ?? [];
                                        foreach ($entries as $entry) {
                                            if (strpos($entry, 'L:') === 0) {
                                                $late = intval(substr($entry, 2, -1));
                                            }
                                            if (strpos($entry, 'U:') === 0) {
                                                $undertime = intval(substr($entry, 2, -1));
                                            }
                                        }
                                    @endphp
                                    <td @if ($late >= 120 || $undertime >= 120) class="sp-td" @endif>
                                        @foreach ($entries as $entry)
                                            <span @if ((strpos($entry, 'L:') === 0 && $late < 120) || (strpos($entry, 'U:') === 0 && $undertime < 120)) class="sp-text" @endif>
                                                {{ $entry }}
                                            </span>
                                            @if (!$loop->last)
                                                ,
                                            @endif
                                        @endforeach
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @php
                $hasSecondTableData = false;
                foreach ($groupedData as $g) {
                    for ($day = 16; $day <= 31; $day++) {
                        if (isset($g['days'][$day])) {
                            $hasSecondTableData = true;
                            break 2;
                        }
                    }
                }
            @endphp
            @if ($hasSecondTableData)
                <div class="page-break"></div>
                <div class="table-height">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Shift</th>
                                @for ($i = 16; $i <= 31; $i++)
                                    <th>{{ $i }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groupedData as $g)
                                <tr>
                                    <td>{{ implode(', ', $g['monthDesc']) }}</td>
                                    <td>{{ implode(', ', $g['codes']) }}</td>
                                    @for ($day = 16; $day <= 31; $day++)
                                        @php
                                            $late = 0;
                                            $undertime = 0;
                                            $uTime = '';
                                            $entries = $g['days'][$day] ?? [];
                                            foreach ($entries as $entry) {
                                                if (strpos($entry, 'L:') === 0) {
                                                    $late = intval(substr($entry, 2, -1));
                                                }
                                                if (strpos($entry, 'U:') === 0) {
                                                    $undertime = intval(substr($entry, 2, -1));
                                                }
                                            }
                                        @endphp
                                        <td @if ($late >= 120 || $undertime >= 120) class="sp-td" @endif>
                                            @foreach ($entries as $entry)
                                                <span @if ((strpos($entry, 'L:') === 0 && $late < 120) || (strpos($entry, 'U:') === 0 && $undertime < 120)) class="sp-text" @endif>
                                                    {{ $entry }}
                                                </span>
                                                @if (!$loop->last)
                                                    ,
                                                @endif
                                            @endforeach
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            <div class="page-break"></div>
            <div class="employee-record-container">
                <div>
                    <div class="employee-record-detail">
                        <div class="column">
                            <div class="column-table">
                                <div>
                                    <p>Employee Tardiness</p>
                                    <table class="styled-table">
                                        <thead>
                                            <tr>
                                                <th>Month</th>
                                                <th>Total Tardiness (Minutes)</th>
                                                <th>Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $monthTransaction = 0; @endphp;
                                            @foreach($monthName as $month)
                                                @php
                                                    $monthTransaction++;
                                                    $found = false;
                                                @endphp
                                                @foreach ($EmployeeTardiness as $t)
                                                    @if($t->month === $month)
                                                        <tr>
                                                            <td>{{ $t->month }}</td>
                                                            <td><span @if(intval($t->TotalTardyMinutes) > 60) class="sp-text" @endif>{{ formatTime(intval($t->TotalTardyMinutes)) }}</span></td>
                                                            <td><span @if(intval($t->TardyCount) >= 5) class="sp-text" @endif>{{ $t->TardyCount }}</span></td>
                                                        </tr>
                                                        @php
                                                            $found = true;
                                                            break;
                                                        @endphp
                                                    @endif
                                                @endforeach
                                                @if (!$found)
                                                    <tr>
                                                        <td>{{ $month }}</td>
                                                        <td>{{ $monthTransaction <= count($groupedData) ? '0' : '-' }}</td>
                                                        <td>{{ $monthTransaction <= count($groupedData) ? '0' : '-' }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="column">
                            <div class="column-table">
                                <div>
                                    <p>Employee Undertime</p>
                                    <table class="styled-table">
                                        <thead>
                                            <tr>
                                                <th>Month</th>
                                                <th>Total Undertime</th>
                                                <th>Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $monthTransaction = 0; @endphp;
                                            @foreach($monthName as $month)
                                                @php
                                                    $monthTransaction++;
                                                    $found = false;
                                                @endphp
                                                @foreach ($EmployeeUndertime as $u)
                                                    @php
                                                        $totalUndertimeParts = explode(' ', $u->TotalUndertime);
                                                        $totalUndertimeHours = $totalUndertimeParts[0];
                                                        $totalUndertimeMinutes = $totalUndertimeParts[2];
                                                    @endphp
                                                    @if($u->month === $month)
                                                        <tr>
                                                            <td>{{ $u->month }}</td>
                                                            <td>
                                                                <span @if($totalUndertimeMinutes >= 45 || $totalUndertimeHours >= 1) class="sp-text" @endif>
                                                                    @if($totalUndertimeHours == 0 && $totalUndertimeMinutes > 0)
                                                                        {{ intval($totalUndertimeMinutes) }} Mins
                                                                    @elseif($totalUndertimeMinutes == 0 && $totalUndertimeHours > 0)
                                                                        {{ intval($totalUndertimeHours) }} Hrs
                                                                    @elseif($totalUndertimeHours > 0 && $totalUndertimeMinutes > 0)
                                                                        {{ intval($totalUndertimeHours) }} Hrs {{ intval($totalUndertimeMinutes) }} Mins
                                                                    @endif
                                                                </span>
                                                            </td>
                                                            <td><span @if(intval($u->UndertimeCount) >= 5) class="sp-text" @endif>{{ $u->UndertimeCount }}</span></td>
                                                        </tr>
                                                        @php
                                                            $found = true;
                                                            break;
                                                        @endphp
                                                    @endif
                                                @endforeach
                                                @if (!$found)
                                                    <tr>
                                                        <td>{{ $month }}</td>
                                                        <td>{{ $monthTransaction <= count($groupedData) ? '0' : '-' }}</td>
                                                        <td>{{ $monthTransaction <= count($groupedData) ? '0' : '-' }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                            <tr></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="employee-record-detail">
                        <div class="column">
                            <div class="column-table">
                                <div>
                                    <p>Employee Leave</p>
                                    <table class="styled-table">
                                        <thead>
                                            <tr>
                                                <th>Code</th>
                                                <th>CUMULATIVE COUNT</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($EmployeeLeaves as $leave)
                                                <tr>
                                                    <td>{{ $leave->code }}</td>
                                                    <td>{{ $leave->CumulativeCount }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="column">
                            <div class="column-table">
                                <div>
                                    <p>Overtime</p>
                                    <table class="styled-table">
                                        <thead>
                                            <tr>
                                                <th>Month</th>
                                                <th>Total Overtime</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $monthTransaction = 0; @endphp;
                                            @foreach($monthName as $month)
                                                @php
                                                    $monthTransaction++;
                                                    $found = false;
                                                @endphp
                                                @foreach ($EmployeeOT as $ot)
                                                    @if($ot->Month === $month)
                                                        <tr>
                                                            <td>{{ $ot->Month }}</td>
                                                            <td>{{ formatTime($ot->OTOtalMinutes) }}</td>
                                                        </tr>
                                                        @php
                                                            $found = true;
                                                            break;
                                                        @endphp
                                                    @endif
                                                @endforeach
                                                @if (!$found)
                                                    <tr>
                                                        <td>{{ $month }}</td>
                                                        <td>{{ $monthTransaction <= count($groupedData) ? '0' : '-' }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="page-break"></div>
                        <div class="employee-record-detail">
                            <div class="column">
                                <div class="column-table">
                                    <div>
                                        <p>Paid Leaves</p>
                                        <table class="styled-table">
                                            <thead>
                                                <tr>
                                                    <th>Code Description</th>
                                                    <th>Code</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($PaidLeaves as $pl)
                                                    <tr>
                                                        <td>{{ $pl->Description }}</td>
                                                        <td>{{ $pl->Code }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="column">
                                <div class="column-table">
                                    <div>
                                        <p>Non Paid Leaves</p>
                                        <table class="styled-table">
                                            <thead>
                                                <tr>
                                                    <th>Code Description</th>
                                                    <th>Code</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($NonPaidLeaves as $npl)
                                                    <tr>
                                                        <td>{{ $npl->Description }}</td>
                                                        <td>{{ $npl->Code }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
