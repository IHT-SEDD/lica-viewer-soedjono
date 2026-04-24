<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewNoOrderToTableFinishTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('finish_transactions', function (Blueprint $table) {
           $table->string('new_no_order')->nullable()->after('no_order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('finish_transactions', function (Blueprint $table) {
            $table->dropcoloumn('new_no_order');
        });
    }
}
