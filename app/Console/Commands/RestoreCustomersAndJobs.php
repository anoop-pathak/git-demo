<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Solr;

class RestoreCustomersAndJobs extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:restore_customers_and_jobs';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Restore AO1 deleted customers and jobs';

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
		$customersIds = [1705242,1705237,1705232,1705229,1705223,1705216,1704935,1703415,1702677,1702664,1702658,1702300,1699644,1699493,1699035,1698927,1698917,1698293,1698281,1698270,1698261,1689889,1685168,1681828,1681505,1681476,1681468,1674531,1671986,1669669,1669350,1664923,1653474,1646624,1645986,1609705,1609696,1605967,1605777,1603635,1595039,1587960,1577187,1577182,1577175,1577170,1573683,1573548,1573488,1570754,1567011,1567010,1566993,1561938,1561936,1552403,1552397,1544291,1544287,1542353,1541562,1541561,1541405,1540429,1538787,1531783,1531577,1530949,1530939,1530928,1530918,1524045,1523990,1518341,1516518,1516509,1516504,1516431,1516417,1516409,1508564,1506820,1506800,1503589,1491305,1484872,1484866,1484865,1481996,1481879,1481871,1481813,1481784,1480980,1480235,1480196,1478434,1472093,1463783,1455858,1455855,1453638,1452302,1452278,1429482,1404828,1404820,1402182,1402178,1402168,1402163,1401291,1401233,1401231,1401229,1401227,1396353,1396331,1396323,1395359,1395318,1395269,1391632,1390325,1390316,1390314,1388511,1388498,1388492,1383997,1381185,1381176,1378965,1372364,1372362,1369142,1369137,1364232,1364209,1364193,1364184,1364158,1364125,1357158,1357146,1357133,1357116,1357109,1353298,1353085,1352124,1352028,1351158,1348736,1348669,1347410,1347144,1346622,1346616,1346612,1346604,1346602,1346596,1346239,1344134,1344122,1344112,1343969,1343906,1343900,1343892,1343772,1343367,1342634,1342624,1340635,1340615,1340603,1340414,1339244,1334205,1334179,1334171,1334169,1333284,1332736,1332620,1332276,1323850,1323840,1323834,1323824,1299724,1283941,1252670,1252669,1252668,1199639,1198315,1198310,1150421,1147443,1140387,1139009,1136409,1135894,1135874,1133257,1131984,1128281,1127082,1121107,1114425,1111605,1105601,1103675,1096285,1089836,1062188,1062180,1061365,1060103,1018608,975142,974953,971871,957639,945802,943524,927507,925220,918080,916695,916668,913440,906441,906439,897765,895368,891965,884030,871713,871250,870568,870298,867052,863685,863655,832734,831719,831717,828169,828071,828055,820364,818118,818109,818103,808256,806132,806104,803892,803864,803849,803828,803822,803812,803809,803386,802629,802330,800030,799722,799712,797632,797628,797623,793141,792582,792233,791176,790780,790597,788137,788132,788128,785703,784391,782776,782707,782699,777118,770423,770418,770411,770394,770372,767803,757803,757768,754557,753142,753129,753113,753071,753035,752977,739002,738989,738962,738959,738952,738946,738934,738925,709351,709200,691576,684420,675545,675500,675468,674466,674459,674456,674454,621891,621303,621296,621275,606037,602729,602583,600484,590520,590519,590518,589707,588576,578133,577611,577609,577541,577508,577361,577359,576757,575733,575031,575028,572333,572323,572315,572304,563669,561768,561760,561754,553225,553223,553222,553220,552746,546727,546722,535784,535772,535747,534882,528769,528763];
		$companyId = 752; // AO1 Roofing and Construction LLC..
		$company = Company::find($companyId);

		if(!$company){
			$this->info('Please enter valid company id.');
			return;
		}

		$systemUser = $company->anonymous;

		$this->info("Start Time: ".Carbon::now()->toDateTimeString());

		$this->info('Total Customer Restore: '.count($customersIds));

		DB::statement('SET FOREIGN_KEY_CHECKS=0;');

		DB::table('customers')
			->where('company_id', $companyId)
			->whereIn('id', $customersIds)
			->update([
				'deleted_at' => null,
				'deleted_by' => null,
				'delete_note' => null,
			]);

		DB::table('jobs')
			->whereIn('customer_id', $customersIds)
			->where('company_id', $companyId)
			->update([
				'deleted_at' => null,
				'deleted_by' => null,
				'delete_note' => null,
			]);

		DB::statement('SET FOREIGN_KEY_CHECKS=1;');
		foreach ($customersIds as $customersId) {
			Solr::customerIndex($customersId);
		}

		$this->info("End Time: ".Carbon::now()->toDateTimeString());
	}
}