<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddManufacturerIdInAllClickthruSettingsManager extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('estimate_type_layers', function(Blueprint $table)
		{
			$table->integer('manufacturer_id')->after('company_id')->index();
		});

		Schema::table('waterproofing', function(Blueprint $table)
		{
			$table->integer('manufacturer_id')->after('company_id')->index();
		});

		Schema::table('estimate_levels', function(Blueprint $table)
		{
			$table->integer('manufacturer_id')->after('company_id')->index();
		});

		Schema::table('estimate_pitch', function(Blueprint $table)
		{
			$table->integer('manufacturer_id')->after('company_id')->index();
		});


		Schema::table('shingles_underlayments', function(Blueprint $table)
		{
			$table->integer('manufacturer_id')->after('company_id')->index();
		});

		Schema::table('estimate_ventilations', function(Blueprint $table)
		{
			$table->integer('manufacturer_id')->after('company_id')->index();
		});

		Schema::table('access_to_home', function(Blueprint $table)
		{
			$table->integer('manufacturer_id')->after('company_id')->index();
		});

		Schema::table('estimate_structures', function(Blueprint $table)
		{
			$table->integer('manufacturer_id')->after('company_id')->index();
		});

		Schema::table('estimate_gutters', function(Blueprint $table)
		{
			$table->integer('manufacturer_id')->after('company_id')->index();
		});

		Schema::table('estimate_chimnies', function(Blueprint $table)
		{
			$table->integer('manufacturer_id')->after('company_id')->index();
		});

		Schema::table('warranty_types', function(Blueprint $table)
		{
			$table->integer('manufacturer_id')->after('company_id')->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('estimate_type_layers', function(Blueprint $table)
		{
			$table->dropColumn('manufacturer_id');
		});

		Schema::table('waterproofing', function(Blueprint $table)
		{
			$table->dropColumn('manufacturer_id');
		});

		Schema::table('estimate_levels', function(Blueprint $table)
		{
			$table->dropColumn('manufacturer_id');
		});

		Schema::table('estimate_pitch', function(Blueprint $table)
		{
			$table->dropColumn('manufacturer_id');
		});

		Schema::table('shingles_underlayments', function(Blueprint $table)
		{
			$table->dropColumn('manufacturer_id');
		});

		Schema::table('estimate_ventilations', function(Blueprint $table)
		{
			$table->dropColumn('manufacturer_id');
		});

		Schema::table('access_to_home', function(Blueprint $table)
		{
			$table->dropColumn('manufacturer_id');
		});

		Schema::table('estimate_structures', function(Blueprint $table)
		{
			$table->dropColumn('manufacturer_id');
		});

		Schema::table('estimate_gutters', function(Blueprint $table)
		{
			$table->dropColumn('manufacturer_id');
		});

		Schema::table('estimate_chimnies', function(Blueprint $table)
		{
			$table->dropColumn('manufacturer_id');
		});

		Schema::table('warranty_types', function(Blueprint $table)
		{
			$table->dropColumn('manufacturer_id');
		});

	}

}
