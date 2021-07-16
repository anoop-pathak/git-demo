<?php
namespace App\Models;

use Nicolaslopezj\Searchable\SearchableTrait;

class Tag extends BaseModel
{
    use SearchableTrait;

	protected $fillable = ['name', 'company_id', 'created_by', 'type'];

    protected $table = 'tags';

	const TYPE_USER = 'user';
    const TYPE_CONTACT = 'contact';

    protected function getRules($id = null)
	{
		$rules['name'] = "required|unique:tags,name,{$id},id,company_id,".getScopeId().',type,'.\Request::get('type');

		$rules['type'] = "required|in:user,contact";

		return $rules;
	}

	protected function getUpdateRules($id, $type)
	{
		$rules['name'] = "required|unique:tags,name,{$id},id,company_id,".getScopeId().',type,'.$type;

		return $rules;
	}

    // set name scope for query filtering.
	public function scopeName($query, $name)
	{
		$this->searchable = [
			'columns' => [
				'tags.name' => 10,
			],
		];
		$query->search($name, null, true);
	}

    // set users scope for query filtering.
	public function scopeUsers($query, $userIds)
	{
		$query->whereIn('tags.id',function($query) use($userIds){
			$query->select('tag_id')
				->from('user_tag')
				->where('company_id', getScopeId())
				->whereIn('user_id', (array)$userIds);
			});
	}

    public function users()
	{
		return $this->belongsToMany(User::class, 'user_tag', 'tag_id', 'user_id')->withTimestamps();
	}

	public function subContractorUsers()
	{
		return $this->belongsToMany(User::class, 'user_tag', 'tag_id', 'user_id')
			->where('users.group_id', User::GROUP_SUB_CONTRACTOR_PRIME)
			->withTimestamps();
	}

	public function contacts()
	{
		return $this->belongsToMany(Contact::class, 'contact_tag', 'tag_id', 'contact_id')->withTimestamps();
	}
}