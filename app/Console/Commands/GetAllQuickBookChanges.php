<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Indatus\Dispatcher\Scheduling\Schedulable;
use App\Services\QuickBooks\Facades\QuickBooks;
use Excel;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetAllQuickBookChanges extends Command
{

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:get_all_quickbook_changes';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Sync QuickBook Customer Changes.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();

		$this->entity = app()->make('\JobProgress\QuickBooks\CDC\Entity\Customer');
	}

	/**
	 * When a command should run
	 *
	 * @param Schedulable $scheduler
	 * @return \Indatus\Dispatcher\Scheduling\Schedulable
	 */
	public function schedule(Schedulable $scheduler)
	{
		return $scheduler;
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		try {

			return false; // not required now.

            QuickBooks::setCompanyScope(null, 1160);

            $response = QuickBooks::cdc(['customer', 'payment', 'invoice', 'creditmemo', 'Account'], 24*60);

            $csvItems = [];

            if ($response->entities) {

                foreach ($response->entities as $key => $items) {

                    if($items) {

                        foreach ($items as $item) {

                            $status = '';

                            if($key == 'Customer') {

                                $status = ($item->Active == 'true') ? 'Active' : 'Deleted';
                            }

                            if($key != 'Customer') {

                                $status = (!empty($item->CustomerRef)) ? 'Active' : 'Deleted';
                            }

                            $csvItems[] = [
                                'Id' => $item->Id,
                                'Display Name' =>  (isset($item->DisplayName)) ? $item->DisplayName: '',
                                'ParentRef' =>  (isset($item->ParentRef)) ? $item->ParentRef: '',
                                'CustomerRef' =>  (isset($item->CustomerRef)) ? $item->CustomerRef: '',
                                'Entity' => $key,
                                'Status' => $status,
                                'Created At' => $item->MetaData->CreateTime,
                                'Updated At' => $item->MetaData->LastUpdatedTime,
                                'Detail' => json_encode(QuickBooks::toArray($item))
                            ];
                        }
                    }
                }
            }

            Excel::create('CDC', function($excel) use($csvItems){
				$excel->sheet('sheet1', function($sheet) use($csvItems){
					$sheet->fromArray($csvItems);
				});
			})->save('csv');

            Log::info(print_r($response, true));

		} catch (Exception $e) {

            DB::rollback();

			Log::error('Sync QuickBooks Task Error :'.$e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine());
		}
	}
}
