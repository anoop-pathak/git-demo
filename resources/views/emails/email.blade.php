
<!DOCTYPE html>

<html lang="en-US">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<style type="text/css">
			.cust-job-id {
				width: 250px;
			    font-size: 12px;
			    /*float: right;*/
			    text-align: right;
			    display: table-cell;
			    vertical-align: middle;
			    /*margin-top: 20px;*/
			}
		</style>
	</head>
	<body>
		<div>
			<table cellspacing="0" style="color: rgb(0, 0, 0); width: 600px; margin: auto;">
				<tbody><tr>
					<td cellspacing="0" cellpadding="0" style="padding:0;">
						<div style="background:#f1f1f1;padding: 15px; position: relative; display:table; width: 100%; box-sizing: border-box;">

							<a href="{{$website_link ?? ''}}" style="color: inherit;display:inline-block; max-width: 320px; text-decoration: none; display: table-cell; vertical-align: middle;">
								@if(!empty ($company->logo) )
                            	<span style="    height: 60px; width: 75px; display: inline-block; margin-right: 10px; line-height: 45px; text-align: center; border: 1px solid #ddd; padding: 5px; box-sizing: border-box; border-radius: 5px;">
									<img src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo, false) }}" style="max-height: 100%; max-width: 100%; height: auto;vertical-align: middle;" />
								</span>
								@endif
								<h3 style="display: inline-block; width: 200px; margin: 0; vertical-align: middle; font-size: 18px;">{{ $company->name }}</h3>
							</a>

							<!-- show customer name and job number -->
							@if(($name = $email->present()->customerName))
								<?php $jobIdReplace = $email->present()->jobIdReplace;
									$fullTitle = $name;
									if ($jobIdReplace) {
										$fullTitle.= ' / '.$jobIdReplace;
									}
									$jobIdReplace = (strlen($jobIdReplace) > 30) ? substr($jobIdReplace,0,30).'...' : $jobIdReplace;
								?>
								<div class="cust-job-id">
									<span style="display: block;" title="{!!$fullTitle!!}">
										{{ $name }}
										@if($jobIdReplace)
										/ {!!$jobIdReplace!!}
										@endif
									</span>
									@if($address = $email->present()->jobAddress)
										<?php
											$job 	  = $email->jobs->first();
											$customer = $email->customer;
											$jobLink  =  config('jp.site_job_url').$customer->id.'/job/'.$job->id.'/overview';
										?>
										<span style="display: block; margin-top: 0; line-height: 1.2; font-size: 11px;">
											<a href="javascript:void(0)" style="color: inherit; text-decoration: none;">{!! $address !!}</a>
										</span>
									@endif
								</div>
							@endif
						</div>
					</td>
				</tr>
				<tr>
					<td cellspacing="0" cellpadding="0" style="color: rgb(0, 0, 0); background: #f9f9f9; padding: 20px 15px;">
						<p style="font-family: arial,sans-serif; margin: 0px; padding: 5px 2px; font-size: 15px; color: rgb(106, 106, 106);">
							{!! $content !!}
						</p>
					</td>
				</tr>
				<tr>
					<td cellspacing="0" cellpadding="0" style="background: #f1f1f1; font-family: arial,sans-serif; font-size: 13px; color: #666; border-bottom-left-radius: 5px; border-bottom-right-radius: 5px; padding: 20px 18px;">
						<div style="margin:5px 0;display:inline-block;">
							Copyright &copy; {{ date("Y") }} <a style="color:#666; text-decoration:none" href="{{Settings::get('WEBSITE_LINK')}}">{{ $company->name }}.</a>
						</div>
					</td>
				</tr>
			</tbody></table>
		</div>
	</body>
</html>

