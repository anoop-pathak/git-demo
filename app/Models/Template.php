<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Request;
use App\Services\Grid\DivisionTrait;
use Nicolaslopezj\Searchable\SearchableTrait;

class Template extends Model
{

    use SortableTrait;
    use SoftDeletes;
    use DivisionTrait;
    use SearchableTrait;

    protected $fillable = [
        'type',
        'title',
        'created_by',
        'company_id',
        'option',
        'insurance_estimate',
        'for_all_trades',
        'all_divisions_access'
    ];

    protected $dates = ['deleted_at'];

    const ESTIMATE = 'estimate';
    const PROPOSAL = 'proposal';
    const BLANK = 'blank';

    protected static $rules = [
        'insurance_estimate' => 'boolean',
        'type' => 'required|in:estimate,proposal',
        'title' => 'required',
        'for_all_trades' => 'boolean',
        'trades' => 'required_without:for_all_trades|required_if:for_all_trades,0|array|nullable',
        'pages' => 'required|array'
    ];

    protected static $changeTypeRules = [
        'type' => 'required|in:estimate,proposal',
    ];

    protected $moveTemplateRules = [
        'ids' => 'required',
        'company_id' => 'required',
        'password' => 'required',
    ];

    protected function getMoveTemplateRules()
    {
        return $this->moveTemplateRules;
    }

    public static function getRules()
    {
        $input = Request::all();
        $pageRules = [];
        $tradeRules = [];
        if (ine($input, 'pages')) {
            foreach ((array)$input['pages'] as $key => $value) {
                // $pageRules["pages.$key.id"] = 'exists:template_pages,id';
                $pageRules["pages.$key.content"] = 'required';
                if(isset($value['tables'])){
					foreach ((array)$value['tables'] as $subkey => $subValue) {
						$pageRules["pages.$key.tables.$subkey.name"] = 'max:30';
						$pageRules["pages.$key.tables.$subkey.ref_id"] = 'required|max:50';
						$pageRules["pages.$key.tables.$subkey.head"] = 'required';
						$pageRules["pages.$key.tables.$subkey.body"] = 'required';
						$pageRules["pages.$key.tables.$subkey.foot"] = 'required';
					}
				}
            }
        } else {
            // $pageRules["pages.0.id"] = 'exists:template_pages,id';
            $pageRules["pages.0.content"] = 'required';
        }

        if (!ine($input, 'for_all_trades')
            && isset($input['trades'])
            && is_array($input['trades'])) {
            foreach ((array)$input['trades'] as $key => $trade) {
                $tradeRules['trades.' . $key] = 'required';
            }
        }

        return array_merge(static::$rules, $pageRules, $tradeRules);
        return array_merge(static::$rules, $pageRules);
    }

    protected function getFolderRules()
	{
		return [
			'type' 		=> 'required|in:estimate,proposal',
			'name'		=> 'required',
		];
	}

	protected function getDocumentMoveRules()
	{
		$rules = [
			'ids'		=> 'required',
			'parent_id' => 'integer',
			'type' 		=> 'required|in:estimate,proposal',
		];

		$inputs = Request::all();
		if(!isset($inputs['ids']) && !isset($inputs['group_ids'])) {
			return $rules;
		}

		if(!isset($inputs['ids']) && isset($inputs['group_ids'])) {
			unset($rules['ids']);
			$rules['group_ids'] = 'required';
		}

		return $rules;
	}


    public static function getChangeTypeRules()
    {
        return self::$changeTypeRules;
    }

    public static function getUploadImageRules()
    {
        $input = Request::all();
        if (ine($input, 'attachments')) {
            foreach ((array)$input['attachments'] as $key => $value) {
                $rules["attachments.$key.type"] = 'required|in:resource,estimate,company_cam';
                $rules["attachments.$key.value"] = 'required';
            }
        } else {
            $rules["image"] = 'required|image|mimes:jpeg,jpg,png';
        }

        return $rules;
    }

    protected function getCreateGoogleSheetRules()
    {
        $rules = [
            'insurance_estimate' => 'boolean',
            'type' => 'required|in:estimate,proposal',
            'title' => 'required',
            'for_all_trades' => 'boolean',
            'trades' => 'required_without:for_all_trades|required_if:for_all_trades,0|array|',
        ];

        $validFiles = implode(',', config('resources.excel_types'));

        $maxSize = config('jp.max_file_size');
        $rules['file'] = 'mime_types:' . $validFiles . '|max_mb:' . $maxSize.'|nullable';
        return $rules;
    }

    public function trades()
    {
        return $this->belongsToMany(Trade::class, 'template_trade', 'template_id', 'trade_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pages()
    {
        return $this->hasMany(TemplatePage::class, 'template_id');
    }

    public function firstPage()
    {
        return $this->hasOne(TemplatePage::class, 'template_id')
            ->orderBy('id', 'asc'); // order by id to get first page id vice
    }

    public function divisions()
    {
        return $this->belongsToMany(Division::class, 'template_division', 'template_id', 'division_id')
            ->withTimestamps();
    }

    public function deletedBy()
	{
		return $this->belongsTo(User::class, 'deleted_by', 'id')->withTrashed();
	}

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function scopeByTrades($query, $trades)
    {
        return $query->whereIn('templates.id', function ($query) use ($trades) {
            $query->select('template_id')->from('template_trade')->whereIn('trade_id', $trades);
        });
    }

    public function scopeSystem($query)
    {
        $query->where(function ($query) {
            $query->whereNull('templates.company_id')
                ->orWhere('templates.company_id', 0);
        });
    }

    public function scopeCustom($query, $companyId)
    {
        $query->where(function ($query) use ($companyId) {
            $query->where('templates.company_id', $companyId)
                ->orWhere(function ($query) {
                    $query->whereNull('templates.company_id')->where('templates.type', '=', 'blank');
                });
        });
    }

    public function scopeWithCustom($query, $companyId)
    {
        $query->where(function ($query) use ($companyId) {
            $query->whereNull('templates.company_id')
                ->orWhere('templates.company_id', 0)
                ->orWhere('templates.company_id', $companyId);
        });
    }

    public function scopeWithoutArchived($query)
    {
        $query->whereNull('templates.archived');
    }

    public function scopeOnlyArchived($query)
    {
        $query->whereNotNull('templates.archived');
    }

    public function scopeWorksheetTemplates($query)
    {
        $query->whereIn('id', function($query) {
            $query->select('template_id')
            ->from('worksheet_templates')
            ->where('worksheet_templates.company_id', getScopeId());
        });
    }

    /**
     * **
     * @method Auth Id save before delete
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($template) {
            $template->deleted_by = \Auth::user()->id;
            $template->save();
        });
    }

    public function scopeSystemEstimate($query)
    {
        $query->where(function ($query) {
            $query->whereType(Template::ESTIMATE)
                ->orWhere('type', Template::BLANK);
        });
    }

    /**
     * track uses of templates
     * @param  $templateId
     * @param  $type
     * @return [type]             [description]
     */
    protected function trackTemplateUses($templateIds, $type)
    {
        foreach ((array)$templateIds as $key => $templateId) {
            TemplateUse::create([
                'template_id' => $templateId,
                'type' => $type,
                'company_id' => getScopeId(),
            ]);
        }
    }

    public function scopeNameSearch($query, $name)
	{
		$this->searchable = [
			'columns' => [
				'templates.group_name'	=> 10,
				'templates.title' => 10,
			],
		];

		$query->search(implode(' ', array_slice(explode(' ', $name), 0, 10)), null, true);
	}

	public function pageTableCalculations()
	{
		return $this->hasMany(PageTableCalculation::class, 'type_id')->wherePageType(PageTableCalculation::TEMPLATE_PAGE_TYPE);
	}
}
