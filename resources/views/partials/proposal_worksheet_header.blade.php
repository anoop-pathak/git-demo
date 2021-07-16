<?php
$company = $job->company;

foreach($header as $key => $value) {
		if(!(bool)$value) continue;

		if ($job->isProject()) {
			$label = 'Project Id';
		} else {
			$label = 'Job Id';
		}

		switch ($key) {
			case 'job_id':
				echo '<div class="grid-col">
					<label>'.$label.'</label>
					<span>'.$job->number.'</span>
				</div>';
				break;
			case 'job_name':
				if(!$job->name) break;
				echo '<div class="grid-col" style="white-space: nowrap;">
					<label>Job Name</label>
					<span>'.$job->name.'</span>
				</div>';
				break;
			case 'job_number':
				if(!$job->alt_id) break;

				echo '<div class="grid-col" style="white-space: nowrap;"><label>Job #</label><span> '.$job->full_alt_id.'</span></div>';
				break;
			case 'proposal_number':
				echo '<div class="grid-col" style="white-space: nowrap;"><label>Proposal #</label><span> '.$proposal->serial_number.'</span></div>';
				break;
			case 'proposal_date':
				$createdDate = new Carbon\Carbon($proposal->created_at, \Settings::get('TIME_ZONE'));
				echo '<div class="grid-col"><label>Proposal Date </label><span> '.$createdDate->format(config('jp.date_format')).'</span></div>';
				break;
			case 'job_contact':
				if(!$job->jobContact) break;
				$jobContact = $job->jobContact;

				echo '<div class="grid-col"><label>Job Contact Name</label><span> ';
				echo $jobContact->full_name.'</span></div>';

				if(!empty($phones = array_filter($jobContact->additional_phones))) {
					$phone = reset($phones);
					echo '<div class="grid-col"><label>Job Contact Phone</label><span> ';
					echo $phone->number.'</span></div>';
				}

				if($jobContact->email) {
					echo '<div class="grid-col"><label>Job Contact Email</label><span> ';
					echo $jobContact->email.'</span></div>';
				}

				break;
			case 'insurance':
				if(!$job->insurance ) break;

				if(isset($job->insuranceDetails->insurance_company)) {
					echo '<div class="grid-col"><label>Insurance company</label><span> '.$job->insuranceDetails->insurance_company.'</span></div>';
				}
				if(isset($job->insuranceDetails->insurance_number)) {
					echo '<div class="grid-col"><label>Insurance #</label><span> '.$job->insuranceDetails->insurance_number.'</span></div>';
				}

				break;

			case 'contractor_license_number':
				if(!$company ) break;

				if($company->present()->licenseNumber) {
					echo '<div class="grid-col"><label>Contractor license #</label><span> '.$company->present()->licenseNumber.'</span></div>';
				}

				break;
		}
	}
?>

