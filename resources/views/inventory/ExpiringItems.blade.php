<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Expired Items</title>
    <style>
        .header-section {
            width: 100%;
            position: relative;
        }

        .header-text {
            position: absolute;
            text-align: center;
            margin-top: -20px;
            width: 69%;
        }

        .qr-code {
            margin-left: 485px;
        }

        .title-section {
            width: 100%;
            text-align: center;
        }

        .info-section {
            width: 100%;
        }

        .item-section td,
        th {
            border: 1px solid;
        }

        .item-section {
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
        }

        h3 {
            font-weight: normal;
            letter-spacing: 2px;
        }

        h5 {
            font-weight: normal;
            letter-spacing: 1px;
        }

        .left-width {
            width: 100px;
        }

        .right-width {
            width: 80px;
        }

        .mid-width {
            width: 370px;
        }

        .underline {
            border-bottom: 1px black solid;
        }

        td {
            font-size: 12px;
        }

        th {
            font-size: 12px;
        }

        .item-td {
            text-align: center;
        }

        .spacer {
            margin-top: 1px;
            width: 100%;
            border-bottom: 2px solid;
        }

        .note {
            font-style: italic;
            font-size: 12px;
        }

        .reminder {
            font-weight: 300;
        }

        .signatory-section1 {
            margin-top: 20px;
            float: left;
        }

        .signatory-section2 {
            float: right;
        }

        .comptroller {
            padding-top: 25px !important;
        }

        .csstransforms {
            position: absolute;
            top: 200;
            left: 100;
            color: rgb(234, 223, 223);
            transform: rotate(330deg);
            text-transform: uppercase;
            font-size: 32px;
            z-index: -10;
        }
    </style>
</head>

<body>
    <div class="header-section">
        <!-- <img src="{{ $pdf_data['logo'] }}" alt="Example Image" width="100" height="100">
        <div class="header-text">
            <h3>a</h3>
            <h5 style="margin: -20px !important;">a</h5>
            <h5>TIN </h5>
        </div>
        <img class="qr-code" src="{{ $pdf_data['qr'] }}" alt="Example Image" width="100" height="100"> -->
    </div>
    <div></div>
</body>

</html>