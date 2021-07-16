<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterLaboursAddAdditionalFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('labours', function(Blueprint $table)
		{
			DB::statement("ALTER TABLE labours MODIFY COLUMN phone text");
			$table->renameColumn('phone', 'phones');
			$table->string('address')->nullable();
			$table->string('address_line_1')->nullable();
			$table->string('city')->nullable();
			$table->integer('state_id')->nullable();
			$table->integer('country_id')->nullable();
			$table->string('zip')->nullable();
			$table->string('type');
			$table->integer('deleted_by')->nullable();
			$table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('labours', function(Blueprint $table)
		{
			DB::statement("ALTER TABLE labours MODIFY COLUMN phones varchar(255)");
			$table->renameColumn('phones', 'phone');
			$table->dropColumn('address');
			$table->dropColumn('address_line_1');
			$table->dropColumn('city');
			$table->dropColumn('state_id');
			$table->dropColumn('country_id');
			$table->dropColumn('zip');
			$table->dropColumn('type');
			$table->dropColumn('deleted_by');
			$table->dropColumn('deleted_at');
		});
	}

}
