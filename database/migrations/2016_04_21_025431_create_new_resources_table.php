<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewResourcesTable extends Migration {

    public function up()
    {
        Schema::table('new_resources', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            Schema::create('new_resources', function(Blueprint $t)
            {
                $t->increments('id');
                $t->integer('company_id');
                $t->integer('parent_id')->unsigned()->nullable();
                $t->integer('position', false, true);
                $t->integer('real_depth', false, true);
                $t->string('name', 255);
                $t->double('size');
                $t->boolean('thumb_exists');
                $t->string('path');
                $t->boolean('is_dir');
                $t->string('mime_type')->nullable();
                $t->boolean('locked');
                $t->integer('created_by')->nullable();
                $t->timestamps();
                $t->integer('deleted_by')->nullable();
                $t->softDeletes();

                $t->foreign('parent_id')->references('id')->on('new_resources')->onDelete('set null');
            });
        });
    }

    public function down()
    {
        Schema::table('new_resources', function(Blueprint $table)
        {
            Schema::dropIfExists('new_resources');
        });
    }
}
