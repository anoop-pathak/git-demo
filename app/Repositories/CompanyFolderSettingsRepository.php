<?php
namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\CompanyFolderSetting;
use Carbon\Carbon;
use App\Exceptions\ParentFolderDoesNotExist;
use App\Exceptions\ParentFoldersDoesNotMatchedException;

class CompanyFolderSettingsRepository extends ScopedRepository {

	/**
     * The base eloquent Setting
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(CompanyFolderSetting $model, Context $scope)
    {
		$this->model = $model;
		$this->scope = $scope;
	}

	public function getFilteredFolders($input = [])
	{
		$parentId = ine($input, 'parent_id') ? $input['parent_id'] : null;

		$query = $this->make()
			->where('type', $input['type'])
			->where('parent_id', $parentId)
			->orderBy('position', 'asc');

		return $query;
	}

	public function getJobFolderSettings()
	{
		$input['type'] = CompanyFolderSetting::JOB_FOLDERS;

		$jobFolders = $this->getFilteredFolders($input)->get();

		return $jobFolders;
	}

	public function getCustomerFolderSettings()
	{
		$input['type'] = CompanyFolderSetting::CUSTOMER_FOLDERS;

		$jobFolders = $this->getFilteredFolders($input)->get();

		return $jobFolders;
	}

	public function save($input)
	{
		$parentId = null;
		if(ine($input, 'parent_id')) {
			$parent = $this->findById($input['parent_id']);
			if(!$parent) {
				throw new ParentFolderDoesNotExist("Parent folder does not exist.");
			}

			$parentId = $parent->id;
		}

		$builder = $this->make()
			->where('type', $input['type'])
			->where('parent_id', $parentId);

		$deletedFolderBuilder = clone $builder;

		$deletedRecord = $deletedFolderBuilder->onlyTrashed()->where('name', $input['name'])->first();

		if($deletedRecord) {
			return $this->restoreFolder(clone $builder, $deletedRecord, $parentId, $input['name'], $input['type']);
		}

		if($parentId) {
			$folder = $this->getById($input['parent_id']);
		}
		$data = [
			'type'        => $input['type'],
			'company_id'  => getScopeId(),
			'name'        => $input['name'],
			'position'    => $builder->withTrashed()->count() + 1,
			'locked'      => ine($input, 'locked') ? $input['locked'] : false,
			'parent_id'	  => $parentId,
		];

		$folder = $this->model->create($data);

		return $folder;
	}

	public function update($folder, $data)
	{
		$folder->name = $data['name'];
		$folder->update();

		return $folder;
	}

	public function updateOrder($folder, $destinationFolder)
	{
		if($folder->position == $destinationFolder->position) {

			return true;
		}

		if($folder->parent_id != $destinationFolder->parent_id) {
			throw new ParentFoldersDoesNotMatchedException("Both folders are not belonging to same parent.");
		}

		$query = $this->make()
			->where('parent_id', $folder->parent_id)
			->withTrashed();

		if($folder->position < $destinationFolder->position) {
			$query->where('position', '<=', $destinationFolder->position)
				->where('position', '>', $folder->position)
				->decrement('position');
		}else {
			$query->where('position', '>=', $destinationFolder->position)
				->where('position', '<', $folder->position)
				->increment('position');
		}

		$folder->position = $destinationFolder->position;
		$folder->save();

		return true;
	}

	public function addDefaultFolders($companyId, $ownerId)
	{
		$jobResources = config('settings.JOB_RESOURCES');
		$jobResourceFolders = $this->setDefaulFoldersPayload(
			$jobResources,
			$companyId,
			$ownerId,
			$ownerId,
			CompanyFolderSetting::JOB_FOLDERS
		);

		$customerResources = config('settings.CUSTOMER_RESOURCES');
		$companyResourceFolder = $this->setDefaulFoldersPayload(
			$customerResources,
			$companyId,
			$ownerId,
			$ownerId,
			CompanyFolderSetting::CUSTOMER_FOLDERS
		);

		$companyFolders = array_merge($companyResourceFolder, $jobResourceFolders);
		CompanyFolderSetting::insert($companyFolders);
	}

	/***** Private Section *****/

	private function restoreFolder($builder, $folder, $parentId, $name, $type)
	{
		$query = clone $builder;

		$lastFolder = $builder->withTrashed()->latest('position')->first();
		$position = 1;
		if($lastFolder) {
			$position = $lastFolder->position;
		}
		$query->withTrashed()->where('position', '>', $folder->position)->decrement('position');

		$folder->deleted_at = NULL;
		$folder->position = $position;
		$folder->save();

		return $folder;
	}

	private function setDefaulFoldersPayload($resources, $companyId, $createdBy, $updatedBy, $type)
	{
		$now = Carbon::now()->toDateTimeString();

		$ret = [];

		$latestRecord = CompanyFolderSetting::withTrashed()
			->where('company_id', $companyId)
			->where('type', $type)
			->orderBy('position', 'desc')
			->first();

		$position = 0;
		if($latestRecord) {
			$position = $latestRecord->position;
		}

		foreach ($resources as $key => $resource) {
			$existingRecord = CompanyFolderSetting::withTrashed()
				->where('company_id', $companyId)
				->where('type', $type)
				->where('name', $resource['name'])
				->first();

			if($existingRecord) {
				$existingRecord->locked = $resource['locked'];
				if($resource['locked']) {
					$existingRecord->deleted_at = null;
				}
				$existingRecord->save();
				continue;
			}

			$position = $position + 1;
			$ret[] = [
				'company_id'	=> $companyId,
				'name'			=> $resource['name'],
				'locked'		=> $resource['locked'],
				'type'			=> $type,
				'position'		=> $position,
				'created_by'	=> $createdBy,
				'updated_by'	=> $updatedBy,
				'created_at'	=> $now,
				'updated_at'	=> $now,
			];
		}

		return $ret;
	}
}