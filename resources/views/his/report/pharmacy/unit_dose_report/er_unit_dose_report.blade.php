<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Patient Unit Dose Report</title>
        <style>
            body {
                font-family: 'DejaVu Sans', sans-serif !important;
                margin: 0;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            th, td {
                font-size: 12px;
                text-align: left;
                padding: 5px;
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
        
        <section style="text-align: center; margin-top: 20px;">
            <span style="font-weight: bold; font-style: italic; font-size: 16px;">{{ $pdf_data['title'] }}</span><br />
            <span style="font-weight: 500; font-style: italic; font-size: 16px;">{{ $pdf_data['sub_title'] . '( Emergency )' }}</span>
        </section>
        
        <main>
            @foreach($pdf_data['transactions'] as $data)
                <table>
                    <thead>
                        <tr>
                            <th style="background-color: #f2f2f2; font-weight: bold; width: 40%;">Patient Name</th>
                            <th style="background-color: #f2f2f2; font-weight: bold; width: 15%;">Patient ID</th>
                            <th style="background-color: #f2f2f2; font-weight: bold; width: 35%;">Case No</th>
                            <th style="background-color: #f2f2f2; font-weight: bold; width: 10%;">Bed No.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $data['patient_Name'] }}</td>
                            <td>{{ $data['patient_Id'] }}</td>
                            <td>{{ $data['case_No'] }}</td>
                            <td>{{ $data['er_Bedno'] }}</td> 
                        </tr>
                    </tbody>
                </table>

                <table>
                    <thead>
                        <tr>
                            <th style="font-style: italic; font-size: 14px; font-weight: 500; width: 40%;">Item Description</th>
                            <th style="font-style: italic; font-size: 14px; font-weight: 500; width: 15%;">Dosage ID</th>
                            <th style="font-style: italic; font-size: 14px; font-weight: 500; width: 35%;">Dosage Description</th>
                            <th style="width: 10%;"></th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach ($data['nurse_logbook'] as $item)
                            <tr>
                                <td>{{ $item['description'] }}</td>
                                <td>{{ $item['dosage_id'] }}</td>
                                <td>{{ $item['dosage_description'] }}</td>
                                <td></td>
                            </tr>
                        @endforeach

                    </tbody>
                </table>
                <hr style="border-top: 1px dashed #ccc; margin-top: 10px;"/>
            @endforeach        
        </main>
    </body>
</html>
