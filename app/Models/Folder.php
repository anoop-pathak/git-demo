<?php
namespace App\Models;

use Carbon\Carbon;
use Nicolaslopezj\Searchable\SearchableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\Contexts\Context;

class Folder extends BaseModel {

	use SoftDeletes;
	use SearchableTrait;

	protected $table = 'folders';
	protected $fillable = [
		'parent_id', 'company_id', 'job_id', 'type', 'reference_id', 'name', 'is_auto_deleted',
		'path', 'is_dir', 'created_by', 'updated_by', 'deleted_by', 'deleted_at'
	];

	protected $dates = ['deleted_at'];

	protected $rules = [
		'path' => 'required',
		'type' => 'required',
		'name' => 'required',
	];

	protected $updateRules = [
		'name' => 'required',
	];

	const DEFUALT_JOBS_DIR_LABEL = 'jobs';
	const DEFUALT_TEMPLATES_DIR_LABEL = 'templates';

	const TEMPLATE_TYPE_PREFIX = "template_";
	const JOB_ESTIMATION = 'estimations';
	const JOB_PROPOSAL = 'proposals';
	const JOB_MEASUREMENT = 'measurements';
    const JOB_WORK_ORDER = 'work_order';
	const JOB_MATERIAL_LIST = 'material_list';

	protected $searchable;

	public static function boot() {
        parent::boot();

        // We set the deleted_by attribute before deleted event so we doesn't get an error if Folder was deleted by force (without soft delete).
       	static::deleting(function($item){
			$item->deleted_by = Auth::user()->id;
            $item->save();
		});

		// We set the deleted_by attribute before deleted event so we doesn't get an error if Folder was deleted by force (without soft delete).
		static::restoring(function($item){
			$item->deleted_by = null;
            $item->save();
        });

        // after save event
        static::created(function($item){

			$scope = \App::make(Context::class);
			if($scope->has() && !$item->company_id) {
				$item->company_id = $scope->id();
			}

			if($item->parent_id) {
				$item->path = ($item->path) ?  $item->path . "/" . $item->parent_id	: $item->parent_id;
			}

			if(Auth::user()) {
				$item->created_by = Auth::user()->id;
				$item->updated_by = Auth::user()->id;
			}
            $item->save();
		});
	}

	public function children()
	{
		return $this->hasMany(Folder::class,'parent_id', 'id');
	}

	public function dir_children()
	{
		return $this->hasMany(Folder::class,'parent_id', 'id')->where('is_dir', true);
	}

	public function doc_children()
	{
		return $this->hasMany(Folder::class,'parent_id', 'id')->where('is_dir', false);
	}

	public function user() {
		return $this->belongsTo(User::class,'created_by');
	}

	public function createdBy()
	{
		return $this->belongsTo(User::class, 'created_by', 'id')->withTrashed();
	}

	public function deletedBy()
	{
		return $this->belongsTo(User::class, 'deleted_by', 'id')->withTrashed();
	}

	public function getCreatedAtAttribute($value) {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value) {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

	protected function getRules()
	{
		return $this->rules;
	}

	protected function getUpdateRules()
	{
		return $this->updateRules;
	}

	public function scopeIsDir($query)
	{
		return $query->where('is_dir', true);
	}

	public function scopeWhereName($query, $value = null)
	{
		if(!$value) {
			return $query;
		}
		return $query->where('name', $value);
	}

	public function scopeWhereType($query, $type = null)
	{
		if(!$type) {
			return $query;
		}
		return $query->where('type', $type);
	}

	public function scopeWhereExceptID($query, $id = null)
	{
		if(!$id) {
			return $query;
		}
		return $query->where('id', '<>', $id);
	}

	public function scopeWhereReferenceID($query, $id = null)
	{
		if(!$id) {
			return $query;
		}
		return $query->where('reference_id', $id);
	}

	public function scopeWhereParentID($query, $id = null)
	{
		if(!$id) {
			return $query;
		}
		if(is_array($id)) {
			return $query->whereIn('parent_id', (array)$id);
		}
		return $query->where('parent_id', $id);
	}

	public function scopeWhereIsDirectory($query, $value = true)
	{
		return $query->where('is_dir', (bool)$value);
	}

	public function scopeWhereCreatedBy($query, $value)
	{
		return $query->where('created_by', $value);
	}

	public function scopeWhereJobId($query, $jobId = null)
	{
		if(!$jobId) {
			return $query;
		}
		return $query->where('job_id', $jobId);
	}

	public function ancestors()
	{
		if(!$this->path) {
			return [];
		}
		$arrPath = explode('/', $this->path);
		if(!$arrPath) {
			return [];
		}

		if($this->company_id) {
			array_shift($arrPath);
		}

		if(!$this->job_id) {
			array_shift($arrPath);
			array_shift($arrPath);
		}

		if($this->job_id) {
			array_shift($arrPath);
			array_shift($arrPath);
			array_shift($arrPath);
		}

		$folders = Folder::whereIn('id', $arrPath)->select('id', 'parent_id', 'name')->get();
		$ancestors = [];
		foreach ($arrPath as $key => $id) {
			$item = $this->findItemById($id, $folders);
			$ancestors[] = [
				'id' => $item->id,
				'parent_id' => $item->parent_id,
				'name' => $item->name,
			];
		}
		return $ancestors;
	}

	public function setSearchableColumns($arr = [])
	{
		$this->searchable = $arr;
		return $this;
	}

	/**
	 * append query to fetch system Templates / Folders
	 *
	 * @param Eloquent $builder
	 * @return Eloquent
	 */
	public function scopeSystem($builder)
	{
		$tableName = $this->getTable();
		$builder->where(function($query) use($tableName) {
			$query->whereNull("$tableName.company_id")
				->orWhere("$tableName.company_id", 0);
		});
	}

	/**
	 * append query to fetch custom Templates / Folders
	 *
	 * @param Eloquent $builder
	 * @return Eloquent
	 */
	public function scopeCustom($builder, $companyId)
	{
		$tableName = $this->getTable();
		$builder->where(function($query) use($tableName, $companyId) {
			$query->where("$tableName.company_id",$companyId)
				->orWhere(function($query) use($tableName) {
					$type = self::TEMPLATE_TYPE_PREFIX . "blank";
					$query->whereNull("$tableName.company_id")->where("$tableName.type", '=', $type);
				});
		});
	}

	/**
	 * append query to fetch Templates / Folders with Custom
	 *
	 * @param Eloquent $builder
	 * @return Eloquent
	 */
	public function scopeWithCustom($builder, $companyId)
	{
		$tableName = $this->getTable();
		$builder->where(function($query) use($tableName, $companyId){
			$query->whereNull("$tableName.company_id")
				->orWhere("$tableName.company_id", 0)
				->orWhere("$tableName.company_id",$companyId);
		});
	}

	public function isTemplateProposal()
	{
		return $this->type == self::TEMPLATE_TYPE_PREFIX . Template::PROPOSAL;
	}

	public function isTemplateEstimate()
	{
		return $this->type == self::TEMPLATE_TYPE_PREFIX . Template::ESTIMATE;
	}

	public function isTemplateBlank()
	{
		return $this->type == self::TEMPLATE_TYPE_PREFIX . Template::BLANK;
	}

	/**
	 * Sortable functionality.
	 *
	 * @param QueryBuilder $query
	 * @return QueryBuilder
	 */
	public function scopeSortable($query)
	{
		$supportedFields = [
			'title' => 'folders.name',
			'deleted_at' => 'folders.deleted_at',
		];
        if(\Request::has('sort_by') && \Request::has('sort_order')) {
    		$sortBy = \Request::get('sort_by');
    		$sortOrder = \Request::get('sort_order');

			if(ine($supportedFields, $sortBy)) {
				$sortBy = $supportedFields[$sortBy];
				$query->addSelect(DB::raw("IF($sortBy IS NOT NULL, $sortBy, 'null')"));
				return $query->orderBy($sortBy, $sortOrder);
			}
			return $query->orderBy('id', 'asc');
		} else {
			return $query->orderBy('is_dir', 'desc')->orderBy('folders.name', 'asc');
            return $query;
		}
    }

	private function findItemById($id, $items)
	{
		if(!$items) {
			return [];
		}

		foreach ($items as $key => $item) {
			if($id == $item->id) {
				return $item;
			}
		}
		return [];
	}
}