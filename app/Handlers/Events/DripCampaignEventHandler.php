<?php
namespace App\Handlers\Events;

use Illuminate\Support\Facades\Log;

class DripCampaignEventHandler
{

    public function __construct()
    {
    }

    public function subscribe($event)
    {
        $event->listen('JobProgress.DripCampaigns.Events.DripCampaignCreated', 'App\Handlers\Events\DripCampaignEventHandler@created');
        $event->listen('JobProgress.DripCampaigns.Events.DripCampaignCanceled', 'App\Handlers\Events\DripCampaignEventHandler@canceled');
    }

    public function created( $event )
    {
        Log::info("DripCampaigns event created.");
    }

    public function canceled( $event )
    {
        Log::info("DripCampaigns event canceled.");
    }

}