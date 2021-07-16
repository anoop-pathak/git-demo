<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" style="font-family:'Roboto', sans-serif; font-size: 16px;">
	<tbody>
		<tr>
			<td align="center" style="background-color:#ffffff">
				<table border="0" cellpadding="0" cellspacing="0" width="800" style="min-width:800px;width:800px" role="presentation">
					<tbody>
						<tr>
							<td align="center" style="background-color:#ffffff">
								<table border="0" cellpadding="0" cellspacing="0" width="800" style="min-width:800px;width:800px;padding:0px 0px 0px 0px; background-color: #f7f8fc;">
									<tbody>
										<tr>
											<td>
												<table cellpadding="0" cellspacing="0" border="0" width="100%" role="presentation">
													<tbody>
														<tr>
															<td align="center">
																<table cellpadding="0" cellspacing="0" border="0" width="100%" role="presentation">
																	<tbody>
																		<tr>
																			<td align="left" style="background-color:#f7f8fc;padding: 30px 20px 20px;">
																				<img src="{{ config('app.url') }}user_invitation_images/email/logo.png" border="0" alt="logo">
																			</td>
																		</tr>
																	</tbody>
																</table>
															</td>
														</tr>
														<tr>
															<td style="border: 0; padding: 0 20px; font-size: 0;">
																<img src="{{ config('app.url') }}user_invitation_images/email/email.jpg" alt="banner" style="width: 100%;">
															</td>
														</tr>
													</tbody>
												</table>
												<table style="width: 100%;" cellpadding="0" cellspacing="0" border="0" width="100%" role="presentation">
													<tbody>
														<tr>
															<td>
																<table style="width: 100%;" cellpadding="0" cellspacing="0" border="0" width="100%" role="presentation">
																	<tbody>
																		<tr>
																			<td style="padding:0 20px;">
																				<div style="background-color: #fff; padding: 30px 20px;"><h1 style="margin:0; color:#3d3d3d; font-weight: 500; text-align: center;">Welcome to {{ $company->name }}</h1></div>
																			</td>
																		</tr>
																	</tbody>
																</table>
															</td>
														</tr>
													</tbody>
												</table>
												<table cellpadding="0" cellspacing="0" border="0" width="100%" role="presentation">
													<tbody>
														<tr>
															<td style="padding: 0 20px;">
																<div style="text-align: center; line-height: 25px; color: #3d3d3d; padding: 40px 30px; background-color: #fff; padding-top: 0;">
																	<p style="margin: 0 auto; width: 80%;" class="thankyou-note">You have been invited by <b>{{ $company->name }}</b>. Click below to accept the invite.</p>
																	<a href="{{ $acceptance_url }}" style="background-color: #428bca; border: 1px solid #428bca; text-transform: uppercase; font-size: 14px; color: #fff; font-weight: 400;  display: inline-block; text-decoration: none; padding: 8px 50px; letter-spacing: 1px; border-radius: 3px; margin-top: 30px;">Accept Invite</a>
																	<p style="margin-top: 20px; margin-bottom: 0;">This link will expire in 30 days.</p>
																	<p style="margin-top: 20px; margin-bottom: 0;">Sincerely,</p>
																	<p style="margin: 0;">The JobProgress Team</p>
																</div>
															</td>
														</tr>
														<tr style="background-color: #f7f8fc;"><td style="padding: 50px 30px; text-align: center; line-height: 25px; color: #3d3d3d;">
															<p style="margin: 0 auto; width: 50%;" class="footer-note">The "Must Have" Business Productivity Software for ALL Home Improvement Contractors.</p><div style="padding: 40px 30px 20px;"><a href="https://www.facebook.com/jobprogressapp" style="text-decoration: none; display: inline-block; margin: 0 3px;"><img src="{{ config('app.url') }}user_invitation_images/email/email-fb-icon.png" alt="facebook"></a><a href="https://twitter.com/jobprogress" style="text-decoration: none; display: inline-block; margin: 0 3px;"><img src="{{ config('app.url') }}user_invitation_images/email/email-tw-icon.png" alt="twitter"></a><a href="https://www.instagram.com/job_progress/" style="text-decoration: none; display: inline-block; margin: 0 3px;"><img src="{{ config('app.url') }}user_invitation_images/email/email-insta-icon.png" alt="instagram"></a><a href="https://www.linkedin.com/company/jobprogress/" style="text-decoration: none; display: inline-block; margin: 0 3px;"><img src="{{ config('app.url') }}user_invitation_images/email/email-in-icon.png" alt="linkedin"></a></div><p style="margin: 0 auto; font-size: 13px;">&copy; {{ date("Y") }} JOBPROGRESS,LLC. All Rights Reserved. <a href="https://www.jobprogress.com/terms-of-use/" style="text-decoration: none; color: #418bca;">Terms of Use</a> | <a href="https://www.jobprogress.com/privacy-policy/" style="text-decoration: none; color: #418bca;">Privacy Policy</a></p>
														</td></tr>
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