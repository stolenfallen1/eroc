<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\BuildFile\Branchs;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\MMIS\PriceList\InventoryPriceListAll;
use App\Models\MMIS\PriceList\InventoryPriceListPerLocation;
use TCPDF; // Import PDF library you are using (like Snappy or DomPDF)

class GeneratePdfReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $location_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($location_id)
    {
        $this->location_id = $location_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

         // Retrieve branch data
        $branch = Branchs::where('id', 1)->first();
        $pdfData = [
             'groupedLocation' => [],
        ];
         InventoryPriceListAll::when($this->location_id, function ($query) {
            return $query->where('LocationID', $this->location_id);
        })->chunk(1000, function ($items) use (&$pdfData) {
            foreach ($items as $item) {
                $locationName = $item->Location;
                $pdfData['groupedLocation'][$locationName][] = $item;
            }
        });
        
        // Group data for the PDF
        $groupedArray = [];
        $warehouse = '';
        $Type = '';
        foreach ($pdfData['groupedLocation'] as $locationName => $items) {
            $groupedByCategory = []; // Reset category grouping for each location
        
            foreach ($items as $item) {
                $LocationID = $item->LocationID;
                $warehouse = $items[0]->Location;
                $CategoryName = $item->CategoryName;
        
                // Initialize category grouping if it doesn't exist
                if (!isset($groupedByCategory[$CategoryName])) {
                    $groupedByCategory[$CategoryName] = [
                        'CategoryName' => $CategoryName,
                        'items' => [],
                    ];
                }
        
                // Add the item to the corresponding category
                $groupedByCategory[$CategoryName]['items'][] = $item;
            }
        
            // Prepare the final structure
            $groupedArray[] = [
                'locationName' => $locationName,
                'categories' => array_values($groupedByCategory), // Use array_values to reindex
            ];
        }
        
        // Optionally, filter out empty categories
        $groupedArray = array_filter($groupedArray, function ($location) {
            return !empty($location['categories']);
        });
      
         // Prepare the final data for the PDF
         $pdf_data = [
             'branch' => $branch,
             'warehouse' => $warehouse,
             'dateFrom' => date('m/d/Y'),
             'dateTo' => date('m/d/Y'),
             'Type' => $Type,
             'groupedLocation' => $groupedArray,
         ];
 
         // Create a new TCPDF instance
         $pdf = new TCPDF();
         $pdf->SetCreator(PDF_CREATOR);
         $pdf->SetAuthor('Your Company');
         $pdf->SetTitle('Inventory Price List');
         $pdf->SetSubject('Price List');
         $pdf->SetKeywords('TCPDF, PDF, price list, inventory');
 
         // Set default header and footer
         $pdf->setPrintHeader(false);
         $pdf->setPrintFooter(false);
         // Set margins (left, top, right)
         $pdf->SetMargins(10, 10, 10,10); // Set left, top, and right margins to 10mm
         $pdf->setCellPadding(1); // Adjust the padding as needed
         // Add a page
         $pdf->AddPage('L');
         // Convert the data into HTML
         $htmlContent = view('reports.price-list.all-location', ['pdf_data'=>$pdf_data])->render();
 
         // Write the HTML content to the PDF
         $pdf->writeHTML($htmlContent, true, false, true, false, '');
 
         // Save the PDF to storage
         $pdfName = 'inventory_.pdf';
         $pdfOutput = $pdf->Output($pdfName, 'S'); // S: Return the document as a string
 

        // Get the public path and store the PDF in the 'public/reports' directory
        $filePath = public_path('reports/' . $pdfName);

        // Store the PDF file in the public directory
        file_put_contents($filePath, $pdfOutput);
 
         // Optionally, notify the user (e.g., send an email with the PDF link)
       
    }
}
