<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Income Report</title>
        <style>
            body {
                font-family: 'DejaVu Sans', sans-serif !important;
                margin: 0;
            }
        </style>
    </head>
    <body>
        <header style="width: 100%">
            <div style="display: inline-block; vertical-align: middle;">
                <p style="margin: 0; margin-bottom: 5px; font-weight: bold; font-size: 19px;">CEBU DOCTORS UNIVERSITY HOSPITAL, INC.</p>
                <p style="margin: 0; font-weight: 500; font-size: 16px;">Osmeña Blvd, Cebu City, 6000 Cebu</p>
                <p style="margin: 0; font-weight: 500; font-size: 16px;">TIN: 000-309-908-000-NV</p>
            </div>
    
            <div style="display: inline-block; vertical-align: middle; margin: 25px 0 0 25px;">
                <img src="../public/images/cdg_logo.png" alt="CDG Logo" height="80" width="90" />
                <img src="../public/images/cduh_logo.png" alt="CDUH Logo" height="80" width="90" />
            </div>
        </header>

        <section style="text-align: center; margin-top 0; padding: 0;">
            <span style="font-weight: bold; font-style: italic; font-size: 16px;">{{ $pdf_data['title'] }}</span><br/>
            <span style="font-weight: 500; font-style: italic; font-size: 16px;">{{ $pdf_data['sub_title'] . ' (' . $pdf_data['report_identifier'] . ')' }}</span>
        </section>

        <div style="margin-top: 8px;">
            <table style="width: 100%; border-collapse: collapse; margin: 2px; text-align: left;">
                <thead style="border-bottom: 1px solid #A9A9A9; border-top: 1px solid #A9A9A9;">
                    <tr>
                        <th style="font-size: 11px; width: 10%">REF NO.</th>
                        <th style="font-size: 11px; width: 10%;">CASE NO.</th>
                        <th style="font-size: 11px; width: 30%;">PATIENT NAME</th>
                        <th style="font-size: 11px; width: 30%;">SUPPLY NAME</th>
                        <th style="font-size: 11px; width: 10%;">QUANTITY</th>
                        <th style="font-size: 11px; width: 10%">AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pdf_data['transactions'] as $transaction)                            
                        <tr style="border-bottom: 1px solid #A9A9A9;">
                            <td style="font-size: 11px;">{{ $transaction->referenceNum }}</td>
                            <td style="font-size: 11px;">{{ $transaction->case_No }}</td>
                            <td style="font-size: 11px;">{{ $transaction->patient_Name }}</td>
                            <td style="font-size: 11px;">{{ $transaction->item_description }}</td>
                            <td style="font-size: 11px;">
                                {{ (int)$transaction->quantity < 0 ? '(' . number_format(abs($transaction->quantity), 0) . ')' : number_format($transaction->quantity, 0) }}
                            </td> 
                            <td style="font-size: 11px;">
                                {{ (float)$transaction->amount < 0 ? '(' . $pdf_data['currency'] . ' ' . number_format(abs($transaction->amount), 2) . ')' : $pdf_data['currency'] . ' ' . number_format($transaction->amount, 2) }}
                            </td>                              
                        </tr>
                    @endforeach

                    <tr style="font-weight: bold; border-top: 1px solid #A9A9A9;">
                        <td colspan="6" style="font-size: 13px; text-align: right;">
                            Grand Total: 
                            <span style="border-bottom: 1px solid #000;">
                                {{ 
                                    $pdf_data['currency'] . ' ' . number_format(
                                        $pdf_data['transactions']->filter(function ($transaction) {
                                            return floatval($transaction->amount) > 0; 
                                        })->sum('amount'), 
                                        2
                                    ) 
                                }}
                            </span>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <footer>
            <p style="margin: 0; margin-top: 15px; font-weight: bold; font-size: 13px;">Total Posted:
                <span style="border-bottom: 1px solid #000;">
                    {{ $pdf_data['totalPostedCount'] }}
                </span>
            </p>
            <p style="margin: 0; font-weight: bold; font-size: 13px;">Total Returned:
                <span style="border-bottom: 1px solid #000;">
                    {{ $pdf_data['totalReturnedCount'] }}
                </span>
            </p> 
            <p style="margin: 0; font-weight: bold; font-size: 13px;">Printed by: 
                <span style="border-bottom: 1px solid #000;">
                    {{ $pdf_data['printed_By'] }}
                </span>
            </p>
            <p style="margin: 0; font-weight: bold; font-size: 13px;">Print Date: 
                {{ now()->format('Y-m-d') }}
            </p>
        </footer>

    </body>
    <div>
    </div>
</html>