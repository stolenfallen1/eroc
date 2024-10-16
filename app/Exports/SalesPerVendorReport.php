<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\MMIS\inventory\VwSalesPerVendor;

class SalesPerVendorReport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithCustomStartCell
{
    protected $from;
    protected $to;
    protected $vendor;
    protected $customerType;
    protected $branch;

    public function __construct($from, $to, $vendor, $customerType, $branch)
    {
        $this->from = $from;
        $this->to = $to;
        $this->vendor = $vendor;
        $this->customerType = $customerType;
        $this->branch = $branch;
    }

    public function collection()
    {
        $query = VwSalesPerVendor::query()
            ->select([
                'branch_id',
                'payment_date',
                'oscaIDno',
                'customerName',
                'invoiceNo',
                'itemID',
                'itemdescription',
                'order_item_qty',
                'order_item_cash_price',
                'vatExclude',
                'grossAmountVatEx',
                'seniorDiscount',
                'manufactureDiscount'
            ]);

        if ($this->from && $this->to) {
            $query->whereBetween('payment_date', [$this->from, $this->to]);
        }

        if ($this->vendor) {
            $query->where('vendorID', $this->vendor);
        }

        if ($this->customerType) {
            $query->where('customerType', $this->customerType);
        }

        return $query->get();
    }

    public function startCell(): string
    {
        // Start after the custom headers (Account Code, Account Name, CDS)
        return 'A5'; // Start the main table from row 4
    }
    public function headings(): array
    {
        return [
            'Store Branch',
            'Date',
            'OSCAID No.',
            'Customer Name',
            'Receipt No.',
            'Item Code',
            'Product Name',
            'Qty Sold',
            "Retailer's Unit Price (VAT Inc)",
            "Retailer's Unit Price (VAT Ex)",
            'Gross Amount (VAT Ex)',
            '20% Senior Citizen Discount',
            '70% Manufacture Share/Unilab Share',
        ];
    }
    public function map($item): array
    {
        return [
            $this->branch->abbreviation, // Assuming 'store_branch' is the column for Store Branch / Hospital
            $item->payment_date, // Assuming 'payment_date' is the column for Date / Render Date
            $item->oscaIDno, // Assuming 'osca_id' is the column for OSCA ID No.
            $item->customerName, // Assuming 'customer_name' is the column for Customer Name
            $item->invoiceNo, // Assuming 'receipt_no' is the column for Receipt No.
            $item->itemID, // Assuming 'item_code' is the column for Item Code
            $item->itemdescription, // Assuming 'product_name' is the column for Product Name
            $item->order_item_qty, // Assuming 'qty_sold' is the column for Qty Sold
            round((float) $item->order_item_cash_price, 4), // Retailer's Unit Price (VAT Inc), rounded to 4 decimal places
            round((float) $item->vatExclude, 4), // Retailer's Unit Price (VAT Ex), rounded to 4 decimal places
            round((float) $item->grossAmountVatEx, 4), // Gross Amount (VAT Ex), rounded to 4 decimal places
            round((float) $item->seniorDiscount, 4), // 20% Senior Citizen Discount, rounded to 4 decimal places
            round((float) $item->manufactureDiscount, 4), // 70% Manufacture Share/Unilab Share, rounded to 4 decimal places
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Store Branch / Hospital
            'B' => 13, // Date / Render Date
            'C' => 13, // Senior Citizen / OSCA ID No.
            'D' => 16, // Senior Citizen Name / Patient Name
            'E' => 12, // Receipt No. / Document No. / Bill No. For Hospital
            'F' => 10, // Item Code / or Unilab Item Code
            'G' => 27, // Product Name
            'H' => 8, // Qty Sold
            'I' => 27, // Retailer's Unit Price (VAT Inc)
            'J' => 27, // Retailer's Unit Price (VAT Ex)
            'K' => 27, // Gross Amount (VAT Ex)
            'L' => 30, // 20% Senior Citizen Discount
            'M' => 35, // 70% Manufacture Share/Unilab Share
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set the additional header rows
        $sheet->mergeCells('B1:E1');
        $sheet->mergeCells('B2:E2');
        $sheet->mergeCells('B3:E3');
        $sheet->setCellValue('A1', 'ACCOUNT CODE');
        $sheet->setCellValue('B1', $this->branch->id);
        $sheet->setCellValue('A2', 'ACCOUNT NAME');
        $sheet->setCellValue('B2', $this->branch->name);
        $sheet->setCellValue('A3', 'CDS');
        $sheet->setCellValue('B3', '');
        $sheet->setCellValue('K3', 'QTY * Retailers Unit Price (VAT Ex)');
        $sheet->setCellValue('L3', ' * 20% of Gross Amount (Vat ex)');
        $sheet->setCellValue('M3', ' *70% of 20% SCD');

        // Get the highest row to calculate the total of column M
        $highestRow = $sheet->getHighestRow();

        // Initialize total for column M
        $totalManufactureShare = 0;

        // Loop through each row and sum only numeric values in column M
        for ($row = 5; $row <= $highestRow; $row++) {
            $cellValue = $sheet->getCell('M' . $row)->getValue();

            // Check if the cell value is numeric
            if (is_numeric($cellValue)) {
                $totalManufactureShare += $cellValue;
            }
        }


        $sheet->getStyle('A1:M1')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                ],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFFFF'], // Light yellow background
            ],
        ]);
        $sheet->getStyle('A2:M3')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                ],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFFFF'], // Light yellow background
            ],
        ]);
        $sheet->getStyle('A4:M4')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                ],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFFFF'], // Light yellow background
            ],
        ]);

        // Set the total in B3
        // $sheet->setCellValue('A3', 'CDS:');
        $sheet->setCellValue('M4', $totalManufactureShare);
        // Apply style to the first three rows (custom headers)
        $sheet->getStyle('A1:A3')->applyFromArray([
           'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => '4F81BD']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);
        $sheet->getStyle('B2')->applyFromArray([
            'font' => [
                'bold' => true, 
                'size' =>16,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT, // Align text to the left
            ],
        ]);
       
        // Optionally, you can apply styles to the merged cell
        $sheet->getStyle('B1:E1')->applyFromArray([
            'font' => [
                'size' => 14, // Size as an integer
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, // Align text to the left
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        // Optionally, you caE apply styles to the merged cell
        $sheet->getStyle('B2:E2')->applyFromArray([
            'font' => [
                'size' => 14, // Size as an integer
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, // Align text to the left
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        // Optionally, you can apply styles to the merged cell
        $sheet->getStyle('B3:E3')->applyFromArray([
            'font' => [
                'size' => 14, // Size as an integer
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, // Align text to the left
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);
        
        $sheet->getStyle('K3:M3')->applyFromArray([
            'font' => [
                'bold' => true, 
                'color' => ['argb' => 'FF0000']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT, // Align text to the left
            ],
        ]);

        $sheet->getStyle('M4')->applyFromArray([
            'font' => [
                'bold' => true, 
                'size' =>14,
                'color' => ['argb' => 'FF0000']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT, // Align text to the left
            ],
        ]);
        // Set the header row style
        $sheet->getStyle('A5:M5')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => '4F81BD']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        // Set the row height for the header row
        $sheet->getRowDimension(5)->setRowHeight(30);

        // Apply border to all rows
        $highestRow = $sheet->getHighestRow(); // Get the last row with data
        $sheet->getStyle('A5:M' . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);
        // Set background color for column K (Gross Amount VAT Ex)
        $sheet->getStyle('J6:J' . $highestRow)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFF00'], // Light yellow background
            ],
        ]);
    }
}
