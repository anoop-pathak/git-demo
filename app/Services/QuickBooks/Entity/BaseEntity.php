<?php
namespace App\Services\QuickBooks\Entity;

use QuickBooksOnline\API\Data\IPPIntuitEntity;
use App\Services\QuickBooks\SynchEntityInterface;
use App;

abstract class BaseEntity{

    /**
     * name of the qbo entity based on qbo documentation
     *
     * @var string
     */
    private $entity = '';

    /**
     * reference to qbo service
     *
     * @var [type]
     */
    protected $service = null;

    /**
     * reference to quickbook data service
     */
    protected $dataService = null;

    public function __construct(){
        $this->entity = $this->getEntityName();
        $this->service = App::make('App\Services\QuickBooks\TwoWaySync\Service');
        $this->dataService = $this->service->getDataService();
    }

    /**
     * must return the name of the qbo entity
     *
     * @return string
     */
    abstract function getEntityName();

    /**
     * Provide implementation to fetch the  corresponding Jp entity
     *
     * @param int $qb_id
     * @return SynchEntityInterface
     */
    abstract function getJpEntity($qb_id);
    /**
     * get qbo entity of entity id
     *
     * @param [type] $id
     * @return IPPIntuitEntity
     */
    public function get($id){
        return $this->dataService->FindbyId($this->entity, $id);
    }

    /**
     * Execute a QBO query and return result
     *
     * @param string $query
     * @return void
     */
    public function query($query){
        $result = $this->dataService->Query($query);
        return $result;
    }

    /**
     * Return paginated response for the query
     *
     * @param string $where complete where part as a string e.g "active=0 and name like '%abd%'"
     * @param string $order complete order part as a string e.g "id desc"
     * @param integer $limit
     * @param integer $page
     * @return array
     */
    public function paginate($limit = 20, $page = 1, $fields = '*',  $where = '',  $order = 'id asc'){
        $page = $page;
        $start_position = ($page - 1)* $limit;
        if($where != ''){
            $where = "where ".$where;
        }

        $query = sprintf("SELECT %s FROM %s %s ORDERBY %s STARTPOSITION %s MAXRESULTS %s", $fields, $this->entity, $where, $order, $start_position, $limit);
        $data = $this->query($query);

        $paginator = [];
        if(count($data) == $limit){
            $paginator['next'] = $page + 1;
        }

        if($page > 1){
            $paginator['prev'] = $page - 1;
        }

        return [
            'data' => $data,
            'paginate' => $paginator
        ];
    }

    public function getAll(){

        $nextPage = 1;
        $entities = [];
        while($nextPage){
            $pagedEntities = $this->paginate(1000, $nextPage);
            $entities = array_merge($entities,  (array) $pagedEntities['data']);
            $nextPage = (isset($pagedEntities['paginate']['next'])) ? $pagedEntities['paginate']['next'] : null;
        }
        return $entities;
    }

    /**
     * Persist the resource in quickbook. This results in actuall http call to quickbook
     *
     * @param QuickBooksOnline\API\Data\IPPIntuitEntity $resource
     * @return IPPIntuitEntity
     */
    protected function add($resource){
        $entity = $this->dataService->add($resource);

        return $entity;
    }

    /**
     * Persist the updations in a resource. This results in actual http to quicktook to update the entity
     *
     * @param QuickBooksOnline\API\Data\IPPIntuitEntity $resource
     * @return IPPIntuitEntity
     */
    protected function update($resource){
        $entity = $this->dataService->update($resource);

        return $entity;
    }

    /**
     * This results in actual deletion of the entity from Quickbooks.
     *
     * @param QuickBooksOnline\API\Data\IPPIntuitEntity $resource
     * @return IPPIntuitEntity
     */
    protected function delete($resource){
        $entity = $this->dataService->delete($resource);
        return $entity;
    }

    protected function softDelete($resource){
        $resource->Active = False;
        $this->update($resource);
    }

    public function getLastErrors(){
        $this->dataService->getLastError();
    }

    /**
     * Convert the entity to array representation.
     *
     * @param QuickBooksOnline\API\Data\IPPIntuitEntity $resource
     * @return IPPIntuitEntity
     */
    public function toArray($resource){
       return $this->service->toArray($resource);
    }

    /**
     * Link jp and qbo entity by saving reference
     *
     * @param SynchEntityInterface $entity
     * @param IPPIntuitEntity $qboEntity
     * @return void
     */
    protected function linkEntity(SynchEntityInterface $entity, IPPIntuitEntity $qboEntity){
        /**
         * @todo change the implementation of this function such that update events for this entity are not fired.
         * e.g DB::table('')->update([]);
         */
        $entity->quickbook_id = $qboEntity->Id;
        $entity->quickbook_sync_token = $qboEntity->SyncToken;
        $entity->quickbook_sync_status = 1;
        $entity->save();
    }

    /**
     * Verify if entity with given qbo id exists in JP
     *
     * @param [type] $qb_id
     * @return boolean
     */
    public function isQboEntitySynched($qb_id){
		$account = $this->getJpEntity($qb_id);
		if($account){
			return true;
		}
		return false;
	}


}