<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProposalAttachmentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('proposal_attachments', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('proposal_id');
			$table->string('name');
			$table->string('path');
			$table->string('mime_type');
			$table->double('size');
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
		Schema::drop('proposal_attachments');
	}

}
