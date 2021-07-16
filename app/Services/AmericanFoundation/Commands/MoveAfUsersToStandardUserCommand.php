<?php
namespace App\Services\AmericanFoundation\Commands;

use Illuminate\Console\Command;
use App\Services\AmericanFoundation\Entities\AfUserEntity;
use App\Services\AmericanFoundation\Models\AfUser;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class MoveAfUsersToStandardUserCommand extends Command
{

    /**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:af_users_move_to_users_make_standard_user';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
    protected $description = 'Move American Foundation users from af_users table to users table and make standard user.';

    private $password = "123456";

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
        $this->info("Script Start.");
        AfUser::with(['state_info', 'country_info', 'company'])
            ->where('is_active', 1)
            // ->whereNull('user_id')
            ->where('first_name', '!=', '')
            ->chunk(100, function($users){

                foreach ($users as $user) {
                    if($user->user_id) {
                        continue;
                    }
                    try {
                        $userArr = $this->userFieldsMapping($user);

                        $service = App::make('App\Services\AmericanFoundation\Services\AfUserService');
                        $savedUser = $service->createStandardUser($userArr);
                        $user->user_id = $savedUser->id;
                        $user->save();
                        $this->inc++;
                        if($this->inc %100 == 0) {
                            $this->info("Total Processing users:- " . $this->inc);
                        }

                    } catch (\Exception $e) {
                        Log::error("Error in American Foundation Move AfUsers to users table");
                        Log::error($e);
                    }
                }
        });
        $this->info("Total Processed users:- " . $this->inc);
        $this->info("Script End.");
    }

    private function userFieldsMapping(AfUser $user)
    {
        $countryId = $user->country_info ? $user->country_info->id : null;
        $stateId = $user->state_info ? $user->state_info->id : null;
        $company = $user->company;

        $mobileNumber = trim($this->removeCountryCodeFromMobileNumber($user->mobile_phone));
        $mobileNumber = $this->removeSpecialCharFromPhoneNumber($mobileNumber);

        $additionalPhones = [];
        if($user->phone) {
            $additionalPhones[] = array(
                'label' => 'phone',
                'phone' => $this->removeSpecialCharFromPhoneNumber($user->phone),
            );
        }
        if($user->fax) {
            $additionalPhones[] = array(
                'label' => 'fax',
                'phone' => $this->removeSpecialCharFromPhoneNumber($user->fax),
            );
        }
        if($user->mobile_phone) {
            $additionalPhones[] = array(
                'label' => 'cell',
                'phone' => $mobileNumber,
            );
        }

        $phoneNumber = $user->phone ?: $company->office_phone;
        if($phoneNumber) {
            $phoneNumber = trim($this->removeCountryCodeFromMobileNumber($phoneNumber));
            $phoneNumber = $this->removeSpecialCharFromPhoneNumber($phoneNumber);
        }

        return array(
            "email" => $user->email,
            "country_id" => $countryId ?: $company->office_country,
            "company_id" => $user->company_id,
            "company_name" => $user->company_name,
            "group_id" => $user->group_id,
            "address" => $user->street  ?: $company->office_address,
            "address_line_1" => null,
            "city" => $user->city ?: $company->office_city,
            "state_id" => $stateId ?: $company->office_state,
            "zip" => $user->postal_code ?: $company->office_zip,
            "first_name" => $user->first_name,
            "last_name" => $user->last_name,
            "position" => "",
            "password" => $this->password,
            "password_confirmation" => $this->password,
            "additional_phone" => $additionalPhones,
            "send_mail" => "0",
            "all_divisions_access" => "1",
            "admin_privilege" => 0,
            "departments" => null,
            "note" => $user->about_me,
            "phone" => $phoneNumber,
            "cell" => $mobileNumber,
            "stop_db_transaction" => false,
        );
    }

    private function removeCountryCodeFromMobileNumber($mobileNumber)
    {
        if(!$mobileNumber) {
            return $mobileNumber;
        }
        $codes = config('mobile-message.country_code');

        foreach($codes as $code) {
            $mobileNumber = str_replace($code, '', $mobileNumber);
        }
        return $mobileNumber;
    }

    private function removeSpecialCharFromPhoneNumber($number)
    {
        if(!$number) {
            return $number;
        }
        $number = str_replace('(', '', $number);
        $number = str_replace(')', '', $number);
        $number = str_replace(' ', '', $number);
        $number = str_replace('-', '', $number);
        return $number;
    }
}