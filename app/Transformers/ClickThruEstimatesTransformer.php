<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class ClickThruEstimatesTransformer extends TransformerAbstract
{
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
    protected $availableIncludes = ['job', 'users'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($estimate)
    {
		return [
            'id' => $estimate->id,
            'name' => $estimate->name,
            'job_id' => $estimate->job_id,
            'customer_id' => $estimate->customer_id,
            'manufacturer_id' => (int)$estimate->manufacturer_id,
            'level' => $estimate->level,
            'type' => $estimate->type,
            'waterproofing' => $estimate->waterproofing,
            'shingle' => $estimate->shingle,
            'underlayment' => $estimate->underlayment,
            'warranty' => $estimate->warranty,
            'roof_size' => (float)$estimate->roof_size,
            'structure' => $estimate->structure,
            'complexity' => $estimate->complexity,
            'pitch' => $estimate->pitch,
            'chimney' => $estimate->chimney,
            'skylight' =>(float)$estimate->skylight,
            'ventilations' => $estimate->others,
            'access_to_home' => $estimate->access_to_home,
            'gutter' => $estimate->gutter,
            'notes' => $estimate->notes,
            'adjustable_note' => $estimate->adjustable_note,
            'adjustable_amount' => (float)$estimate->adjustable_amount,
            'amount' => (float)$estimate->amount,
            'url'    => $estimate->url,
            'thumb_url'    => $estimate->thumb_url
        ];
	}

    /**
     * Include users
     *
     * @return League\Fractal\ItemResource
     */
    public function includeUsers($estimate) {
        $users = $estimate->users;

        if($users){
            return $this->collection($users, function($users) {
                return [
                    'id'            => $users->id,
                    'first_name'    => $users->first_name,
                    'last_name'     => $users->last_name,
                    'full_name'     => $users->full_name,
                    'email'         => $users->email,
                ];
            });
        }
    }

    /**
     * Include rep
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($estimate) {
        $job = $estimate->job;

        if($job){
            return $this->item($job, function($job) {
                return [
                    'id'             =>   $job->id,
                    'name'           =>   $job->name,
                    'number'         =>   $job->number,
                    'alt_id'         =>   $job->alt_id,
                    'lead_number'    =>   $job->lead_number,
                ];
            });
        }
    }
}