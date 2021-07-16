<div style="margin: auto; border-radius: 5px; padding: 30px 0px;">
	<table cellspacing="0" style="color: #272727; background-color: #F7F8FC; width: 600px; margin: auto; background-image: url({{ config('app.url').'images/slide.jpg' }}); background-size: 100% 200px;background-repeat: no-repeat;background-position: top;">
		<tbody>
			<tr style="text-align: center;">
				<td style="position: relative;color: rgb(0, 0, 0);margin:100px auto 0; text-align:center; width: 465px; background: none repeat scroll 0% 0% rgb(255, 255, 255); padding: 35px 40px;display: block;border-radius: 5px;">
					<img style="margin: 0 auto 20px;" src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo) }}" height="100" alt="company-logo" />
					<br><p style="font-family: arial,sans-serif; font-size: 24px; color: #357ebd;margin:0;margin-bottom: 20px;margin-top:15px;text-align: center;">Thank you for accepting our proposal</p>
					<p style="font-family: arial,sans-serif; font-size: 15px; color: #272727;margin:0;margin-bottom:10px;text-align: center;
	    			line-height: 22px;">Choosing a reliable contractor is the most important decision you will make when it comes to quality & improvement. We are thankful you have decided to use our Company and decided to move forward by accepting the proposal.</p>

	    			<p style="font-family: arial,sans-serif; font-size: 15px; color: #272727;margin:0;text-align: center;
	    			line-height: 22px;">We are committed to exceeding your expectations and look forward to working with you for your complete satisfaction now and in the future.</p>
					<a href="{{config('app.url').config('jp.BASE_PROPOSAL_PATH').$proposal->token.'/view'}}" style="display: inline-block; background-color: #357ebd; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 2px; margin-top: 25px; font-family: arial,sans-serif; font-size: 14px;">View Proposal</a>
					<p style="font-family: arial,sans-serif; font-size: 15px; margin-top: 25px; margin-bottom: 0;">Thanks!</p>
				</td>
			</tr>
		</tbody>
		<tfoot>
			<tr>
				<td cellspacing="0" cellpadding="0" style="font-family: arial,sans-serif; font-size: 13px; color: #000; border-bottom-left-radius: 5px; border-bottom-right-radius: 5px; padding: 30px 18px;text-align: center;">
					Copyright &copy; 2019 <a style="color:#000; text-decoration:none" href="http://jobprogress.com">JOBPROGRESS.COM</a>
				</td>
			</tr>
		</tfoot>
	</table>
</div> 