<?php
namespace App\Repositories;

use App\Models\UserInvitation;
use App\Services\Contexts\Context;

class UserInvitationsRepository extends ScopedRepository
{
	protected $model;
	protected $scope;

	public function __construct(UserInvitation $model, Context $scope)
	{
		$this->model = $model;
		$this->scope = $scope;
	}

	public function save($user, $groupId)
	{
		$data = [
			'user_id'		=> $user->id,
			'email'			=> $user->email,
			'company_id'	=> $this->scope->id(),
			'status'		=> UserInvitation::DRAFT,
			'group_id'		=> $groupId,
			'token'			=> generateUniqueToken(),
		];

		$invitation = $this->model->create($data);

		return $invitation;
	}

	public function update($invitation, $data)
	{
		$invitation->update($data);

		return $invitation;
	}
}