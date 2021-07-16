<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFoldersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('folders', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('parent_id')->nullable()->index();
			$table->integer('company_id')->default(0)->nullable()->index();
			$table->string('type')->nullable()->comment("Like template_proposals/template_estimates/estimates/proposals etc")->index();
			$table->integer('reference_id')->nullable();
			$table->string('name')->index();
			$table->string('path')->nullable()->index();
	      	$table->boolean('is_dir')->default(0)->index();
	      	$table->integer('created_by')->nullable();
			$table->integer('updated_by')->nullable();
			$table->softDeletes();
			$table->integer('deleted_by')->nullable();
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
		Schema::drop('folders');
	}

}
