<?php 
namespace App\Transformers;
use League\Fractal\TransformerAbstract;
class AppointmentResultOptionsTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['appointment_count'];
 	/**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($result)
    {
		return [
            'id'     		=>  $result->id,
            'name'   		=>  $result->name,
			'fields' 		=>  $result->fields,
            'active'        =>  $result->active,
        ];
	}
     /**
     * Include Appointment Count
     * 
     * @return League\Fractal\ItemResource
     */
    public function includeAppointmentCount($result)
    {
        $count['count'] = (int)$result->appointment_count;
         return $this->item($count, function($count) {
            return $count;
        }); 
    }
}