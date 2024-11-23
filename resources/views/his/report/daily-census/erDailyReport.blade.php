<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<style>
    .container {
        margin-top: 1rem;
        margin-left: 1rem;
        margin-bottom: 1rem;
        margin-right: 1rem;
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

    .header-column {
        text-align: left;
        text-transform: uppercase;
        font-size : 12px;
        line-height: 11px;
    }
    .left-text {
        text-align: left;
        text-transform: uppercase;
        font-size : 14px;
        width: 100%;
    }

    .right-text {
        text-align: left;
        padding-left: 45%;
        font-size: 14px;
    }
</style>
<body>
    @php 
        $time = $run_time;
        $date = $run_date;
        $reports = $dailyReport;
        $grandTotal = $grand_total;
        $main_header = function() use ($time, $date) {
            return "
                <div class='header'>
                    <div class='header-column' style='width: 50%';>
                        <p class='left-text'>
                            Cebu Doctors University Hospital.Inc 
                        </p>
                        <p class='left-text'>
                            Census Report From Er Department
                        </p>
                        <p class='left-text'>
                            Report Date: 
                            <span>{$date}</span>
                        </p>
                    </div>
                    <div class='header-column' style='width: 48%'>
                        <p class='right-text'>Run Date : <span>{$date}</span></p>
                        <p class='right-text'>Run Time : <span>{$time}</span></p>
                        <p class='right-text'> Page : </p>
                    </div>
                </div>
            ";
        };

        $main_content = function() use($reports){
            $rows = '';
            $count = 1;
            foreach($reports as $report) {
                $rows .= "
                    <tr>
                        <td  style='text-align: left;'>{$report['bed_No']}</td>
                        <td  style='text-align: left;'>{$report['admission_date']}</td>
                        <td  style='text-align: left;'>{$report['name_prefix']}. {$report['patient_name']}</td>
                        <td  style='text-align: left;'>{$report['admission_No']}</td>
                        <td  style='text-align: left;'>{$report['attending_doctor']}</td>
                    </tr>
                ";
                $count++;
            }
            return $rows;
        };

    @endphp
    <div class="container">

        {!! $main_header() !!}

        <table style="width: 100%">
            <tr>
                <th>Bed No.</th>
                <th>Adm.Date</th>
                <th>Name of Patient</th>
                <th>Adm.No,</th>
                <th>Attending Doctor</th>
            </tr>
            <tbody style="border-top: 2px solid #000">
                {!! $main_content() !!}
            </tbody>
            <tbody  style="border-top: 2px solid #000">
                <tr>
                    <td>
                        Grand Total : {{$grandTotal}}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
   
</body>
</html>