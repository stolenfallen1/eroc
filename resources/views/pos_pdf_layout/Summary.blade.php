<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Summary Report</title>
    <style>
        body{
            font-family: arial, sans-serif;
        }
        .header-section {
            
            font-family: arial, sans-serif;
            display: block;
            width: 100% !important;
            margin-bottom: 2%;
            font-size: 1.2em !important;

            @if ($print_layout == '2')
                font-size: 0.5em !important;
            @endif
        }

        .company-details {
            width: 100% !important;
            text-align: center !important;
        }

        table {
            
            font-family: arial, sans-serif;
            border-collapse: collapse;
            width: 100%;

            @if ($print_layout == '2')
                font-size: 0.5em !important;
            @endif
        }

        .content-section {
            width: 100%;
        }

        table>thead>tr>th {
            border-top: 1px solid black;
            border-bottom: 1px solid black;
            text-align: left;
        }

        table>tbody>tr>td {
            text-transform: initial !important;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right !important;
        }

        .dflex {
            display: inline-block;
            width: 100%;
        }

        .flex {
            width: 45% !important;
            display: inline-block;
        }

        .flex-50 {
            width: 30% !important;
            display: inline-block;
        }

        .flex-80 {
            width: 60% !important;
            display: inline-block;
        }

        .flex-10 {
            width: 40% !important;
            display: inline-block;
        }

        .flex-5 {
            width: 30% !important;
            display: inline-block;
        }

        @page {
            margin: 15px !important;
            width: 100%;
            font-size: 14px !important;
        }
        .denominationtable{
            font-family: arial, sans-serif;
            border-collapse: collapse;
            width: 100%;
        }
        .denominationtable td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 4px;
        }
        .denominationtable th {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 2px;
        }
    </style>
</head>

<body>
    <div class="header-section">
        <div class="company-details">
            <div class="company-name">{{ $possetting->company_name }}</div>
            <div class="company-address">{{ $possetting->company_address_bldg }}
                {{ $possetting->company_address_streetno }}</div>
            <div class="company-address">
                <h4 style="margin: 15px 0px 0px 0px;">SUMMARY OF DAILY CASH & CHECKS ACCOUNTABILITY	</h4>
            </div>
        </div>
    </div>
    <div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 68.5% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                    <div class="text-right">Terminal ID</div>
                </div>
                <div style="width: 58.5% !important; display:inline-block;">
                    <div class="">: {{ $terminalid }}</div>
                </div>
            </div>
            <div style="width: 29.5% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                    <div class="text-right">Print Date</div>
                </div>
                <div style="width: 58.5% !important; display:inline-block;">
                    <div class="">: {{ $printdate }}</div>
                </div>
            </div>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 68.5% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                    <div class="text-right">Prepared By</div>
                </div>
                <div style="width: 58.5% !important; display:inline-block;">
                    <div class="">: {{ $cashiername }}</div>
                </div>
            </div>
            <div style="width: 29.5% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                    <div class="text-right">Print Time</div>
                </div>
                <div style="width: 58.5% !important; display:inline-block;">
                   <div class="">: {{ $printtime }}</div>
                </div>
            </div>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 68.5% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                    <div class="text-right">User ID</div>
                </div>
                <div style="width: 58.5% !important; display:inline-block;">
                    <div class="">: {{ $userid }}</div>
                </div>
            </div>
            <div style="width: 29.5% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                </div>
                <div style="width: 58.5% !important; display:inline-block;">
                </div>
            </div>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 68.5% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                    <div class="text-right">Shift ID</div>
                </div>
                <div style="width: 58.5% !important; display:inline-block;">
                    <div class="">: {{ $shift }}</div>
                </div>
            </div>
            <div style="width: 29.5% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                </div>
                <div style="width: 58.5% !important; display:inline-block;">
                </div>
            </div>
        </div>
        <div class="dflex" style="width: 100% !important;">
            <div style="width: 68.5% !important;display:inline-block;">
                <div style="width: 30% !important; display:inline-block;">
                    <div class="text-right">Transaction Date</div>
                </div>
                <div style="width: 58.5% !important; display:inline-block;">
                    <div class="">:{{ $transdate }}</div>
                </div>
            </div>

        </div>
    </div>
    <div class="content-section">

        <table class="denominationtable">
            <thead>
                <tr>
                    <th width="150">Bancknote / Coin</th>
                    <th width="150">Qty</th>
                    <th width="150">Amount</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $amount_1000 = 1000;
                    $amount_500 = 500;
                    $amount_200 = 200;
                    $amount_100 = 100;
                    $amount_50 = 50;
                    $amount_20 = 20;
                    $amount_10 = 10;
                    $amount_5 = 5;
                    $amount_1 = 1;
                    $amount_025 = 0.25;
                    $amount_001 = 0.01;
                @endphp
                <tr>
                    <td class="text-center">&nbsp;&nbsp;&nbsp;&nbsp;{{number_format($amount_1000,2)}}</td>
                    <td>{{ $cashonhanddetails ? (int)$cashonhanddetails['cashonhand_details']['denomination_1000'] : '0'}}</td>
                    <td>{{ $cashonhanddetails ? number_format(($amount_1000 * $cashonhanddetails['cashonhand_details']['denomination_1000']),2) : '0'}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">&nbsp;&nbsp;&nbsp;&nbsp;{{number_format($amount_500,2)}}</td>
                    <td>{{ $cashonhanddetails ? (int)$cashonhanddetails['cashonhand_details']['denomination_500'] : '0'}}</td>
                    <td>{{ $cashonhanddetails ? number_format(($amount_500 * $cashonhanddetails['cashonhand_details']['denomination_500']),2) : '0'}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">&nbsp;&nbsp;&nbsp;&nbsp;{{number_format($amount_200,2)}}</td>
                    <td>{{ $cashonhanddetails ? (int)$cashonhanddetails['cashonhand_details']['denomination_200'] : '0'}}</td>
                    <td>{{ $cashonhanddetails ? number_format(($amount_200 * $cashonhanddetails['cashonhand_details']['denomination_200']),2) : '0'}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">&nbsp;&nbsp;&nbsp;&nbsp;{{number_format($amount_100,2)}}</td>
                    <td>{{ $cashonhanddetails ? (int)$cashonhanddetails['cashonhand_details']['denomination_100'] : '0'}}</td>
                    <td>{{ $cashonhanddetails ? number_format(($amount_100 * $cashonhanddetails['cashonhand_details']['denomination_100']),2) : '0'}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">&nbsp;&nbsp;&nbsp;&nbsp;{{number_format($amount_50,2)}}</td>
                    <td>{{ $cashonhanddetails ? (int)$cashonhanddetails['cashonhand_details']['denomination_50'] : '0'}}</td>
                    <td>{{ $cashonhanddetails ? number_format(($amount_50 * $cashonhanddetails['cashonhand_details']['denomination_50']),2) : '0'}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">&nbsp;&nbsp;&nbsp;&nbsp;{{number_format($amount_20,2)}}</td>
                    <td>{{ $cashonhanddetails ? (int)$cashonhanddetails['cashonhand_details']['denomination_20'] : '0'}}</td>
                    <td>{{ $cashonhanddetails ? number_format(($amount_20 * $cashonhanddetails['cashonhand_details']['denomination_20']),2) : '0'}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">&nbsp;&nbsp;&nbsp;&nbsp;{{number_format($amount_10,2)}}</td>
                    <td>{{ $cashonhanddetails ? (int)$cashonhanddetails['cashonhand_details']['denomination_10'] : '0'}}</td>
                    <td>{{ $cashonhanddetails ? number_format(($amount_10 * $cashonhanddetails['cashonhand_details']['denomination_10']),2) : '0'}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">&nbsp;&nbsp;&nbsp;&nbsp;{{number_format($amount_5,2)}}</td>
                    <td>{{ $cashonhanddetails ? (int)$cashonhanddetails['cashonhand_details']['denomination_5'] : '0'}}</td>
                    <td>{{ $cashonhanddetails ? number_format(($amount_5 * $cashonhanddetails['cashonhand_details']['denomination_5']),2) : '0'}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">&nbsp;&nbsp;&nbsp;&nbsp;{{number_format($amount_1,2)}}</td>
                    <td>{{ $cashonhanddetails ? (int)$cashonhanddetails['cashonhand_details']['denomination_1'] : '0'}}</td>
                    <td>{{ $cashonhanddetails ? number_format(($amount_1 * $cashonhanddetails['cashonhand_details']['denomination_1']),2) : '0'}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">&nbsp;&nbsp;&nbsp;&nbsp;{{number_format($amount_025,2)}}</td>
                    <td>{{ $cashonhanddetails ? (int)$cashonhanddetails['cashonhand_details']['denomination_dot25'] : '0'}}</td>
                    <td>{{ $cashonhanddetails ? number_format(($amount_025 * $cashonhanddetails['cashonhand_details']['denomination_dot25']),2) : '0'}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">&nbsp;&nbsp;&nbsp;&nbsp;{{number_format($amount_001,2)}}</td>
                    <td>{{ $cashonhanddetails ? (int)$cashonhanddetails['cashonhand_details']['denomination_dot15'] : '0'}}</td>
                    <td>{{ $cashonhanddetails ? number_format(($amount_001 * $cashonhanddetails['cashonhand_details']['denomination_dot15']),2) : '0'}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td  class="text-right" colspan="2">TOTAL CASH</td>
                    <td>{{ $cashonhanddetails ? number_format(($cashonhanddetails['cashonhand_details']['denomination_total']),2) : '0'}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td  class="text-right" colspan="2">TOTAL CHECKS</td>
                    <td>{{ $cashonhanddetails ? number_format(($cashonhanddetails['cashonhand_details']['denomination_checks_total_amount']),2) : '0'}}<</td>
                    <td></td>
                </tr>
                <tr>
                    <td  class="text-right" colspan="2">TOTAL</td>
                    <td>{{ $cashonhanddetails ? number_format(($cashonhanddetails['cashonhand_total_collection_amount']),2) : '0'}}<</td>
                    <td></td>
                </tr>
                <tr>
                    <td  class="text-right" colspan="2">TOTAL COLLECTION FOR THE DAYTAL</td>
                    <td>{{ $cashonhanddetails ? number_format(($cashonhanddetails['cashonhand_total_collection_amount']),2) : '0'}}<</td>
                    <td></td>
                </tr>
                <tr>
                    <td  class="text-right" colspan="2">LESS: COLLECTED CARD PAYMENTS</td>
                    <td>{{ $cashonhanddetails ? number_format(($cashonhanddetails['cashonhand_less_collected_card_amount']),2) : '0'}}<</td>
                    <td></td>
                </tr>
                <tr>
                    <td  class="text-right" colspan="2">NET COLLECTIONS FOR THE DAY</td>
                    <td>{{ $cashonhanddetails ? number_format(($cashonhanddetails['cashonhand_net_collections_for_the_day']),2) : '0'}}<</td>
                    <td></td>
                </tr>
                <tr>
                    <td  class="text-right" colspan="2">ADD: UNCOLLECTED CARD PAYMENTS</td>
                    <td>{{ $cashonhanddetails ? number_format(($cashonhanddetails['cashonhand_add_uncollected_card_day']),2) : '0'}}<</td>
                    <td></td>
                </tr>
                <tr>
                    <td  class="text-right" colspan="2">TOTAL TENDERED PAYMENTS</td>
                    <td>{{ $cashonhanddetails ? number_format(($cashonhanddetails['cashonhand_total_tendered_amount']),2) : '0'}}<</td>
                    <td></td>
                </tr>
                <tr>
                    <td  class="text-right" colspan="2">TOTAL COLLECTIONS FOR THE DAY</td>
                    <td>{{ $cashonhanddetails ? number_format(($cashonhanddetails['cashonhand_total_collection_amount']),2) : '0'}}<</td>
                    <td></td>
                </tr>
                <tr>
                    <td  class="text-right" colspan="2">OVERAGE/ (SHORTAGES)</td>
                    <td>{{ $cashonhanddetails ? number_format(($cashonhanddetails['cashonhand_overage_shortages']),2) : '0'}}<</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        <br>
       
    </div>
</body>

</html>
