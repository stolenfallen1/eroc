<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockRequisitionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_requisitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transfer_by_id');
            $table->unsignedBigInteger('receiver_id')->nullable();
            $table->unsignedBigInteger('sender_warehouse_id');
            $table->unsignedBigInteger('receiver_warehouse_id');
            // $table->unsignedBigInteger('warehouse_item_id');
            // $table->unsignedBigInteger('item_id');
            // $table->double('quantity');
            // $table->unsignedBigInteger('department_head_approved_by')->nullable();
            // $table->dateTime('department_head_approved_date')->nullable();
            // $table->unsignedBigInteger('department_head_declined_by')->nullable();
            // $table->dateTime('department_head_declined_date')->nullable();
            // $table->longText('department_head_declined_remarks')->nullable();
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
        Schema::dropIfExists('stock_requisitions');
    }
}
