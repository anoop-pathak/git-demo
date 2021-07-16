<!DOCTYPE html>
<html>
<head>
	<title>Email Print</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="{{config('app.url')}}css/vendor.css" />
	<link rel="stylesheet" href="{{config('app.url')}}css/main.css" />
	<style type="text/css">
		body {
			margin: 20px;
			background: transparent;
		}
		.separator {
			border: 1px solid #ccc;
			margin: 20px 0;
		}
	</style>
</head>
<body>
	<div class="container">
		<table width="100%" cellpadding="0" cellspacing="0" border="0">
			<tbody>
				<tr height="14px">
					<td width="143">

						@if(! empty($company->logo) )
						<img src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo, false) }}" 		width="143" height="59" alt="{{ $company->name ?? ''}} Mail" class="logo"/>
						@else 
						<h1>{{ $company->name ?? ''}}</h1>
						@endif


					</td>
					<td align="right">
						<font size="-1" color="#777"><b>&lt;{{ $emails[0]['from'] }}&gt;</b></font>
					</td>
				</tr>
			</tbody>
		</table>
		<hr>
		<table width="100%" cellpadding="0" cellspacing="0" border="0">
			<tbody>
				<tr>
					<td>
						<font size="+1"><b>{{ $emails[0]['subject'] }}</b></font><br><font size="-1" color="#777">{{ count($emails)}} message(s)</font>
					</td>
				</tr>
			</tbody>
		</table>
		<hr>
		@foreach($emails  as $key => $email)

		<table width="100%" cellpadding="0" cellspacing="0" border="0" class="message">
			<tbody>
				<tr>
					<td><font size="-1">
						<!-- <b>Test data </b> -->
						&lt;{{ $email['from'] }}&gt;
					 </font></td>
					<td align="right"><font size="-1">
						<?php 
							 $timezone =  Settings::get('TIME_ZONE');
							 $createdAt = new \Carbon\Carbon($email['created_at'], $timezone);
							 echo $createdAt->format('D, M d, Y   \\a\\t  h:i A');
						 ?>
					</font></td>
				</tr>
				<tr>
					<td colspan="2">
						<font size="-1" class="recipient">
							<div>To: {!! '&lt;' .implode('&gt;'.', '.'&lt;' , $email['to']). '&gt;' !!}</div>
							@if(ine($email, 'cc'))
								<div>Cc: {!! '&lt;' .implode('&gt;'.', '.'&lt;' , $email['cc']). '&gt;' !!}</div>
							@endif
						</font>
					</td>
				</tr>
			</tbody>
		</table>
		<br>
		<div>
			<div dir="ltr">
				@if(ine($email, 'content')) 
				<?php echo $email['content'];?>
				@endif
			</div>
		</div>
		@if(ine($email, 'attachments') && ine($email['attachments'], 'data') )
		<hr>
		<table class="att" cellspacing="0" cellpadding="5" border="0">
			<tbody>
				<tr>
					<td>
						<table cellspacing="0" cellpadding="0">
							<tbody>
								<?php $attachments = $email['attachments']['data']; ?>
								@foreach ($attachments as $key => $attachment)
									<?php $ext = strtolower(pathinfo($attachment['path'], PATHINFO_EXTENSION)); ?>
									@if(in_array($ext, ['eml', 'ai', 'psd', 've', 'eps', 'dxf', 'pnf', 'skp', 'ac5', 'ac6', 'sdr']))
									<?php  
										$icon = config('app.url').config('jp.MIME_TYPE_ICON_PATH'). $ext . '.png';
									?>
									@elseif(in_array( $attachment['mime_type'], config('resources.image_types') ))
									<?php
									$fullPath = config('resources.BASE_PATH').$attachment['path'];
									$icon = getBase64EncodedData($fullPath, 200, 200);
									?>
									@elseif(in_array( $attachment['mime_type'], config('resources.excel_types') ))
									<?php 
										$icon = config('app.url').config('jp.MIME_TYPE_ICON_PATH'). 'excel.png';
									?>

									@elseif(in_array( $attachment['mime_type'], config('resources.compressed_file_types') ))
									<?php 
										$icon = config('app.url').config('jp.MIME_TYPE_ICON_PATH'). 'zip.png';
									?>
									@elseif(in_array( $attachment['mime_type'], config('resources.word_types') ))
									<?php 
										$icon = config('app.url').config('jp.MIME_TYPE_ICON_PATH'). 'doc.png';
									?>
									@elseif(in_array( $attachment['mime_type'], config('resources.pdf_types') ))
									<?php 
										$icon = config('app.url').config('jp.MIME_TYPE_ICON_PATH'). 'pdf.png';
									?>
									@elseif(in_array( $attachment['mime_type'], config('resources.powerpoint_types') ))
									<?php 
										$icon = config('app.url').config('jp.MIME_TYPE_ICON_PATH'). 'ppt.png';
									?>

									@elseif(in_array( $attachment['mime_type'], config('resources.text_types') ))
									<?php 
										$icon = config('app.url').config('jp.MIME_TYPE_ICON_PATH'). 'txt.png';
									?>
									@endif
									<tr style="display: inline-block; vertical-align: middle; margin-right: 10px;">
										<td>
										<img width="30" height="30" src="{{ $icon ?? ''}}">
										</td>
									<td width="7"></td>
									<td><b>{{ $attachment['name'] ?? ''}}</b><br>{{ formatFileSize($attachment['size']) }} </td>
									</tr>		
								@endforeach
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
		@endif
		<div class="separator"></div>
		@endforeach
	</div>
</body>
</html>