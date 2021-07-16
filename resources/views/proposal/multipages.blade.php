<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Download</title>
	<link rel="stylesheet" href="{{config('app.url')}}css/template-preview.css" />
	<style type="text/css">
		body .dropzone-container {
		 box-shadow: none;
		}
		body {
		 background: none;
		 margin: 0;
		 font-size: 0;
		}
		.page {
	        overflow: hidden;
	        page-break-before: always;
	    }
	    .attachment-container h2 {
	    	text-align: center;
	    	font-size: 20px;
	    	background: #000;
	    	color: #fff;
	    	padding: 5px;
	    	margin-top: 30px;
	    }
	    .attachment-container .attach-images {
	    	width: 49%;
	    	display: inline-block;
	    	vertical-align: top;
	    	margin-right: 1%;
	    }
	    .attachment-container .attach-images:last-child {
	    	margin-right: 0;
	    }
	    .attachment-container p {
	    	text-align: center;
	    	font-size: 16px;
	    	color: #000;
	    	word-break: break-all;
	    	margin-top: 25px;
    		min-height: 40px;
	    }
	    .customer-detail-info-print {
	    	float: right;
	    }
	    .customer-detail-info-print {
	    	float: right;
	    	font-size: 16px;
	    }
	    .company-detail-info-print {
	    	display: inline-block;
	    	font-size: 16px;
	    }
	    .company-detail-info-print label, .customer-detail-info-print label {
	    	line-height: 20px;
	    	display: block;
	    }
	</style>
</head>
<?php
	// For Precision Restoration Proposal Template Issue (Tempaorary)*/
	$hasPrecisionClass = "";
	if((isset($proposal)) && in_array($proposal->id, config('jp.precision_company_proposals'))) {
		$hasPrecisionClass .= "has-precision";
	}
?>

<body class="wkhtml-print {{$pageType}} legal-size-proposal a4-size-proposal {{$hasPrecisionClass}} " style="width:794px;">
	@foreach($pages as $page)
    <div class="page wkhtml-print" style="position:relative;margin:0;">
       {!!$page->template!!}
		<div style="position:absolute;top:0;left:0;right:0;bottom:0;">
			<img src="{{$page->template_cover}}">
		</div>
    </div>
	@endforeach
	
	@foreach($attachments->chunk($attachments_per_page) as $chunk)
    <div class="page wkhtml-print attachment-container" style="text-align:center;position:relative;margin:0.9cm;">
    	<div style="text-align:left">
	    	<div class="company-detail-info-print">
	    		<label><b>{{$company->name}}</b></label>
	    		<label>{{$company->office_address}}</label>
	    		<label>{{$company->office_address_line_1}}</label>
	    		<label>{{$company->office_city}}, {{$company->state->name}} {{$company->office_zip}}</label>
	    		<label>{{$company->office_email}}</label>
	    	</div>
	    	<div class="customer-detail-info-print">
	    		<label>{{$customer->full_name}}/{{$job->number}}</label>
	    		<label>{{$job->address->address ?? ''}}</label>
	    		<label>{{$job->address->address_line_1 ?? ''}}</label>
	    		<label>{{$job->address->city ?? ''}} {{$job->address->state->name ?? ''}} {{$job->address->zip ?? ''}}</label>
	    	</div>
	    </div>
    	<div style="clear:both"></div>
    	<h2>Attached Images</h2>
		@foreach($chunk as $attachment)
			<div class="attach-images">
			<p>{{$attachment->name}}</p>
				<img style="max-width: 100%; max-height: 350px;" src="{{FlySystem::getUrl(config('jp.BASE_PATH').$attachment->path)}}">
			</div>
		@endforeach	
    </div>
	@endforeach
</body>
</html>