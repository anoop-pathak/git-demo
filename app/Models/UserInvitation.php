<?php

namespace App\Models;

use Carbon\Carbon;

class UserInvitation extends BaseModel
{
	protected $fillable = [
		'user_id', 'email', 'company_id', 'status', 'group_id', 'token'
	];

	const DRAFT		= 'draft';
	const ACCEPTED 	= 'accepted';
	const REJECTED 	= 'rejected';

	/********** Relations **********/

	public function user()
	{
		return $this->belongsTo(User::class, 'email', 'email');
	}

	public function company()
	{
		return $this->belongsTo(Company::class);
	}

	/********** Relations End **********/

	/********** Scopes **********/

	public function scopeExcludeExpired($query)
	{
		$expireDate = Carbon::now()->subDays(config('jp.user_invitation_token_expire_limit'))->toDateString();

		$query->where('user_invitations.created_at', '>', $expireDate);
	}
}