<?php

return [
	'sandbox_base_url' 		=> env('HOVER_SANDBOX_BASE_URL'),
	'sandbox_base_url_v2' 	=> env('HOVER_SANDBOX_BASE_URL_V2'),
	'base_url' 				=> env('HOVER_BASE_URL'),
	'client_id' 			=> env('HOVER_CLIENT_ID'),
	'client_secret' 		=> env('HOVER_CLIENT_SECRET'),
	'redirect_url' 			=> env('HOVER_REDIRECT_URL'),
	'webhook_url' 			=> env('HOVER_WEBHOOK_URL'),
	'webhook_verification_event' => 'webhook-verification-code',
	'webhook_job_state_change_event' => 'job-state-changed',
	'webhook_deliverable-change-request-state-changed' => 'deliverable-change-request-state-changed',
];