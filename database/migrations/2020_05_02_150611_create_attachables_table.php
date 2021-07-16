<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttachablesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('attachables', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('customer_id')->index();
			$table->integer('job_id');
			$table->integer('jp_object_id');
			$table->string('object_type');
			$table->integer('jp_attachment_id')->nullable();
			$table->bigInteger('quickbook_id')->nullable();
			$table->integer('quickbook_sync_token')->nullable();
			$table->integer('quickbook_sync_status')->nullable();
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
		Schema::drop('attachables');
	}

}
