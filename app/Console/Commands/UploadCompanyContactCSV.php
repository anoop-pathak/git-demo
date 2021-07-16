<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use App\Models\Company;
use App\Models\CompanyContact;
use Excel;

class UploadCompanyContactCSV extends Command {
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
 	protected $name = 'command:upload_company_contacts_csv';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
 	protected $description = 'Upload Company Contacts CSV file in company account';
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

 		$companyId = 158;
 		$this->company = Company::find($companyId);
 		if(!$this->company) throw new Exception("Invalid Company ID");

 		$path = storage_path().'/data/contacts.csv';
 		$contacts = Excel::load($path)->get();
 		$this->info('Start Timing: '. date('Y-m-d H:i:s'));
 		$this->info('Total Contacts: '. count($contacts));

 		$validContacts = [];
 		foreach ($contacts as $key => $contact) {
 			$lastName = implode(' ', arry_fu([$contact->additional_name, $contact->family_name, $contact->name_suffix]));
 			if(!$lastName) continue;
 			$email = arry_fu([$contact->e_mail_1_value, $contact->e_mail_2_value, $contact->e_mail_3_value]);
 			if(empty($email)) continue;
 			$phones = [];
 			$phoneKey = 0;
 			if($contact->phone_1_type &&  $contact->phone_1_value) {
 				$phones[$phoneKey]['label']  = strtolower($contact->phone_1_type);
 				$phones[$phoneKey]['number'] = $this->getPhoneMobileAttribute($contact->phone_1_value);
 				$phoneKey++;
 			}
 			if($contact->phone_2_type &&  $contact->phone_2_value) {
 				$phones[$phoneKey]['label']  = strtolower($contact->phone_2_type);
 				$phones[$phoneKey]['number'] = $this->getPhoneMobileAttribute($contact->phone_2_value);
 				$phoneKey++;
 			}
 			if($contact->phone_3_type &&  $contact->phone_3_value) {
 				$phones[$phoneKey]['label']  = strtolower($contact->phone_3_type);
 				$phones[$phoneKey]['number'] = $this->getPhoneMobileAttribute($contact->phone_3_value);
 				$phoneKey++;
 			}
 			if(empty($phones)) continue;
 			$validContacts[] = [
 				'company_id' => $companyId,
 				'first_name' => $contact->given_name,
 				'last_name'  => $lastName,
 				'email'      => array_shift($email),
 				'phones'     =>  json_encode($phones, true),
 				'note'       => $contact->notes,
 				'address'    => $contact->address_1_formatted,
 				'created_at' => date('Y-m-d H:i:s'),
 				'updated_at' => date('Y-m-d H:i:s')
 			];
 		}
 		$this->info('Total Valid Records: '. count($validContacts));
 		if(!empty($validContacts)) {
 			CompanyContact::insert($validContacts);
 		}
 		$this->info('End Timing: '. date('Y-m-d H:i:s'));
 	}
 	public function getPhoneMobileAttribute($phoneNumber)
 	{
 		if ($phoneNumber) {
 			$output = preg_replace("/[^\d]/","",$phoneNumber);
 			return substr($output, -10);
 		}
 	}
 }
