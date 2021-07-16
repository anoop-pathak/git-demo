<?php

namespace App\Repositories;

use App\Events\NewAnnouncement;
use App\Models\Announcement;
use Illuminate\Support\Facades\Event;

class AnnouncementsRepository extends AbstractRepository
{

    protected $model;

    function __construct(Announcement $model)
    {
        $this->model = $model;
    }

    public function createAnnouncement($title, $description, $trades, $otherData = [])
    {
        $announcement = $this->model;
        $announcement->title = $title;
        $announcement->description = $description;
        $announcement->for_all_trades = isset($otherData['for_all_trades']) ? (bool)$otherData['for_all_trades'] : false;
        $announcement->save();

        if (!(bool)$announcement->for_all_trades) {
            $announcement->trades()->attach($trades);
        }

        //New announcement event..
        Event::fire('JobProgress.Announcements.Events.NewAnnouncement', new NewAnnouncement($announcement, $otherData));
        return $announcement;
    }

    public function getFilteredAnnouncements($filters = [])
    {
        $announcements = $this->model
            ->query()
            ->orderBy('id', 'desc');
        $this->applyFilters($announcements, $filters);
        return $announcements;
    }

    /***************** Private function *****************/

    private function applyFilters($query, $filters)
    {
        if (ine($filters, 'trades')) {
            $query->where(function ($query) use ($filters) {
                $query->whereIn('id', function ($query) use ($filters) {
                    $query->select('announcement_id')->from('announcement_trade')->whereIn('trade_id', $filters['trades']);
                })->orWhere('for_all_trades', true);
            });
        }

        if (ine($filters, 'title')) {
            $query->where('title', 'Like', '%' . $filters['title'] . '%');
        }
    }
}
