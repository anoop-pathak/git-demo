<?php 
namespace App\Repositories;

use App\Models\ProposalViewer;
use DB;
use App\Services\Contexts\Context;

class ProposalViewersRepository extends ScopedRepository {
 	/**
     * The base eloquent Proposal Viewer
     * @var Eloquent
     */
 	protected $model;
 	protected $scope;
 	function __construct(ProposalViewer $model, Context $scope)
 	{
 		$this->model = $model;
 		$this->scope = $scope;
 	}
 	/**
	 * @param  $title
	 * @param  $description
	 * @param  $isActive
	 * @return $proposalViewer
	 */
 	public function save($title, $description, $isActive)
 	{
 		$data = [
 			'company_id' 	=> getScopeId(),
 			'title'			=> $title,
 			'description' 	=> $description,
 			'is_active' 	=> (bool)$isActive,
 			'display_order' => $this->getPreviousDisplayOrder(),
 		];
 		$proposalViewer = $this->model->create($data);
 		return $proposalViewer;
 	}
 	/**
	 * @return $record
	 */
 	public function getPreviousDisplayOrder()
 	{
 		$record = $this->make()->latest('display_order')->first();
 		if(!$record) return 1;
 		return $record->display_order + 1;
 	}
 	/**
	 * @return $query
	 */
 	public function getListing($filters)
 	{
 		$proposals = $this->make();
 		$this->applyFilters($proposals, $filters);
 		$proposals->sortable();
 		if(!isset($filters['sort_by']) || !isset($filters['sort_order'])) {
 			return ProposalViewer::whereCompanyId($this->scope->id())->orderBy('display_order', 'asc');
 		}
 		return $proposals;
 	}
 	private function applyFilters($query, $filters)
 	{
 		if(isset($filters['is_active'])) {
 			$query->where('is_active', (bool)$filters['is_active']);
 		}
 	}
 	/**
	 * @param  $proposalViewer
	 * @param  $title
	 * @param  $description
	 * @param  $isActive
	 * @return $proposalViewer
	 */
 	public function update($proposalViewer, $title, $description, $isActive)
 	{
 		$data = [
 			'title' 		=> $title,
 			'description' 	=> $description,
 			'is_active'		=> (bool)$isActive,
 		];
 		$proposalViewer->update($data);
 		return $proposalViewer;
 	}
 	/**
	 * @param  $updateActivity
	 * @param  $isActive
	 * @return $updateActivity
	 */
 	public function active($proposalViewer, $isActive)
 	{
 		$proposalViewer->is_active = (bool)$isActive;
 		$proposalViewer->update();
 		return $proposalViewer;
 	}
 	/**
	 * @param $proposalViewer
	 * @param $input
	 * @return $proposalViewer
	 */
 	public function changeDisplayOrder($proposalViewer,$input)
 	{
 		$currentOrder = $proposalViewer->display_order;
 		$destinationOrder    = $input['display_order'];
 		if($currentOrder == $destinationOrder) {
 			return $proposalViewer;
 		}
 		if($currentOrder < $destinationOrder) {
 			$updateDisplayOrder = $this->make()->whereBetween('display_order', [$currentOrder, $destinationOrder]);
 			$updateDisplayOrder->decrement('display_order');
 		}
 		else {
 			$updateDisplayOrder = $this->make()->whereBetween('display_order', [$destinationOrder, $currentOrder]);
 			$updateDisplayOrder->increment('display_order');
 		}
 		$proposalViewer = $proposalViewer->update(['display_order' => $destinationOrder]);
 		return $proposalViewer;
 	}
 } 