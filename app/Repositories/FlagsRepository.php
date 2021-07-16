<?php namespace App\Repositories;

use App\Models\Flag;
use App\Services\Contexts\Context;

class FlagsRepository extends AbstractRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;

    function __construct(Flag $model, Context $scope)
    {

        $this->scope = $scope;
        $this->model = $model;
    }

    /**
     * Get Filtered flags
     * @param  ARRAY $filters filters array
     * @return QueryBuilder
     */
    public function getFilteredFlags($filters, $sortable = true)
    {

        if ($sortable) {
            $query = $this->make()->sortable();
        } else {
            $query = $this->make();
        }

        $query->with(['color']);

        if ($this->scope->has()) {
            $companyId = $this->scope->id();
            $query->company($companyId);
            $query->excludeDeletedFlags();
        } else {
            $query->whereNull('company_id');
        }

        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * Flag Save
     * @param  String $title Flag title
     * @param  String $for Flag for
     * @return Flag
     */
    public function save($title, $for, $input = [])
    {
        $companyId = null;

        if ($this->scope->has()) {
            $companyId = $this->scope->id();
        }

        $flag = $this->model->create([
            'title' => $title,
            'for' => $for,
            'company_id' => $companyId,
        ]);

        if(isset($input['color']) && $companyId) {
			$flag = $this->saveColor($flag, $input['color']);
		}

        return $flag;
    }


    /**
     * Check flag already exist
     * @param  String $title Flag title
     * @param  String $for [Customer, Job]
     * @param  Int $excludedFlagId Flag Id
     * @return Count
     */
    public function isFlagExist($title, $for, $excludedFlagId = null)
    {
        $query = $this->make()->whereTitle($title)->whereFor($for);

        if ($this->scope->has()) {
            $query->company($this->scope->id());
        } else {
            $query->whereNull('company_id');
        }

        if ($excludedFlagId) {
            $query->where('id', '!=', $excludedFlagId);
        }

        $query->excludeDeletedFlags();

        return $query->count();
    }

    /**
     * Get Flag by Id
     * @param  Int $id Id
     * @return flag id
     */
    public function getById($id)
    {
        $query = $this->make()->whereId($id);

        if ($this->scope->has()) {
            $query->whereCompanyId($this->scope->id());
        } else {
            $query->whereNull('company_id');
        }

        $query->excludeDeletedFlags();

        return $query->firstOrFail();
    }

    /**
     * Get system flag by id
     * @param  int $id Flag Id
     * @return flag
     */
    public function getSystemFlagById($id)
    {
        $flag = $this->make();

        if ($this->scope->has()) {
            $flag->company($this->scope->id());
        }

        $flag->excludeDeletedFlags();

        $flag = $flag->findOrFail($id);

        return $flag;
    }

    /**
	 * save color of a flag
	 * @param  Flag 	| $flag  | Object of a Flag
	 * @param  String 	| $color | HEX code of a color
	 * @return $flag
	 */
	public function saveColor($flag, $color)
	{
		if(!$color) {
			$flag->color()->delete();

			return $flag;
		}

		if($flagColor = $flag->color) {
			$flagColor->color = $color;
			$flagColor->save();
		}else {
			$flagColor = $flag->color()->create([
				'color' => $color,
				'company_id' => getScopeId(),
			]);
			$flag->setRelation('color', $flagColor);
		}

		return $flag;
	}

	/***** Private Section *****/

    /**
     * Apply filters
     * @param  QueryBuilders $query Query builder
     * @param  Array $filters array
     * @return Void
     */
    private function applyFilters($query, $filters)
    {
        if (ine($filters, 'flag_for')) {
            $query->whereFor($filters['flag_for']);
        }
    }
}
