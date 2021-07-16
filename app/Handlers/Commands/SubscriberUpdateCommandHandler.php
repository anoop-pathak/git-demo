<?php
namespace App\Handlers\Commands;

use App\Events\SubscriberAccountUpdated;
use App\Models\Company;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;

class SubscriberUpdateCommandHandler
{

    /**
     * Handle the command.
     *
     * @param object $command
     * @return void
     */
    public function handle($command)
    {
        $input = $command->input;
        $company = Company::find($command->input['id']);

        // save license number into DB
        if(isset($input['license_numbers'])) {

            $this->setCompanyLicenseNumber($command, $input['license_numbers']);

            $this->saveLicenseNumbers($company, $input['license_numbers']);
        }

        $company->update($command->companyData);

        //event..
        Event::fire('JobProgress.Subscriptions.Events.SubscriberAccountUpdated', new SubscriberAccountUpdated($company));

        return $company;
    }

    private function saveLicenseNumbers($company, $licenseNumbers)
    {
        $data = [];
        $company->licenseNumbers()->delete();
        foreach ($licenseNumbers as $key => $licenseNumber) {
            $data[] = [
                'company_id'     => getScopeId(),
                'position'       => $licenseNumber['position'],
                'license_number' => $licenseNumber['number'],
                'created_by'     => Auth::id(),
                'updated_by'     => Auth::id(),
            ];
        }

        if($data) {
            $company->licenseNumbers()->createMany($data);
        }

        return $company;
    }

    private function setCompanyLicenseNumber($command, $licenseNumbers)
    {
        if(!empty($licenseNumbers)) {
            sort($licenseNumbers);
            $command->companyData['license_number'] = $licenseNumbers[0]['number'];
        }

        return $command;
    }
}
