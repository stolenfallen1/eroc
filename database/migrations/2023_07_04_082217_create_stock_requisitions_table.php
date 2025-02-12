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
            $table->id()->autoIncrement();
            $table->string('request_by_id');
            $table->string('receiver_id')->nullable();
            $table->unsignedBigInteger('requester_warehouse_id');
            $table->unsignedBigInteger('requester_branch_id');
            $table->unsignedBigInteger('sender_warehouse_id');
            $table->unsignedBigInteger('sender_branch_id');
            $table->string('transfer_by_id')->nullable();
            $table->unsignedBigInteger('item_group_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->longText('remarks')->nullable();
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
