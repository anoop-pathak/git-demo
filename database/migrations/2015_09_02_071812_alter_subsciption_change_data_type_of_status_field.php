<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSubsciptionChangeDataTypeOfStatusField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE subscriptions MODIFY COLUMN status VARCHAR(255)');
		DB::statement('UPDATE subscriptions SET status = CASE WHEN status = 1 THEN "active" ELSE "inactive" END');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('UPDATE subscriptions SET status = CASE WHEN status = "active" THEN 1 ELSE 0 END');
		DB::statement('ALTER TABLE subscriptions MODIFY COLUMN status TINYINT(1)');
	}

}
