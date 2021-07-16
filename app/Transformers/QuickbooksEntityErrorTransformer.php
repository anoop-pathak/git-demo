<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class QuickbooksEntityErrorTransformer extends TransformerAbstract {

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
    public function transform($error) {
       $data = [
            'id'              =>   $error->id,
            'entity'          =>   $error->entity,
            'entity_id'       =>   $error->entity_id,
            'message'         =>   $error->message,
            'error_type'      =>   $error->error_type,
            'error_code'      =>   $error->error_code,
            'created_at'      =>   $error->created_at,
            'details'         =>   $error->details,
            'remedy'          =>   $this->getRemedy($error->error_code),
            'explanation'     =>   $this->getExplanation($error->error_code),
        ];

        return $data;
    }

    private function getRemedy($code)
    {
        $remedy = null;

        if(!$code) return $remedy;

        $remedies = config('qb.remedy');

        if(array_key_exists($code, $remedies)){
              $remedy = $remedies[$code];
        }
       return $remedy;
    }

    private function getExplanation($code)
    {
        $explanation = null;

        if(!$code) return $explanation;

        $explanations = config('qb.explanation');

        if(array_key_exists($code, $explanations)){
              $explanation = $explanations[$code];
        }
       return $explanation;
    }
}
