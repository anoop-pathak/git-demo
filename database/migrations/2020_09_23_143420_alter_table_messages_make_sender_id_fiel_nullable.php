<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableMessagesMakeSenderIdFielNullable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE messages MODIFY  sender_id INT(11) NULL;");
		DB::statement("ALTER TABLE messages MODIFY  subject varchar(255) NULL;");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE messages MODIFY  sender_id INT(11)  NOT NULL;");
		DB::statement("ALTER TABLE messages MODIFY  subject varchar(255)  NOT NULL;");
	}

}
