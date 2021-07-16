<?php
namespace  App\Handlers\Commands;

use App\Services\DripCampaigns\DripCampaignService;

class DripCampaignCommandHandler {

	/**
	 *  Command Object
	 * @var JobProgress\DripCampaigns\DripCampaign
	 */
	private $command;
	protected $service;

    public function __construct(DripCampaignService $service) {
        $this->service = $service;
    }
	/**
     * Handle the command.
     *
     * @param object $command
     * @return void
     */
	public function handle($command)
	{
		$this->command = $command;
        $dripCampaign = null;
        try{
            $dripCampaign = $this->service->save(
                $command->campaignData,
                $command->emailCampaign,
                $command->emailRecipentsTo,
                $command->emailRecipentsCC,
                $command->emailRecipentsBCC,
                $command->emailAttachments
            );

        }catch(\Exception $e){
        	throw $e;
        }

        return $dripCampaign;
    }
}