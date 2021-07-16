<?php
namespace App\Models;

use App\Services\Grid\SortableTrait;
use App\Models\YouTubeVideoLink;

class Trade extends BaseModel
{

    use SortableTrait;

    CONST ROOFING_ID = 8;
    CONST SIDING_ID = 9;
    CONST PAINTING_ID = 13;

    protected $fillable = ['name'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $rules = [
        'name' => 'required'
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    public function job_types()
    {
        return $this->hasMany(JobType::class);
    }

    protected function getOtherTradeId()
    {
        $otherTrade = self::where('name', 'OTHER')->first();
        if ($otherTrade) {
            return $otherTrade->id;
        }
        return null;
    }

    public function jobs()
    {
        return $this->belongsToMany(Job::class, 'job_trade', 'trade_id', 'job_id');
    }

    public function workTypes()
    {

        $jobType = $this->hasMany(JobType::class)
            ->whereType(JobType::WORK_TYPES);

        if ($scopeId = getScopeId()) {
            $jobType->whereCompanyId($scopeId);
        }

        $jobType->orderBy('name', 'asc');

        return $jobType;
    }

    public function youtubeVideos() {
		return $this->belongsToMany(YouTubeVideoLink::class, 'youtube_video_link_trades', 'trade_id', 'youtube_video_link_id')->where('youtube_video_links.company_id', getScopeId());
	}

    public function scopeWithColor($query)
    {
        $companyId = getScopeId();

        if (!$companyId) {
            return;
        }

        $query->leftJoin('company_trade', function ($join) use ($companyId) {
            $join->on('company_trade.trade_id', '=', 'trades.id')
                ->where('company_trade.company_id', '=', $companyId);
        });

        $query->select('trades.id as id', 'trades.name', 'color');
    }

    public function scopeActiveTrades($query)
    {
    	$query->whereIn('trades.id', function($query) {
    		$query->select('trade_id')
    			->from('company_trade')
    			->where('company_trade.company_id', getScopeId());
    	});
    }

    public function getDefaultColor()
    {
        $key = $this->id;

        return config('trades-color.' . $key);
    }

    public function measurementValues()
    {
        return $this->hasMany(MeasurementAttribute::class)
            ->where('company_id', getScopeId())
            ->withTrashed();
    }

    public function measurementAttributes()
    {
        return $this->hasMany(MeasurementAttribute::class)
            ->where('company_id', getScopeId());
    }
}
