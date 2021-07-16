<?php
namespace App\Models;

use App\Services\Grid\SortableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Trade;

class YouTubeVideoLink extends BaseModel
{
	protected $fillable = ['company_id','title', 'created_by', 'video_id', 'for_all_trades'];
	protected $table = 'youtube_video_links';
	protected $appends = ['url'];
	protected $rules = [
		'title'			 => 'required',
		'url'			 => 'required',
		'trade_ids'		 => 'required_without:for_all_trades',
		'for_all_trades' => 'required_without:trade_ids',
    ];

	public function getUrlAttribute()
	{
		$url = 'https://www.youtube.com/embed/'.$this->video_id;
		return $url;
    }

	protected function getRules()
	{
		return $this->rules;
    }

	public function trades()
	{
		return $this->belongsToMany(Trade::class, 'youtube_video_link_trades', 'youtube_video_link_id', 'trade_id');
	}
}