<?php
namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\Tag;
use Illuminate\Support\Facades\Auth;

Class TagRepository extends ScopedRepository
{
	protected $model;
	protected $scope;

    function __construct(Tag $model, Context $scope)
	{
		$this->model = $model;
		$this->scope = $scope;
	}

    /**
	* get tags list.
	* @return $tag
	*/
	public function getTags($filters = array())
	{
		$with = $this->getIncludesData($filters);
		$tags = $this->make($with);
		$this->applyFilters($tags, $filters);

        return $tags;
	}

    /**
	* saveTags
	* @return Response
	*/
	public function saveTag($name, $type, $input)
	{
		$tag = Tag::create([
			'name' 		=> 	$name,
			'company_id' => getScopeId(),
			'created_by' => Auth::id(),
			'type' => $type,
		]);

		if(($type == Tag::TYPE_USER) && ine($input,'user_ids')) {
			$tag->users()->attach($input['user_ids'], ['company_id' => $this->scope->id()]);
		}

		if(($type == Tag::TYPE_CONTACT) && ine($input,'contact_ids')) {
			$tag->contacts()->sync($input['contact_ids']);
		}

        return $tag;
	}

	/**
	 * Update Tag
	 * @param id
	 * @return $response
	 */
	public function updateTag(Tag $tag, $name, $input)
	{
		$tag->name = $input['name'];
		$tag->save();

		if(($tag->type == Tag::TYPE_USER) && ine($input,'user_ids')) {
			$tag->users()->detach();
			$tag->users()->attach($input['user_ids'], ['company_id' => $this->scope->id()]);
		}

		if(($tag->type == Tag::TYPE_CONTACT) && ine($input,'contact_ids')) {
			$tag->contacts()->sync($input['contact_ids']);
		}

		return $tag;
	}

	public function assignUsers(Tag $tag, $userIds)
	{
		$userIds = arry_fu((array)$userIds);
		if(empty($userIds)) return $tag;
		$pivotData = array_fill(0, count($userIds), ['company_id' => $this->scope->id()]);
		$syncData  = array_combine($userIds, $pivotData);
		$tag->users()->sync($syncData);

		return $tag;
	}

    /********** Private Functions **********/
	private function applyFilters($query, $filters = array())
	{
		if(ine($filters,'name')) {
			$query->name($filters['name']);
		}

        if (ine($filters, 'user_ids')) {
			$query->users($filters['user_ids']);
		}

		if (ine($filters, 'has_users')) {
			$query->whereIn('tags.id', function($query) {
				$query->select('user_tag.tag_id')
					->from('user_tag')
					->where('user_tag.company_id', getScopeId())
					->join('users', 'user_tag.user_id', '=', 'users.id')
					->whereNull('users.deleted_at');
			});
		}

		$type = Tag::TYPE_USER;
		if(ine($filters, 'type')) {
			$type = $filters['type'];
		}

		$query->where('type', $type);
	}

    private function getIncludesData($filters)
	{
		$with = [];

        if(!ine($filters, 'includes')) return $with;
        $includes = (array)$filters['includes'];

        if(in_array('users', $includes)) {
			$users = ['users' => function($query) use($filters){
				$includeInactiveUsers = ine($filters, 'include_inactive_users');
				if(!$includeInactiveUsers)  {
					$query->where('active', true);
				}
			}];
			$with = array_merge($with, $users);
		}

		if(in_array('counts', $includes)) {
			if(empty($with)) {
				$users = ['users' => function($query) use($filters){
					$includeInactiveUsers = ine($filters, 'include_inactive_users');
					if(!$includeInactiveUsers)  {
						$query->where('active', true);
					}
				}];
				$with = array_merge($with, $users);
			}
			$with[] = 'contacts';

			$users = ['subContractorUsers' => function($query) use($filters){
					$includeInactiveUsers = ine($filters, 'include_inactive_users');
					if(!$includeInactiveUsers)  {
						$query->where('active', true);
					}
				}];
			$with = array_merge($with, $users);
		}

		if(in_array('contacts', $includes)) {
			$with[] = 'contacts';
		}

        return $with;
	}
}