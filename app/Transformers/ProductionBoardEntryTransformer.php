<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class ProductionBoardEntryTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['productionBoardColumn', 'task'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($pbEntry)
    {
        return [
            'id' => $pbEntry->id,
            'column_id' => $pbEntry->column_id,
            'data' => $pbEntry->data,
            'created_at' => $pbEntry->created_at,
            'updated_at' => $pbEntry->updated_at,
            'color'        =>  $pbEntry->color,
        ];
    }

    public function includeProductionBoardColumn($pbEntry)
    {
        $pbColumn = $pbEntry->productionBoardColumn;

        if ($pbColumn) {
            return $this->item($pbColumn, new ProductionBoardColumnTransformer);
        }
    }

    /**
     * Include Task
     * 
     * @return League\Fractal\Item
     */
    public function includeTask($pbEntry)
    {
        $task = $pbEntry->task;
         $transformer = new TasksTransformer;
         $transformer->setDefaultIncludes(['participants']);
         if ($task) {
            return $this->item($task, $transformer);
        }
    }
}
