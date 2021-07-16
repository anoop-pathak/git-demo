<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableWarrantyTypesAddNullable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		 DB::statement("ALTER TABLE warranty_types MODIFY description TEXT NULL");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		 DB::statement("ALTER TABLE warranty_types MODIFY description TEXT NOT NULL");
	}

}
