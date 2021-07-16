<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"
	style="font-family:'Roboto', sans-serif; font-size: 16px;">
	<tbody>
		<tr>
			<td align="center" style="background-color:#ffffff">
				<table border="0" cellpadding="0" cellspacing="0" width="600" style="min-width:600px;width:600px"
					role="presentation">
					<tbody>
						<tr>
							<td align="center" style="background-color:#ffffff">
								<table border="0" cellpadding="0" cellspacing="0" width="600"
									style="min-width:600px;width:600px;padding:0px 0px 0px 0px; background-color: #f7f8fc;">
									<tbody>
										<tr>
											<td>
												<table cellpadding="0" cellspacing="0" border="0" width="100%"
													role="presentation">
													<tbody>
														<tr>
															<td align="center">
																<table cellpadding="0" cellspacing="0" border="0"
																	width="100%" role="presentation">
																	<tbody>
																		<tr>
																			<td align="left"
																				style="background-color:#f7f8fc;padding: 30px 20px 20px;">
																				<img src="{{ config('app.url') }}user_invitation_images/email/logo.png" border="0" alt="logo">
																			</td>
																		</tr>
																	</tbody>
																</table>
															</td>
														</tr>
														<tr>
															<td style="border: 0; padding: 0 20px; font-size: 0;">
																<img src="{{ config('app.url') }}user_invitation_images/email/estimate-email.jpg" alt="banner" style="width: 100%;">
															</td>
														</tr>
													</tbody>
												</table>
												<table cellpadding="0" cellspacing="0" border="0" width="100%"
													role="presentation">
													<tbody>
														<tr>
															<td style="padding: 0 20px;">
																@if($customer && $job && $current_user)
																<?php $link = config('jp.site_job_url').$customer->id.'/job/'.$job->id.'/overview'; ?>
																<div
																	style="text-align: left; line-height: 25px; color: #3d3d3d; padding: 40px 30px; background-color: #fff; padding-top: 0;">
																	<div style="background-color: #fff; padding: 30px 0 20px;">
																		<p style="margin:0; margin-top: 5px; color:#3d3d3d; text-transform: capitalize; font-weight: 400; text-align: left; font-size: 16px;">Hello,</p>
																	</div>
																	<p style="margin: 0 0 15px; width: 95%; color:#3d3d3d;"
																		class="thankyou-note">You are requested to
																		review the attached <span style="color: #000;">ClickThru</span> Estimate for
																		<a href="{{$link}}" target="_blank" style="color:#418bca; text-decoration:none">{{$customer->full_name}} / {{$job->number}}</a>
																	</p>
																	<p style="margin-top: 25px; margin-bottom: 0; color:#3d3d3d;">
																		Thanks</p>
																	<p style="margin: 0; margin-top: 2px; color:#3d3d3d;">{{$current_user->full_name}}</p>
																</div>
																@endif
															</td>
														</tr>
														<tr style="background-color: #f7f8fc;">
															<td
																style="padding: 50px 30px; text-align: center; line-height: 25px; color: #3d3d3d;">
																<p style="margin: 0 auto; width: 70%;"
																	class="footer-note">The "Must Have" Business
																	Productivity Software for ALL Home Improvement
																	Contractors.</p>
																<div style="padding: 40px 30px 20px;"><a
																		href="https://www.facebook.com/jobprogressapp"
																		style="text-decoration: none; display: inline-block; margin: 0 3px;"><img src="{{ config('app.url') }}user_invitation_images/email/email-fb-icon.png" alt="facebook"></a><a
																			href="https://twitter.com/jobprogress"
																			style="text-decoration: none; display: inline-block; margin: 0 3px;"><img src="{{ config('app.url') }}user_invitation_images/email/email-tw-icon.png" alt="twitter"></a><a
																				href="https://www.instagram.com/job_progress/"
																				style="text-decoration: none; display: inline-block; margin: 0 3px;"><img src="{{ config('app.url') }}user_invitation_images/email/email-insta-icon.png" alt="instagram"></a><a
																					href="https://www.linkedin.com/company/jobprogress/"
																					style="text-decoration: none; display: inline-block; margin: 0 3px;"><img src="{{ config('app.url') }}user_invitation_images/email/email-in-icon.png" alt="linkedin"></a>
																</div>
																<p style="margin: 0 auto; font-size: 13px;">&copy; 2019
																	JOBPROGRESS,LLC. All Rights Reserved. <a
																		href="https://www.jobprogress.com/terms-of-use/"
																		style="text-decoration: none; color: #418bca;">Terms
																		of Use</a> | <a
																		href="https://www.jobprogress.com/privacy-policy/"
																		style="text-decoration: none; color: #418bca;">Privacy
																		Policy</a></p>
															</td>
														</tr>
													</tbody>
												</table>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
	</tbody>
</table>