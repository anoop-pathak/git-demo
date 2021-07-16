<?php

namespace App\Repositories;

use App\Models\ProductionBoardColumn;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductionBoardColumnRepository extends ScopedRepository
{

    /**
     * The base eloquent ActivityLog
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(ProductionBoardColumn $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    public function save($name, $boardId, $order)
    {
        $data = [
            'company_id' => $this->scope->id(),
            'created_by' => \Auth::id(),
            'name' => $name,
            'board_id' => $boardId,
            'sort_order' => $order
        ];

        $column = $this->model->create($data);

        return $column;
    }

    public function getFilteredColumns($boardId, $filter = [], $sortable = true)
    {
        if ($sortable) {
            $query = $this->make()->Sortable();
            $query->orderBy('sort_order', 'asc');
        } else {
            $query = $this->make();
        }


        $query->where('board_id', $boardId);

        $this->applyFilters($query, $filter);

        return $query;
    }

    /**
     * Check ids is valid
     * @param  array $ids column ids
     * @return boolean
     */
    public function isValidIds($ids)
    {
        return !(ProductionBoardColumn::where('company_id', '!=', getScopeId())
            ->whereIn('id', $ids)
            ->exists());
    }

    /**
     * Update sort order
     * @param  array $ids column ids
     * @return Boolean
     */
    public function updateSortOrders(array $ids = [])
    {
        if (empty($ids)) {
            return false;
        }

        $caseString = 'CASE id';
        foreach ($ids as $key => $id) {
            $key++;
            $caseString .= " WHEN $id THEN $key";
        }

        $ids = implode(', ', $ids);
        DB::statement("UPDATE production_board_columns SET sort_order = $caseString END  WHERE id IN ($ids)");

        return true;
    }

    /**
	 * Restore Column
	 */
	public function getDeletedColumn($id)
	{
		return $this->model->withTrashed()
		    	->where('id', $id)
		    	->restore();
	}

    /************************** Private function ********************************/

    private function applyFilters($query, $filters = [])
    {
    }
}
