<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockRequisitionItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_requisition_items', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->unsignedBigInteger('stock_requisition_id');
            $table->unsignedBigInteger('warehouse_item_id');
            $table->unsignedBigInteger('item_id');
            $table->double('quantity');
            $table->unsignedBigInteger('unit_id');

            $table->unsignedBigInteger('department_head_approved_by')->nullable();
            $table->dateTime('department_head_approved_date')->nullable();
            $table->unsignedBigInteger('department_head_declined_by')->nullable();
            $table->dateTime('department_head_declined_date')->nullable();
            $table->longText('department_head_declined_remarks')->nullable();
            
            $table->unsignedBigInteger('administrator_approved_by')->nullable();
            $table->dateTime('administrator_approved_date')->nullable();
            $table->unsignedBigInteger('administrator_declined_by')->nullable();
            $table->dateTime('administrator_declined_date')->nullable();
            $table->longText('administrator_declined_remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_requisition_items');
    }
}
