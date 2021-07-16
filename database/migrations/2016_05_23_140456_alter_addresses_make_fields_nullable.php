<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAddressesMakeFieldsNullable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
	    DB::statement("ALTER TABLE addresses MODIFY  zip varchar(255) NULL;");
	    DB::statement("ALTER TABLE addresses MODIFY  address varchar(255) NULL;");
	    DB::statement("ALTER TABLE addresses MODIFY  state_id INTEGER DEFAULT  0;");
	    DB::statement("ALTER TABLE addresses MODIFY  country_id INTEGER DEFAULT  0;");
	    DB::statement("ALTER TABLE addresses MODIFY  city varchar(255) NULL;");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE addresses MODIFY  city varchar(255)  NOT NULL;");
	    DB::statement("ALTER TABLE addresses MODIFY  country_id INTEGER UNSIGNED NOT NULL;");
	    DB::statement("ALTER TABLE addresses MODIFY  state_id INTEGER UNSIGNED NOT NULL;");
	    DB::statement("ALTER TABLE addresses MODIFY  address varchar(255) NOT NULL;");
	    DB::statement("ALTER TABLE addresses MODIFY  zip varchar(255)  NOT NULL;");
	}

}
