<?php
namespace App\Models;

class UserFavouriteEntity extends BaseModel
{
	const TYPE_PROPOSAL = 'proposal';
	const TYPE_ESTIMATE = 'estimate';
	const TYPE_MATERIAL_LIST = 'material_list';
	const TYPE_WORK_ORDER = 'work_order';
	const TYPE_XACTIMATE_ESTIMATE = 'xactimate_estimate';

	protected $table = 'user_favourite_entities';

	protected $fillable = [
		'name', 'type', 'entity_id', 'marked_by', 'company_id', 'for_all_trades'
	];

	protected $rules = [
		'name'				=> 'required',
		'entity_id'			=> 'required',
		'trade_ids'			=> 'required_without:for_all_trades',
		'for_all_trades'	=> 'required_without:trade_ids',
	];

	protected function getRules()
	{
		$types = config('jp.favourite_entity_types');
		$rules['type'] = 'required|in:'.implode(',', $types);

		return array_merge($this->rules, $rules);
	}

	protected function getRemoveRules()
	{
		$types = config('jp.favourite_entity_types');
		$rules = [
			'entity_id'	=> 'required',
			'type'		=> 'required|in:'.implode(',', $types),
		];

		return $rules;
	}

	public function trades()
	{
		return $this->belongsToMany(Trade::class, 'user_favourite_entity_trades', 'user_favourite_entity_id', 'trade_id')
			->withTimestamps();
	}

	public function proposal()
	{
		return $this->belongsTo(Proposal::class, 'entity_id', 'id')
			->join('user_favourite_entities', function($join) {
				$join->on('proposals.id', '=', 'user_favourite_entities.entity_id')
					->where('proposals.company_id', '=', getScopeId());
			})->where('user_favourite_entities.type', self::TYPE_PROPOSAL)
			->select('proposals.*');
	}

	public function estimate()
	{
		return $this->belongsTo(Estimation::class, 'entity_id', 'id')
			->join('user_favourite_entities', function($join) {
				$join->on('estimations.id', '=', 'user_favourite_entities.entity_id')
					->where('estimations.company_id', '=', getScopeId());
			})->whereIn('user_favourite_entities.type', [self::TYPE_ESTIMATE, self::TYPE_XACTIMATE_ESTIMATE])
			->select('estimations.*');
	}

	public function materialList()
	{
		return $this->belongsTo(MaterialList::class, 'entity_id', 'id')
			->join('user_favourite_entities', function($join) {
				$join->on('material_lists.id', '=', 'user_favourite_entities.entity_id')
					->where('material_lists.company_id', '=', getScopeId());
			})->where('user_favourite_entities.type', self::TYPE_MATERIAL_LIST)
			->select('material_lists.*');
	}

	public function workOrder()
	{
		return $this->belongsTo(MaterialList::class, 'entity_id', 'id')
			->join('user_favourite_entities', function($join) {
				$join->on('material_lists.id', '=', 'user_favourite_entities.entity_id')
					->where('material_lists.company_id', '=', getScopeId());
			})->where('user_favourite_entities.type', self::TYPE_WORK_ORDER)
			->select('material_lists.*');
	}

	/********** Scopes Start **********/

	public function scopeTrades($query, $trades)
	{
		$query->where(function($query) use($trades) {
			$query->whereIn('user_favourite_entities.id', function($query) use($trades){
				$query->select('user_favourite_entity_id')
					->from('user_favourite_entity_trades')
					->where('company_id', getScopeId())
					->whereIn('trade_id', (array)$trades);
			})->orWhere('user_favourite_entities.for_all_trades', true);
		});
	}

	public function scopeWorksheets($query, $multiTier = null)
	{
		$query->join('worksheets', function($query) {
			$query->on('worksheets.id', '=', 'user_favourite_entities.worksheet_id');
		});

		if(!is_null($multiTier))	 {
			$query->where('worksheets.multi_tier', (bool)$multiTier);
		}
	}

	public function scopeExcludeDeleted($query, $filters)
	{

		$types = (array)issetRetrun($filters, 'type') ?: [];

		$query->where(function($query) use($types){

			if(in_array(self::TYPE_ESTIMATE, $types) || in_array(self::TYPE_XACTIMATE_ESTIMATE, $types)) {
				$query->where(function($query) {
					$query->whereIn('user_favourite_entities.type', [self::TYPE_ESTIMATE, self::TYPE_XACTIMATE_ESTIMATE]);
					$query->whereNotIn('entity_id', function($query) {
						$query->select('id')
							->from('estimations')
							->where('company_id', getScopeId())
							->whereNotNull('deleted_at');
					});
				});
			}

			if(in_array(self::TYPE_WORK_ORDER, $types)) {
				$query->orWhere(function($query) {
					$query->where('user_favourite_entities.type', self::TYPE_WORK_ORDER);
					$query->whereNotIn('entity_id', function($query) {
						$query->select('id')
							->from('material_lists')
							->where('company_id', getScopeId())
							->where('type', self::TYPE_WORK_ORDER)
							->whereNotNull('deleted_at');
					});
				});
			}

			if(in_array(self::TYPE_MATERIAL_LIST, $types)) {
				$query->orWhere(function($query) {
					$query->where('user_favourite_entities.type', self::TYPE_MATERIAL_LIST);
					$query->whereNotIn('entity_id', function($query) {
						$query->select('id')
							->from('material_lists')
							->where('company_id', getScopeId())
							->where('type', self::TYPE_MATERIAL_LIST)
							->whereNotNull('deleted_at');
					});
				});
			}

			if(in_array(self::TYPE_PROPOSAL, $types)) {
				$query->orWhere(function($query) {
					$query->where('user_favourite_entities.type', self::TYPE_PROPOSAL);
					$query->whereNotIn('entity_id', function($query) {
						$query->select('id')
							->from('proposals')
							->where('company_id', getScopeId())
							->whereNotNull('deleted_at');
					});
				});
			}
		});
	}
	/********** Scopes End **********/
}