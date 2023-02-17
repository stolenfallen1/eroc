<?php

namespace Database\Seeders;

use App\Models\BuildFile\Itemmasters;
use Illuminate\Database\Seeder;

class ItemMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Itemmasters::create([
            'item_Name'	=> 'test1',
            'item_Description'	=> 'this is a test',
            'item_Brand_Id'	=> 1,
            'item_Manufacturer_Id'	=> 1,
            'item_specsification'	=> 'test',
            'item_SKU'	=> 'test1s',
            'item_UnitOfMeasure_Id' => 1,	
            'item_InventoryGroup_Id' => 1,
            'item_Category_Id'	=> 1,
            'item_SubCategory_Id'	=> 1,
            'item_Remarks'	=> 'test',
            'isSupplies' => 1,
            'isActive' => 1
        ]);
    }
}
