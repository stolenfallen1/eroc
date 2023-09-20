<?php

namespace App\Exports;

use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Warehouseitems;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class WarehouseItemExport implements FromQuery, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $start_date;
    protected $end_date;
    protected $department_id;
    protected $branch_id;
    public function __construct($department, $branch_id, $start_date, $end_date)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->department_id = $department;
        $this->branch_id = $branch_id;
    }
    public function query()
    {
        return Warehouseitems::query()->with('itemMaster', 'unit')->where('warehouse_Id', $this->department_id)->where('branch_id', $this->branch_id);
    }

    public function map($warehouse_item): array
    {
        return [
            // "test",
            // "test",
            // "test",
            // "test",
            // "test",
            // "test",
            $warehouse_item->item_Id,
            $warehouse_item->itemMaster?$warehouse_item->itemMaster->item_name:'...',
           'A',
            $warehouse_item->unit? $warehouse_item->unit->name:'...',
            $warehouse_item->item_ListCost,
            $warehouse_item->item_OnHand,
        ];
    }

    public function headings(): array
    {
        return  $columns = [
            'Code',
            'Product name',
            'Status',
            'Unit',
            'List cost',
            'Onhand',
        ];
    }
}
