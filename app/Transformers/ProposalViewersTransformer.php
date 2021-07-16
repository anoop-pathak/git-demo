<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class ProposalViewersTransformer extends TransformerAbstract
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
    protected $availableIncludes = [
    	'description'
    ];
    public function transform($proposalViewer)
    {
      $data = [
         'id'  			=> $proposalViewer->id,
         'company_id' 	=> $proposalViewer->company_id,
         'title' 		=> $proposalViewer->title,
         'display_order' => $proposalViewer->display_order,
         'is_active'		=> $proposalViewer->is_active,
     ];
     return $data;
 }
 	/**
     * Include description
     */
    public function includeDescription($proposalViewer)
    {
    	return $this->item($proposalViewer, function($proposalViewer) {
            return [
                'description' => $proposalViewer->description
            ];
        });
    }
}