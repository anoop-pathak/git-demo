<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMaterialListsAddIndexLinkTypeLinkIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('material_lists', function(Blueprint $table)
		{
			if(!isIndexExists('material_lists', 'material_lists_link_id_index')){
				$table->index('link_id');
			}

			if(!isIndexExists('material_lists', 'material_lists_link_type_index')){
				$table->index('link_type');
			}

			if(!isIndexExists('material_lists', 'material_lists_type_index')){
				$table->index('type');
			}
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('material_lists', function(Blueprint $table)
		{
			$table->dropIndex('material_lists_link_id_index');
			$table->dropIndex('material_lists_link_type_index');
			$table->dropIndex('material_lists_type_index');
		});
	}

}
