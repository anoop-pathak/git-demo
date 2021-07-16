<!DOCTYPE html>
<html lang="en-US">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>
	<div>
		<table cellspacing="0" style="color: rgb(0, 0, 0); width: 600px; margin: auto;">
			<tbody>
				<tr>
					<td cellspacing="0" cellpadding="0" style="padding:0;"><div style="max-height:200px;width:100%;background:#f1f1f1;border-top-left-radius: 5px; border-top-right-radius: 5px;">
						<img src="https://www.jobprogress.com/wp-content/themes/jobprogress/images/main-logo-grey.png" style="padding:15px;border-top-left-radius: 5px; border-top-right-radius: 5px;" height="60" alt="JOBPROGRESS" />
					</td>
				</tr>
				<tr>
					<td cellspacing="0" cellpadding="0" style="color: rgb(0, 0, 0); background: #f9f9f9; padding: 20px 15px;">
						<p style="font-family: arial,sans-serif; margin: 0px; padding: 5px 2px;  font-size: 15px;font-weight:bold; color: rgb(106, 106, 106);">Dear {{ $user->full_name}},</p>
						<p style="font-family: arial,sans-serif; margin: 0px; padding: 5px 2px; font-size: 15px; color: rgb(106, 106, 106);">
							You have been assigned a new task.
							<br><br>
							<span style="font-size: 13px;  display: inline-block; font-weight: bold;text-transform:uppercase;width: 100%;text-align: center;margin-bottom:15px;">
								<span>Task Details</span> 
							</span><br>

							<span style="font-size: 13px; margin-bottom: 8px; display: inline-block;"><span style="font-weight: bold; width: 100px; float: left;">Title:</span><span style="display: block;margin-left: 100px;">{{ $task->title }}</span></span><br>
							@if(($job =$task->job) && ($customer = $job->customer))
								<span style="font-size: 13px; margin-bottom: 8px; display: inline-block;"><span style="font-weight: bold; width: 100px; float: left;">Linked Job:</span><span style="display: block;margin-left: 100px;">{{ $customer->full_name }} / {{ $job->number }}</span></span><br>
							@endif
							<span style="font-size: 13px; margin-bottom: 8px; display: inline-block;"><span style="font-weight: bold; width: 100px; float: left;">Created By:</span><span style="display: block;margin-left: 100px;">{{ $task->createdBy->full_name}}</span></span><br>
							<span style="font-size: 13px; margin-bottom: 8px; display: inline-block;"><span style="font-weight: bold; width: 100px;  float: left;">Participants:</span><span style="display: block;margin-left: 100px;">{{ $participants }}</span></span><br>
							@if($task->notes)
							<span style="font-size: 13px; margin-bottom: 8px; display: inline-block;"><span style="font-weight: bold; width: 100px; float: left;">Task Notes: </span> <span style="display: block;margin-left: 100px;">{{ $task->notes }}</span></span><br>
							@endif
							@if($task->due_date)
							<span style="font-size: 13px; margin-bottom: 8px; display: inline-block;"><span style="font-weight: bold; width: 100px; float: left;">Due Date: </span> <span style="display: block;margin-left: 100px;">
								{{ Carbon\Carbon::parse($task->due_date)->format('m/d/Y') }}</span>
							</span><br>
							@endif
							<br>
							<span style="display: inline-block; margin-bottom: 5px;">Thanks</span> <br>
							<span>JobProgress Support</span>
						</p>
					</td>
				</tr>
				<tr>
					<td cellspacing="0" cellpadding="0" style="background: #f1f1f1; font-family: arial,sans-serif; font-size: 13px; color: #666; border-bottom-left-radius: 5px; border-bottom-right-radius: 5px; padding: 20px 18px;">
						<div style="margin:5px 0;display:inline-block;">
							Copyright &copy; 2018 <a style="color:#666; text-decoration:none" href="http://jobprogress.com">JOBPROGRESS.COM.</a>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</body>
</html>