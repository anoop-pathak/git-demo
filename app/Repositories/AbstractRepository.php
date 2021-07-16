<?php namespace App\Repositories;

use Illuminate\Support\Facades\DB;

abstract class AbstractRepository
{

    /**
     * Return all users
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function all()
    {
        return $this->model->all();
    }

    /**
     * Make a new instance of the entity to query on
     *
     * @param Array $with
     */
    public function make(array $with = [])
    {
        return $this->model->with($with);
    }

    /**
     * Find a single entity by key value
     *
     * @param String $key
     * @param String $value
     * @param Array $with
     */
    public function getFirstBy($key, $value, array $with = [])
    {
        return $this->make($with)->where($key, '=', $value)->first();
    }

    /**
     * Find many entities by key value
     *
     * @param String $key
     * @param String $value
     * @param Array $with
     */
    public function getManyBy($key, $value, array $with = [])
    {
        return $this->make($with)->where($key, '=', $value)->get();
    }

    /**
	 * Created function to append range filtering.
	 * Is only single value from the range is available then create query according to that value and ignore empty value.
	 *
	 * @param Eloquent $builder
	 * @param String $field
	 * @param Mix $start
	 * @param Mix $end
	 */
	protected function appendRangeFilter($builder, $field, $start = null, $end = null)
	{
		if(!$start && !$end) {
			return $builder;
		}
		if($start && $end) {
			return $builder->whereBetween(DB::Raw($field), [$start, $end]);
		}

		if($start) {
			return $builder->where(DB::Raw($field), '>=', $start);
		}

		return $builder->where(DB::Raw($field), '<=', $end);
	}

	/**
	 * Created function to append range filtering.
	 * Is only single value from the range is available then create query according to that value and ignore empty value.
	 *
	 * @param Eloquent $builder
	 * @param String $field
	 * @param Mix $start
	 * @param Mix $end
	 */
	protected function appendCustomRangeFilter($builder, $field, $start = null, $end = null)
	{
		if(!$start && !$end) {
			return $builder;
		}
		if($start && $end) {
			// return $builder->whereBetween($field, [$start, $end]);
			return $builder->where(function($q) use($field, $start, $end) {
				$q->where(DB::Raw($field), '>=', $start)
					->where(DB::Raw($field), '<=', $end);
			});
		}

		if($start) {
			return $builder->where(DB::Raw($field), '>=', $start);
		}

		return $builder->where(DB::Raw($field), '<=', $end);
	}
}
