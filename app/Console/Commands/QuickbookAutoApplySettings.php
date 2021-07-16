<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use GuzzleHttp\Client as GuzzleClient;
use App\Models\Company;
use App\Services\QuickBooks\QuickBookService;

class QuickbookAutoApplySettings extends Command
{
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
 	protected $name = 'command:quickbook_auto_apply_settings';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
 	protected $description = 'Quickbook Auto Appply credits settings';
 	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
 	public function __construct()
 	{
 		parent::__construct();
 		$this->request = new GuzzleClient(['base_url' => \Config::get('jp.quickbook.base_api_url'), 'debug' => true]);
 	}
 	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
 	public function fire()
 	{
 		$companies = Company::has('quickbook')->get();
 		$quickbookService = App::make(QuickBookService::class);
 		foreach ($companies as $key=>$company) {
 			setScopeId($company->id);
 			$quickbook = $company->quickbook;
 			$quickbookId = $quickbook->quickbook_id;
 			$token = $quickbookService->getToken();
 			$path = config('jp.quickbook.base_api_url').$quickbookId."/query?";
 			$body = [
 				'query' => "select * from preferences",
 				'minorversion' => 38
 			];
 			$response = $this->get($path, $body, $token->access_token);
 			$data[$key]['Subscriber Id'] = $company->id;
 			$data[$key]['Subscriber Name'] = $company->name;
 			$data[$key]['Reconnect'] = ine($response,'reconnect') ? "true" : "false";
 			$data[$key]['Auto Apply Credits'] = "false";
 			if(!ine($response,'reconnect')){
 				$data[$key]['Auto Apply Credits'] = $response['QueryResponse']['Preferences'][0]['SalesFormsPrefs']['AutoApplyCredit'] ? 'true':'false';
 			}
 		}
		// $headers = ['Subscriber Id', 'Subscriber Name', 'Auto Apply Credits'];
		// $this->table($headers, $data);
 		$csv = "";
 		$csv .= implode(",", array_keys($data[0])) . "\n";
 		foreach ($data as $row) {
 			$csv .= implode(",", array_values($row)) . "\n";
 		}
 		file_put_contents("public/auto_apply_credits.csv", $csv);
 		$this->info('Download file URL:'.config('app.url').'auto_apply_credits.csv');
 	}
 	public function get($path, $body, $authorizationHeader, $context = 'accounting')
 	{
 		$data = $this->request->setDefaultOption('headers',[
 			'Authorization'	=> "Bearer ".$authorizationHeader,
 			'Content-Type' => 'application/json',
 			'Accept' => 'application/json',
 			'Request-Id' => time()
 		]);
 		$request = $this->request->createRequest( 'Get', $path.http_build_query($body));
 		try {
 			$response = $this->request->send($request);
	        // $response['reconnect'] = false;
 		} catch (\Exception $e) {
			// if($e->getCode() == 500) {
			// 	throw new \Exception("Quickbooks Server Error (500): " . $e->getMessage(), 0);
			// }
			// throw $e;
 			$response['reconnect'] = true;
 			return $response;
 		}

 		$result = $response->getBody()->getContents();
 		$queryResponse = json_decode($result, true);
 		return $queryResponse;
 	}
 }