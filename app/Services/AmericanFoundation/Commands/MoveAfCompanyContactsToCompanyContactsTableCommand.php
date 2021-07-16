<?php
namespace App\Services\AmericanFoundation\Commands;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Services\AmericanFoundation\Models\AfCompanyContact;

class MoveAfCompanyContactsToCompanyContactsTableCommand extends Command
{

    /**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:af_company_contacts_move_to_company_contacts';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
    protected $description = 'Move American Foundation company contacts from af_company_contacts table to company company_contacts table.';

    private $inc = 0;

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
        AfCompanyContact::chunk(50, function($items){
                foreach ($items as $item) {
                    if($item->company_contact_id) {
                        continue;
                    }

                    try {

                        $arrData = $this->fieldsMapping($item);

                        setScopeId($item->company_id);

                        $repository = App::make('App\Repositories\CompanyContactsRepository');
                        $savedUser = $repository->save($arrData);
                        $item->company_contact_id = $savedUser->id;
                        $item->save();
                        $this->inc++;
                        if($this->inc %10 == 0) {
                            $this->info("Total Processing company contacts:- " . $this->inc);
                        }

                    } catch (\Exception $e) {
                        Log::error("Error in American Foundation Move AfCompanyContacts to CompanyContacts table");
                        Log::error($e);
                    }
                }
        });
        $this->info("Total Processed company contacts:- " . $this->inc);
    }

    private function fieldsMapping(AfCompanyContact $item)
    {
        $phones = [];

        if($item->phone) {
            $phone = str_replace('(', '', $item->phone);
            $phone = str_replace(')', '', $phone);
            $phone = str_replace(' ', '', $phone);
            $phone = str_replace('-', '', $phone);
            $phones[] = array(
                'label' => 'phone',
                'number' => $phone
            );
        }

        if($item->fax) {
            $fax = str_replace('(', '', $item->fax);
            $fax = str_replace(')', '', $fax);
            $fax = str_replace(' ', '', $fax);
            $fax = str_replace('-', '', $fax);
            $phones[] = array(
                'label' => 'fax',
                'number' => $fax
            );
        }

        if($item->mobile_phone) {
            $mphone = str_replace('(', '', $item->mobile_phone);
            $mphone = str_replace(')', '', $mphone);
            $mphone = str_replace(' ', '', $mphone);
            $mphone = str_replace('-', '', $mphone);
            $phones[] = array(
                'label' => 'cell',
                'number' => $mphone
            );
        }
        $data = array(
            'first_name'            => $item->first_name,
            'last_name'             => $item->last_name,
            'email'                 => $item->email ?: "",
            'company_name'          => null,
            'note'                  => $item->description ?: "" ,
            'phones'                => $phones,
            'address'               => $item->mailing_address,
        );
        return $data;
    }
}