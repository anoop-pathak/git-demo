<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DuplicateSRSOrdersAppendIdToMaterialListId extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:duplicate_srs_orders_append_id_to_material_list_id';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Update duplicate srs orders and append its id to material_list_id column.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$query = "SELECT id FROM srs_orders
			WHERE material_list_id in
			(
				SELECT material_list_id
				FROM srs_orders
				WHERE material_list_id IS NOT NULL
				GROUP BY srs_orders.material_list_id HAVING count(*) > 1
			)
			AND id NOT IN
			(
				SELECT MAX(srs_orders.id)
				FROM srs_orders
				WHERE material_list_id IS NOT NULL
				GROUP BY srs_orders.material_list_id HAVING count(*) > 1
			);";

		$orderIds = DB::select(DB::raw($query));
		DB::table('srs_orders')
			->whereIn('id', json_decode(json_encode($orderIds), 1))
			->update(['material_list_id' => DB::raw("concat(material_list_id,'_',id)")]);
	}

}
