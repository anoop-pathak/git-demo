<?php namespace App\Commands;

use App\Handlers\Commands\SMSignupCommandHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SMSignupCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $input;
    public $signupDetails;
    public $username;
    public $password;

    public function __construct($input)
    {
        $this->input = $input;
        $this->mapSignupDetails();
        $this->username = $input['Username'];
        $this->password = $input['Password'];
    }
    
    public function handle()
    {
        $commandHandler = \App::make(SMSignupCommandHandler::class);
        
        return $commandHandler->handle($this);
    }

    private function mapSignupDetails()
    {
        $map = [
            "Username",
            "Password",
            "FirstName",
            "LastName",
            "CellPhone",
            "CompanyName",
            "CompanyAddress",
            "CompanyAddress2",
            "CompanyCity",
            "CompanyState",
            "CompanyZip",
            "CompanyPhone",
            "BillingName",
            "BillingAddress",
            "BillingAddress2",
            "BillingCity",
            "BillingState",
            "BillingZip",
            "BillingPhone",
            "CardNumber",
            "CardExp",
            "CardCode",
            "CoveragePlus",
            "PreferredContact",
        ];

        $this->signupDetails = $this->mapInputs($map, $this->input);
        $signupDetails['CompanyType'] = 'Contractor';
        $signupDetails['CellPhone'] = phoneNumberFormat($this->signupDetails['CellPhone'], 'US');
        $signupDetails['CompanyPhone'] = phoneNumberFormat($this->signupDetails['CompanyPhone'], 'US');
        $signupDetails['BillingPhone'] = phoneNumberFormat($this->signupDetails['BillingPhone'], 'US');
        // $signupDetails["PreferredContact"] = "Email";
        $this->signupDetails['CustomerType'] = config('skymeasure.source_id');

        // dd($this->signupDetails);
    }

    private function mapInputs($map, $input = [])
    {

        $ret = [];

        if (empty($input)) {
            $input = $this->input;
        }

        foreach ($map as $key => $value) {
            if (is_numeric($key)) {
                $ret[$value] = isset($input[$value]) ? $input[$value] : null;
            } else {
                $ret[$key] = isset($input[$value]) ? $input[$value] : null;
            }
        }

        return $ret;
    }
}
