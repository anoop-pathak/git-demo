<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableNewResourcesChangeColType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('new_resources', function(Blueprint $table) {
        //     $table->string('external_full_url')->nullable()->change();
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table('new_resources', function(Blueprint $table) {
        //     $table->string('external_full_url')->change();
        // });
    }
}
