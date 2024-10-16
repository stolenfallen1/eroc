<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Subsidiary Ledger</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .border-bottom-none {
            border-bottom: none !important;
        }

        .border-top-none {
            border-top: none !important;
        }

        .border-none {
            border: none !important;
        }

        .border-bottom {
            border-bottom: 0.5px solid !important;
        }

        .double-underline {
            text-decoration: underline;
            text-decoration-style: double;
        }

        .font-md {
            font-size: 9px !important;
        }

        .font-sm {
            font-size: 8px !important;
        }

        .description {
            width: 60% !important;
        }

        .itemid {
            width: 10% !important;
        }

        .item-width {
            width: 8% !important;
        }

        @page {
            width: 100%;
            margin: 10% !important;
        }

        table {
            border-collapse: collapse;
            /* Prevent double borders */
        }

        th,
        td {
            padding: 4px;
            /* Padding for th and td */
            vertical-align: top;
            /* Align text to the top */
        }
    </style>
</head>

<body>
    <table>
        <tr>
            <td colspan="6" class="text-center">{{$pdf_data['branch']['name']}}</td>
        </tr>
        <tr>
            <td colspan="6" class="text-center font-md">{{$pdf_data['branch']['address']}}</td>
        </tr>
        <tr>
            <td colspan="6" class="text-center font-sm">TIN {{$pdf_data['branch']['TIN']}}</td>
        </tr>
    </table>
    <table>
        <thead>
            <tr>
                <th style="border-bottom: 1px dotted #000;" class="font-md text-center itemid">ITEMID</th>
                <th style="border-bottom: 1px dotted #000;" class="font-md description">DESCRIPTION</th>
                <th style="border-bottom: 1px dotted #000;" class="font-md item-width">LIST COST</th>
                <th style="border-bottom: 1px dotted #000;" class="font-md text-center item-width">PACKING</th>
                <th style="border-bottom: 1px dotted #000;" class="font-md text-center item-width">OUT</th>
                <th style="border-bottom: 1px dotted #000;" class="font-md text-center item-width">IN</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pdf_data['items'] as $item)
            <tr>
                <td class="font-md itemid" style="padding:15px;">
                    {{ $item->id }}
                </td>
                <td class="font-md description">{{ $item->itemdescription }}</td>
                <td class="font-md item-width">{{ number_format($item->Listcost,2) }}</td>
                <td class="font-md item-width text-center">{{ $item->Packing }}</td>
                <td class="font-md item-width text-center">{{ number_format($item->PriceOut,2) }}</td>
                <td class="font-md item-width text-center">{{ number_format($item->PriceIn,2) }}</td>
            </tr>

            @endforeach
            <tr>
                <td class="font-md" colspan="6"><br></td>
            </tr>

        </tbody>
    </table>
</body>

</html>