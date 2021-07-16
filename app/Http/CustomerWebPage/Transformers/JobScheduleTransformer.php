<?php
namespace App\Http\CustomerWebPage\Transformers;

use League\Fractal\TransformerAbstract;
use App\Http\CustomerWebPage\Transformers\JobsTransformer as JobTrans;
use App\Http\CustomerWebPage\Transformers\CustomersTransformer as CustomerTrans;
use App\Http\CustomerWebPage\Transformers\UsersTransformer;
use App\Http\CustomerWebPage\Transformers\JobTypesTransformer;

class JobScheduleTransformer extends TransformerAbstract {
	/**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

     /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($schedule) {
        
		return [
		   'id'		      => $schedule->recurring_id,
            'title'            => $schedule->title,
            'description'      => $schedule->description,
            'start_date_time'  => $schedule->start_date_time,
            'end_date_time'    => $schedule->end_date_time,
            'full_day'         => $schedule->full_day,
            'is_completed'     => (bool)$schedule->completed_at,
		];
	}
    
}