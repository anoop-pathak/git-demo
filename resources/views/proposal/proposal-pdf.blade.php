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
	    	margin-top: 50px;
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
<body class="wkhtml-print {{$pageType}} legal-size-proposal a4-size-proposal" style="width:794px;">
	@foreach($pages as $page)
    <div class="page wkhtml-print" style="position:relative;margin:0;">
       {!! $page['template'] ?? '' !!}
		<div style="position:absolute;top:0;left:0;right:0;bottom:0;">
			<img src="{{$page['template_cover'] ?? ''}}">
		</div>
    </div>
	@endforeach
</body>
</html>
