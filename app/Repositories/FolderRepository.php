<?php namespace App\Repositories;

use App\Models\Folder;
use Exception;
use App\Services\Contexts\Context;
use App\Repositories\ScopedRepository;
use Illuminate\Http\Response as IlluminateResponse;
use App\Exceptions\Folders\FolderNotExistException;
use App\Exceptions\Folders\DuplicateFolderException;

class FolderRepository extends ScopedRepository
{
	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;

	function __construct(Folder $model, Context $scope)
	{
		$this->scope = $scope;
		$this->model = $model;
	}

	public function get($filters = [])
	{
		$builder = $this->make();

		if(ine($filters, 'ids')) {
			$builder->whereIn('id', (array)$filters['ids']);
		}

		if(ine($filters, 'reference_ids')) {
			$builder->whereIn('reference_id', (array)$filters['reference_ids']);
		}

		if(ine($filters, 'parent_id')) {
			$builder->whereParentID($filters['parent_id']);
		}

		if(ine($filters, 'type')) {
			$builder->whereType($filters['type']);
		}

		if(ine($filters, 'job_id')) {
			$builder->whereJobId($filters['job_id']);
		}

		if(ine($filters, 'is_dir')) {
			$isDir = (bool)$filters['is_dir'];
			$builder->whereIsDirectory($isDir);
		}

		return $builder->get();
	}
	/**
     * Find item by id and type.
     *
     * @param Integer $id: integer of folder id.
     * @param String $type: string of entity type like proposal/estimate.
     * @return Folder Model instance.
     */
    public function findByIdAndType($id, $type = null)
    {
        return Folder::where('id', $id)->whereType($type)->first();
	}

	public function isValidParentAndEntityType($parentId, $entity)
	{
		$builder = Folder::where('parent_id', $parentId)
						->where('path', 'LIKE', "$entity%");

		return $builder->count();
	}

	/**
     * Find parent ID on the basis of parentPath.
     *  If parent is not exists then create new entity on the basis of parent path.
     *
     * @param String $name: string of folder name.
     * @param String $parentPath: string of breadcrum path of parent.
     * @param String $type: string of entity type like proposal/estimate/etc.
     * @param Array $options: array of additional data.
     * @return integer of entity root id.
     */
	public function findOrCreateByName($name, $parentId = null, $type = null, $options = [])
    {
		$isDir = isSetNotEmpty($options, 'is_dir');
		$exists = $this->findByNameAndParentId($name, $parentId, $type, $isDir);
		if($exists) {
			return $exists->id;
		}

		$jobId = isSetNotEmpty($options, 'job_id');
		$inputs = [
			'parent_id' => $parentId,
			'job_id' => $jobId,
			'name' => $name,
			'type' => $type,
			'path' => $this->getParentNodePath($parentId),
			'is_dir' => true,
		];
		$data =$this->setPayload($inputs);
		$item = Folder::create($data);
		return $item->id;
	}

	/**
	 * find node by name and parent id.
	 *
	 * @param String $name: string of folder/file name.
	 * @param Integer $parentId: (optional) integer of parent id.
	 * @param String $type: string of entity type like proposal/estimate/etc.
	 * @return Folder model instance.
	 */
	public function findByNameAndParentId($name, $parentId = null, $type = null, $isDir = false)
	{
		$builder = $this->make();
		$builder = $builder->whereName($name)
							->whereType($type)
							->whereParentID($parentId);

		if($isDir) {
			$builder = $builder->whereIsDirectory();
		}

		return $builder->first();
	}

	/**
	 * find system folder/templates root id.
	 *
	 * @return Folder model instance.
	 */
	public function findSystemRootId($type)
	{
		$builder = Folder::system();
		$parentTemplate = $builder->whereName(Folder::DEFUALT_TEMPLATES_DIR_LABEL)
								->whereNull('parent_id')
								->first();
		if(!$parentTemplate) {
			return null;
		}

		$builder = Folder::system();
		$typeParent = $builder->whereName($type)
								->whereParentId($parentTemplate->id)
								->first();

		return $typeParent->id;
	}

	/**
	 * Folder Save
	 *
	 * @param  Array $inputs array of input parameters
	 * @return Folder
	 */
	public function store($inputs)
	{
		$inputs['path'] = $this->getParentNodePath($inputs['parent_id']);
		$inputs['is_dir'] = true;

		$data = $this->setPayload($inputs);
		return $this->model->create($data);
	}

	/**
     * store file inside requested folder.
     *
     * @param Integer $folderId: integer of folder id.
     * @param Integer $referenceId: integer of reference id.
     * @param String $name: string of file name.
     */
	public function storeFile($parentId, $referenceId, $name, $metas = [])
    {
		$type = isset($metas['type']) ? $metas['type'] : null;
		$jobId = isset($metas['job_id']) ? $metas['job_id'] : null;
		$inputs = [
			'parent_id' => $parentId,
			'reference_id' => $referenceId,
			'job_id' => $jobId,
			'name' => $name,
			'type' => $type,
			'is_dir' => false,
			'path' => $this->getParentNodePath($parentId),
		];

		$data = $this->setPayload($inputs);
		return $this->model->create($data);
    }

	/**
	 * Folder Update
	 *
	 * @param Integer $id: integer of folder id.
	 * @param Array $inputs array of input parameters
	 * @return Folder
	 */
	public function update($id, $inputs)
	{
		$data = [
			'name' => $inputs['name'],
			'updated_by' => $inputs['updated_by'],
		];
		$type = isset($inputs['type']) ? $inputs['type'] : null;
		$item = $this->findByIdAndType($id, $type);
		$item->fill($data);
		$item->save();

		return $item;
	}

	/**
     * Soft delete the specified resource from storage.
	 * 	Resource can only soft delete if empty.
     *
     * @param Integer $id: integer folder id.
     * @return Folder Model instance
     */
    public function delete($id)
    {
		$builder = $this->make()->where('id', $id);

		$item = $builder->first();
		if(!$item) {
			throw new FolderNotExistException("Invalid Folder.", IlluminateResponse::HTTP_PRECONDITION_FAILED);
		}
		return $item->delete();
	}

	/**
     * Create functionality to find and restore folder/file.
     *
     * @param Integer $id: integer of folder/file id.
     * @return boolean(true/false)
     */
    public function restore($id)
    {
		$builder = $this->make();
		$builder = $builder->where('id', $id)->onlyTrashed();

		$item = $builder->first();
		if(!$item) {
			throw new FolderNotExistException("Invalid Folder.", IlluminateResponse::HTTP_PRECONDITION_FAILED);
		}
		$metas = ['type' => $item->type];
		$this->isUnique($item->parent_id, $item->name, $metas);
		$item->restore();
		return $item;
	}

	/**
	 * Delete file on the basis of Reference id and parent id.
	 * 	Internally also added check to match company id with the logged In user company id.
	 *
	 * @param Interger $referenceId: integer of reference id.
	 * @param String $type: string of type estimations/proposals/etc.
	 * @param Integer $companyId: integer of company id.
	 * @return boolean (true/false)
	 */
	public function deleteFileByRefAndType($referenceId, $type, $companyId = null)
    {
		$builder = $this->model;

		if($companyId) {
			$table = $this->model->getTable();
			$builder = $builder->where($table.".company_id", "=", $companyId);
		}
		$builder = $builder->whereReferenceID($referenceId)
						->whereType($type)
						->whereIsDirectory(false);

		$item = $builder->first();
		if(!$item) {
			throw new Exception("Invalid File.", IlluminateResponse::HTTP_PRECONDITION_FAILED);
		}
		return $item->delete();
	}

	/**
	 * restore file on the basis of Reference id and type.
	 * 	Internally also added check to match company id with the logged In user company id.
	 *
	 * @param Interger $referenceId: integer of reference id.
	 * @param String $type: string of type estimations/proposals/etc.
	 * @param Integer $companyId: integer of company id.
	 * @return boolean (true/false)
	 */
	public function restoreFileByRefAndType($referenceId, $type, $companyId = null)
    {
		$builder = $this->model;

		if($companyId) {
			$table = $this->model->getTable();
			$builder = $builder->where($table.".company_id", "=", $companyId);
		}
		$builder = $builder->whereReferenceID($referenceId)
						->whereType($type)
						->whereIsDirectory(false)
						->onlyTrashed();

		$item = $builder->first();
		if(!$item) {
			throw new Exception("Invalid File.", IlluminateResponse::HTTP_PRECONDITION_FAILED);
		}
		$item->restore();
		$item->deleted_by = null;
		$item->save();
		return $item;
    }

	/**
	 * check is folder name unique
	 *
	 * @param Integer $parentId: integer of parent id.
	 * @param String $name: string of folder name.
	 * @param Array $metas: array of meta fields.
	 * @param Integer $exceptId: integer of unique id.
	 * @return boolean(true/false)
	 */
	public function isUnique($parentId, $name, $metas = [], $exceptId = null)
    {
		$type = isset($metas['type']) ? $metas['type'] : null;
		$builder = $this->make();
		$builder = $builder->where('parent_id', $parentId)
						->where('name', $name)
						->whereType($type)
						->whereExceptID($exceptId);

		if($builder->count()) {
			throw new DuplicateFolderException("The name has already been taken.");
		}

		return true;
    }

	/**
     * check folder id deletable or not.
     *
     * @param Array $inputs: array of requested fields.
     * @return Folder Model instance.
     */
    public function isFolderDeletable($id)
    {
		$builder = $this->make();
		$exists = $builder->whereParentID($id)->count();

		if($exists) {
			return false;
		}
		return true;
	}

	/**
	 * Check is requested id is Dir or File
	 *
	 * @param integer of item id.
	 * @return boolean(true/false)
	 */
	public function isDir($id)
	{
		$builder = $this->make();
		return $builder->where('id', $id)
					->isDir()
					->count();
	}

	/**
	 * get parent node path.
	 *
	 * @param Integer $parentId: integer of parent id.
	 * @return String of path to the node.
	 */
	public function getParentNodePath($parentId = null)
	{
		if(!$parentId) {
			return null;
		}

		$parent = $this->findById($parentId);
		if(!$parent) {
			return null;
		}

		return $parent->path;
	}

	/**
	 * get parent directory by id and type
	 * @param  Integer 	| $id	| Id of a Folder
	 * @param  String 	| $type | folder type like proposal,estimate
	 * @param  Integer 	| $jobId | (optional) integer of job id.
	 * @return $parentDir
	 */
	public function getParentDir($id, $type = null, $jobId = null)
	{
		$builder = $this->make();
		$parentDir =  $builder->where('id', $id)
						->where(function($query) use($type) {
							$query->where('type', $type)
								->orWhereNull('type');
						})
						->whereJobId($jobId)
						->isDir()
						->first();

		return $parentDir;
	}

	/**
	 * set input fields payload for saving.
	 * 	in this function set company id.
	 *
	 * @param Array $inputs: array of requested data.
	 * @return array of filtered data.
	 */
	protected function setPayload($inputs)
	{
		$fields = [
			'parent_id'     => null,
			'reference_id'  => null,
			'job_id'  		=> null,
			'type'      	=> null,
			'name'      	=> $inputs['name'],
			'path'      	=> null,
			'is_dir'      	=> false,
		];

		$fields['type'] = isset($inputs['type']) ? $inputs['type'] : null;

		if(ine($inputs, 'parent_id')) {
			$fields['parent_id'] = $inputs['parent_id'];
		}
		if(ine($inputs, 'reference_id')) {
			$fields['reference_id'] = $inputs['reference_id'];
		}
		if(ine($inputs, 'is_dir')) {
			$fields['is_dir'] = $inputs['is_dir'];
		}

		if(ine($inputs, 'job_id')) {
			$fields['job_id'] = $inputs['job_id'];
		}

		if(ine($inputs, 'path')) {
			$fields['path'] = $inputs['path'];
		}
		return $fields;
	}
}