<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PDF;

class SOAController extends Controller
{
    //  

    public function createStatmentOfAccount() {

        $data = ['test'];
        $filename   = 'Statement_of_account';
        $html       = view('his.report.soa.statement_of_account', $data)->render();
        $pdf        = PDF::loadHTML($html)->setPaper('letter', 'portrait');

        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->get_font("Montserrat", "normal");
        $dompdf->get_canvas()->page_text(750, 575, "{PAGE_NUM} / {PAGE_COUNT}", $font, 10, array(0, 0, 0));

        $currentDateTime = \Carbon\Carbon::now()->format('Y-m-d g:i A');
        $dompdf->get_canvas()->page_text(35, 750, $currentDateTime, $font, 10, array(0, 0, 0));
        return $pdf->stream($filename . '.pdf');
    }
}
