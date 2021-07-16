<div style="margin: auto; border-radius: 5px; padding: 30px 0px;">
	<table cellspacing="0" style="color: #272727; background-color: #F7F8FC; width: 600px; margin: auto; background-image: url({{ config('app.url').'images/slide.jpg' }}); background-size: 100% 200px;background-repeat: no-repeat;background-position: top;">
		<tbody>
			<tr style="text-align: center;">
				<td style="position: relative;color: rgb(0, 0, 0);margin:100px auto 0; text-align:center; width: 465px; background: none repeat scroll 0% 0% rgb(255, 255, 255); padding: 35px 40px;display: block;border-radius: 5px;">
					<img style="margin: 0 auto 20px;" src="{{ FlySystem::getUrl(\Config::get('jp.BASE_PATH').$company->logo) }}" height="100" alt="company-logo" />
					<br>
					<p style="font-family: arial,sans-serif; font-size: 15px; color: #272727;margin:0;margin-bottom:10px;text-align: center; line-height: 22px;">
						{{ $content }}
					</p><br>
					<p style="font-family: arial,sans-serif; font-size: 15px; color: #272727;margin:0;margin-bottom:10px;text-align: center; line-height: 22px;">
						Best,<br>JobProgress Team
					</p>

					@if(isset($proposalFileUrl) && $proposalFileUrl)
					<p style="font-family: arial,sans-serif; font-size: 15px; color: #272727;margin:0;margin-bottom:10px;text-align: center; line-height: 22px;">
						<a href="{{ $proposalFileUrl }}" style="color: #357ebd; text-decoration: none;">Click here to download proposal</a>
					</p>
					@endif
				</td>
			</tr>
		</tbody>
		<tfoot>
			<tr>
				<td cellspacing="0" cellpadding="0" style="font-family: arial,sans-serif; font-size: 13px; color: #000; border-bottom-left-radius: 5px; border-bottom-right-radius: 5px; padding: 30px 18px;text-align: center;">
					Copyright &copy; {{ date("Y") }} <a style="color:#000; text-decoration:none" href="http://jobprogress.com">JOBPROGRESS.COM</a>
				</td>
			</tr>
		</tfoot>
	</table>
</div>