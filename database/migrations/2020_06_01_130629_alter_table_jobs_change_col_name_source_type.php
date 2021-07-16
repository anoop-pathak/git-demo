<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobsChangeColNameSourceType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jobs',function($table){
            $table->dropColumn('type');
            $table->string('source_type')->after('spotio_lead_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jobs',function($table){
            $table->string('type')->after('spotio_lead_id')->nullable();
            $table->dropColumn('source_type');
        });
    }
}
