<?php namespace App\Repositories;

abstract class ScopedRepository extends AbstractRepository
{

    public function make(array $with = [])
    {
        $entity = parent::make($with);

        if ($this->scope->has()) {
            $table = $this->model->getTable();
            $entity->where($table . "." . $this->scope->column(), "=", $this->scope->id());
        }
        return $entity;
    }

    /**
     * Find an entity by id
     *
     * @param int $id
     * @param array $with
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getById($id, array $with = [])
    {
        $query = $this->make($with);

        return $query->findOrFail($id);
    }

    /**
     * Find an entity by id
     *
     * @param int $id
     * @param array $with
     * @return Illuminate\Database\Eloquent\Model
     */
    public function findById($id, array $with = [])
    {
        $query = $this->make($with);

        return $query->whereId($id)->first();
    }

    /**
     * Get Scope Id
     * @return [type] [description]
     */
    public function getScopeId()
    {
        if ($this->scope->has()) {
            return $this->scope->id();
        }

        return false;
    }

    /**
     * Find an deleted emetity by id
     * @param int $id
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getDeletedById($id)
    {
        return $this->make()->onlyTrashed()->findOrFail($id);
    }

    public function getByIdWithTrashed($id)
	{
		return $this->make()->withTrashed()->findOrFail($id);
	}
}
