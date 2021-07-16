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
																				<img src="{{ config('app.url') }}monthly_email_images/logo.png" border="0" alt="logo">
																			</td>
																		</tr>
																	</tbody>
																</table>
															</td>
														</tr>
														<tr>
															<td style="border: 0; padding: 0 20px; font-size: 0;">
																<img src="{{ config('app.url') }}monthly_email_images/banner.png" alt="banner" style="width: 100%;">
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
																				<div style="background-color: #fff; padding: 30px 20px;"><h1 style="margin:0; color:#3d3d3d; font-weight: 500; text-align: center;"><?php echo $data['companyName']; ?> Monthly Summary</h1></div>
																			</td>
																		</tr>
																	</tbody>
																</table>
															</td>
														</tr>
														<tr>
															<td style="padding: 0 20px;">
																<table style="width: 100%; background: #fff;" cellpadding="0" cellspacing="0" border="0" width="100%" role="presentation">
																	<tr>
																		<td style="padding: 0 20px;" align="center">
																			<p style="padding-bottom: 5px; color: #000; margin: 0 0 25px; font-size: 17px; text-decoration: underline; line-height: normal;">
																			Here's a summary of what happened last month</p>
																		</td>
																	</tr>
																</table>
																<table style="width: 100%; background: #fff;" cellpadding="0" cellspacing="0" border="0" width="100%" role="presentation">
																	<td align="center">
																		<div class="summary-col" style="padding-bottom: 30px;"><div style="background-color: #f5994e; border-radius: 100%; height: 100px; width: 100px; margin: auto;"><img src="{{ config('app.url') }}monthly_email_images/users.png" alt="new leads" style="display: inline-block; margin-top: 15px;"></div><h3 style="line-height: normal;color: #418bca; font-size: 30px; font-weight: 500; margin-top: 20px; margin-bottom: 0;"><?php echo $data['newJobs']; ?></h3><p style="display: block; color: #3d3d3d; font-size: 16px; margin: 10px 0 0;line-height: normal;">New Leads</p></div>
																	</td>
																	<td align="center">
																		<div class="summary-col" style="padding-bottom: 30px;"><div style="background-color: #f27364; border-radius: 100%; height: 100px; width: 100px; margin: auto;"><img src="{{ config('app.url') }}monthly_email_images/notebook.png" alt="propoals sent" style="display: inline-block; margin-top: 15px;"></div><h3 style="line-height: normal;color: #418bca; font-size: 30px; font-weight: 500; margin-top: 20px; margin-bottom: 0;"><?php echo $data['proposals'];?></h3><p style="display: block; color: #3d3d3d; font-size: 16px; margin: 10px 0 0;line-height: normal;">Proposals Sent</p></div>
																	</td>
																	<td align="center">
																		<div class="summary-col" style="padding-bottom: 30px;"><div style="background-color: #009cad; border-radius: 100%; height: 100px; width: 100px; margin: auto;"><img src="{{ config('app.url') }}monthly_email_images/handshake.png" alt="jobs awarded" style="display: inline-block; margin-top: 20px;"></div><h3 style="line-height: normal;color: #418bca; font-size: 30px; font-weight: 500; margin-top: 20px; margin-bottom: 0;"><?php echo $data['jobsAwarded']; ?></h3><p style="display: block; color: #3d3d3d; font-size: 16px; margin: 10px 0 0;line-height: normal;">Jobs Awarded</p></div>
																	</td>
																	<td align="center">
																		<div class="summary-col" style="padding-bottom: 30px;"><div style="background-color: #0084ce; border-radius: 100%; height: 100px; width: 100px; margin: auto;"><img src="{{ config('app.url') }}monthly_email_images/dollar.png" alt="earned" style="display: inline-block; margin-top: 18px;"></div><h3 style="line-height: normal;color: #418bca; font-size: 30px; font-weight: 500; margin-top: 20px; margin-bottom: 0;">$<?php echo $data['received_payment'] ?></h3><p style="display: block; color: #3d3d3d; font-size: 16px; margin: 10px 0 0;line-height: normal;">Earned</p></div>
																	</td>
																</table>
															</td>
														</tr>
													</tbody>
												</table>
												<table cellpadding="0" cellspacing="0" border="0" width="100%" role="presentation">
													<tr>
														<td>
															<table style="width: 100%;" cellpadding="0" cellspacing="0" border="0" width="100%" role="presentation">
																<tr>
																	<td style="padding: 0 20px;" align="center">
																		<div style="background: #fff;">
																			<p style="margin: 0; text-align: center; color: #000; padding: 10px 0 25px; font-size: 17px; text-decoration: underline; line-height: normal;">What's Happening</p>
																		</div>
																	</td>
																</tr>
															</table>
														</td>
													</tr>
													<tr>
														<td style="padding: 0 20px;">
															<table style="background-color: #fff; width: 100%;" cellpadding="0" cellspacing="0" border="0" width="100%" role="presentation">
																<tr>
																	<td style="padding: 0 20px;">
																		<table style="width: 100%; background: #fff;" cellpadding="0" cellspacing="0" border="0" width="100%" role="presentation">
																			<tr>
																				<td style="padding: 20px 30px; background-color: #f7f8fc;">
																					<div class="report-left-col" style="float: left; width: 65%; padding-right: 30px;">
																						<div style="border-right: 1px solid #e8e9ee; float: left; width: 100%;">
																							<div style="float: left; width: 100%; margin-bottom: 20px;"><div style="float: left; padding-right: 30px; width: 46%;" class="report-detail"><div style="border-right: 1px solid #e8e9ee;"><span style="display: block; color: #3d3d3d; font-size: 16px;">Tasks</span><h3 style="color: #418bca; font-weight: 500; margin: 3px 0 10px; font-size: 32px; line-height: normal;"><?php echo $data['tasks']; ?></h3></div></div><div style="float: left; width: 46%;" class="report-detail"><span style="display: block; color: #3d3d3d; font-size: 16px; line-height: normal;">Messages</span><h3 style="color: #418bca; font-weight: 500; margin: 3px 0 10px; font-size: 32px; line-height: normal;"><?php echo $data['messages']; ?></h3></div></div>
																							<div style="float: left; width: 100%;"><div style="float: left; padding-right: 30px; width: 46%;" class="report-detail"><div style="border-right: 1px solid #e8e9ee;"><span style="display: block; color: #3d3d3d; font-size: 16px; line-height: normal;">Emails</span><h3 style="color: #418bca; font-weight: 500; margin: 3px 0 0; font-size: 32px; line-height: normal;"><?php echo $data['emails']; ?></h3></div></div><div style="float: left; width: 46%;" class="report-detail"><span style="display: block; color: #3d3d3d; font-size: 16px; line-height: normal;">Appointments</span><h3 style="color: #418bca; font-weight: 500; margin: 3px 0 0; font-size: 32px;"><?php echo $data['appointments']; ?></h3></div></div>
																						</div>
																					</div>
																					<div class="report-right-col" style="float: left; width: 30%;"><span style="display: block; color: #3d3d3d; margin-bottom: 15px; font-size:16px; line-height: normal;">New Users</span><div style="clear: both; margin-bottom: 4px; line-height: normal;"><span style="float: left; color: #418bca; font-size: 28px;"><?php echo $data['admins']; ?></span><span style="display: block; color: #111; margin-left: 40px; padding-top: 8px; line-height: normal; font-size: 16px;">Admin</span></div><div style="clear: both; margin-bottom: 4px;"><span style="float: left; color: #418bca; font-size: 28px; line-height: normal;"><?php echo $data['standard'] ?></span><span style="display: block; color: #111; margin-left: 40px; padding-top: 8px; line-height: normal; font-size: 16px;">Standard</span></div><div style="clear: both;"><span style="float: left; color: #418bca; font-size: 28px; line-height: normal;"><?php echo $data['subContractor'];?></span><span style="display: block; color: #111; margin-left: 40px; padding-top: 8px; line-height: normal; font-size: 16px;">Sub Contractors</span></div></div>
																				</td>
																			</tr>
																		</table>
																	</td>
																</tr>
															</table>
														</td>
													</tr>
												</table>
												<table cellpadding="0" cellspacing="0" border="0" width="100%" role="presentation">
													<tbody>
														<tr>
															<td style="padding: 0 20px;">
																<div style="text-align: center; line-height: 25px; color: #3d3d3d; padding: 40px 30px; background-color: #fff;">
																	<p style="margin: 0 auto; width: 80%;" class="thankyou-note">We are constantly listening to your feedback and enriching JobProgress with new features and tools. Want to leave us a review? <a href="https://www.softwareadvice.com/construction/jobprogress-profile" style="color: #3d3d3d; text-decoration: none; color: #418bca;">Click Here.</a></p>
																	<p style="margin: 0;">We wish you have a stronger upcoming month.</p>
																	<p style="margin-top: 20px; margin-bottom: 0;">Sincerely,</p>
																	<p style="margin: 0;">The JobProgress Team</p>
																</div>
															</td>
														</tr>
														<tr style="background-color: #f7f8fc;"><td style="padding: 50px 30px; text-align: center; line-height: 25px; color: #3d3d3d;">
															<p style="margin: 0 auto; width: 50%;" class="footer-note">The "Must Have" Business Productivity Software for ALL Home Improvement Contractors.</p><div style="padding: 40px 30px 20px;"><a href="https://www.facebook.com/jobprogressapp" style="text-decoration: none; display: inline-block; margin: 0 3px;"><img src="{{ config('app.url') }}monthly_email_images/email-fb-icon.png" alt="facebook"></a><a href="https://twitter.com/jobprogress" style="text-decoration: none; display: inline-block; margin: 0 3px;"><img src="{{ config('app.url') }}monthly_email_images/email-tw-icon.png" alt="twitter"></a><a href="https://www.instagram.com/job_progress/" style="text-decoration: none; display: inline-block; margin: 0 3px;"><img src="{{ config('app.url') }}monthly_email_images/email-insta-icon.png" alt="instagram"></a><a href="https://www.linkedin.com/company/jobprogress/" style="text-decoration: none; display: inline-block; margin: 0 3px;"><img src="{{ config('app.url') }}monthly_email_images/email-in-icon.png" alt="linkedin"></a></div><p style="margin: 0 auto; font-size: 13px;">&copy; {{ date('Y') }} JOBPROGRESS,LLC. All Rights Reserved. <a href="https://www.jobprogress.com/terms-of-use/" style="text-decoration: none; color: #418bca;">Terms of Use</a> | <a href="https://www.jobprogress.com/privacy-policy/" style="text-decoration: none; color: #418bca;">Privacy Policy</a></p>
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
