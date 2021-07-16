<?php
namespace App\Services\Queue;
use Illuminate\Support\Facades\Facade;

class JobQueueFacade extends Facade
{
    // all status
    const STATUS_QUEUED = 'queued';
    const STATUS_IN_PROCESS = 'in_process';
    const STATUS_FAILED = 'failed';
    const STATUS_COMPLETED = 'completed';

	// all queue actions
	const CONNECT_SRS = 'connect_srs';
	const SRS_SAVE_BRANCH_PRODUCT = 'srs_save_branch_product';
	const SRS_SYNC_DETAILS = 'srs_sync_details';
    const PROPOSAL_DIGITAL_SIGN = 'proposal_digital_sign';

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'job_queue';
    }
}