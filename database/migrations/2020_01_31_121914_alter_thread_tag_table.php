<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterThreadTagTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE `thread_tag` 
			MODIFY COLUMN `thread_id` VARCHAR(255) NOT NULL'
		);
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE `financial_products` 
			MODIFY COLUMN `name` INT NOT NULL'
		);
	}

}
