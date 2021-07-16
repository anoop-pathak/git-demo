<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialCategoriesAddSlugColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_categories', function(Blueprint $table) {
			$table->string('slug');
		});

		// create slug
		DB::statement("UPDATE financial_categories as t1,(SELECT *, LOWER(REPLACE(LTRIM(RTRIM(name)), SPACE(1), '_')) as name_slug FROM financial_categories ) as t2 SET `t1`.`slug` = t2.name_slug WHERE t2.id = t1.id;");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_categories', function(Blueprint $table) {
			$table->dropColumn('slug');
		});
	}

}
