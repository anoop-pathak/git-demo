<?php
namespace App\Repositories;

use App\Models\UserFavouriteEntity;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\Auth;

class UserFavouriteEntityRepository extends ScopedRepository
{
	protected $model;
    protected $scope;

    public function __construct(UserFavouriteEntity $model, Context $scope){
		$this->scope = $scope;
		$this->model = $model;
	}

    /**
	 * save an entity in user favourites
	 * @param  String 	| $type     | type of entity
	 * @param  Int 		| $entityId | id of an entity
	 * @param  String 	| $name     | name of favourite
	 * @param  Array 	| $input    | array of input
	 * @return $favouriteEnity
	 */
	public function save($type, $entity, $name, $input)
	{
		$favouriteEnity = UserFavouriteEntity::firstOrNew([
			'type'			=> $type,
			'entity_id'		=> $entity->id,
			'marked_by'		=> Auth::id(),
			'company_id'	=> $this->scope->id(),
		]);

		$favouriteEnity->name = $name;
		$favouriteEnity->for_all_trades	= ine($input, 'for_all_trades');
		$favouriteEnity->worksheet_id	= $entity->worksheet_id;
		$favouriteEnity->save();

        if(isset($input['trade_ids']) && !$favouriteEnity->for_all_trades) {
			$favouriteEnity->trades()->sync((array)$input['trade_ids']);
		}

        return $favouriteEnity;
	}

    /**
	 * get filtered user favourite entities
	 * @param  array  | $filters | Array of inputs
	 * @return $entities
	 */
	public function getFilteredEntities($filters = [])
	{
		$with = $this->getIncludes($filters);
		$entities = $this->make($with)->excludeDeleted($filters);
		$this->applyFilters($entities, $filters);
		$entities->select('user_favourite_entities.*');

        return $entities;
	}

    /**
	 * rename an favourite entity
	 * @param  Object | $entity | Object of entity
	 * @param  String | $name   | new name of favourite
	 * @return $entity
	 */
	public function rename($entity, $name)
	{
		$entity->name = $name;
		$entity->save();

        return $entity;
	}

    /********** Private Functions **********/
	private function applyFilters($query, $filters)
	{
		if(ine($filters, 'trade_ids')) {
			$query->trades($filters['trade_ids']);
		}

        if(ine($filters,'type')) {
			$query->whereIn('user_favourite_entities.type', (array)$filters['type']);
		}

        if(isset($filters['multi_tier'])) {
			$query->worksheets($filters['multi_tier']);
		}

        if(Auth::user()->isAuthority()) {
			if(!ine($filters, 'all')) {
				$query->where('marked_by', Auth::id());
			}
		} else {
			$query->where('marked_by', Auth::id());
		}
    }

	private function getIncludes($input)
	{
		$with = [];

        if(!ine($input, 'includes')) {
            return $with;
        }

		$includes = (array)$input['includes'];

        if(in_array('trades', $includes)) {
			$with[] = 'trades';
		}

        if(in_array('proposal', $includes)) {
			$with[] = 'proposal';
		}

        if(in_array('estimate', $includes)) {
			$with[] = 'estimate';
		}

        if(in_array('material_list', $includes)) {
			$with[] = 'materialList';
		}

        if(in_array('work_order', $includes)) {
			$with[] = 'workOrder';
		}

        return $with;
	}
}