<?php

namespace App\Repositories;

use App\Models\ThirdPartyTool;

class ThirdPartyToolsRepository extends AbstractRepository
{

    protected $model;

    function __construct(ThirdPartyTool $model)
    {
        $this->model = $model;
    }

    public function saveTool($title, $description, $trades, $otherData = [])
    {
        $tool = $this->model;
        $tool->title = $title;
        $tool->description = $description;
        $tool->url = isset($otherData['url']) ? $otherData['url'] : null;
        $tool->image = isset($otherData['image']) ? $otherData['image'] : null;
        $tool->thumb = isset($otherData['thumb']) ? $otherData['thumb'] : null;
        $tool->for_all_trades = isset($otherData['for_all_trades']) ? (bool)$otherData['for_all_trades'] : false;
        $tool->save();

        if (!(bool)$tool->for_all_trades) {
            $tool->trades()->attach($trades);
        }
        return $tool;
    }

    public function getFilteredTools($filters = [])
    {
        $tools = $this->model
            ->query()
            ->orderBy('id', 'desc');
        $this->applyFilters($tools, $filters);
        return $tools;
    }

    /***************** Private function *****************/

    private function applyFilters($query, $filters)
    {
        if (ine($filters, 'trades')) {
            $query->where(function ($query) use ($filters) {
                $query->whereIn('id', function ($query) use ($filters) {
                    $query->select('third_party_tool_id')->from('third_party_tools_trades')->whereIn('trade_id', (array)$filters['trades']);
                })->orWhere('for_all_trades', true);
            });
        }

        if (ine($filters, 'title')) {
            $query->where('title', 'Like', '%' . $filters['title'] . '%');
        }
    }
}
