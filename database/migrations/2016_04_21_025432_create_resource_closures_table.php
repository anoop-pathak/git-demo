<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResourceClosuresTable extends Migration {

    public function up()
    {
        Schema::table('resource_closure', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            Schema::create('resource_closure', function(Blueprint $t)
            {
                $t->increments('ctid');

                $t->integer('ancestor', false, true);
                $t->integer('descendant', false, true);
                $t->integer('depth', false, true);

                $t->foreign('ancestor')->references('id')->on('new_resources')->onDelete('cascade');
                $t->foreign('descendant')->references('id')->on('new_resources')->onDelete('cascade');
            });
        });
    }

    public function down()
    {
        Schema::table('resource_closure', function(Blueprint $table)
        {
            Schema::dropIfExists('resource_closure');
        });
    }
}
