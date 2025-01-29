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
                            <th>Year</th>
                            <th>Month</th>
                            <th>Department</th>
                            <th>Total Absences</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($absences as $absence)
                            <tr>
                                <td>{{ $absence->year }}</td>
                                <td>{{ $absence->month }}</td>
                                <td>{{ $absence->department }}</td>
                                <td>{{ $absence->total_absences }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </body>
</html>
