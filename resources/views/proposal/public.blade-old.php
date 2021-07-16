<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta content="width=device-width, initial-scale=1" name="viewport" />
	<!-- <meta name="viewport" content ="width=device-width,initial-scale=1,user-scalable=no" /> -->
	<title>Proposal | JobProgress</title>
	<meta name="google" content="notranslate">
	<link type="image/x-icon" href="//www.jobprogress.com/app/favicon.ico" rel="shortcut icon">
	<link href="https://fonts.googleapis.com/css?family=Satisfy" rel="stylesheet">
	<link rel="stylesheet" href="{{config('app.url')}}css/main.css" />
	<link rel="stylesheet" href="{{config('app.url')}}css/vendor.css" />
	<link rel="stylesheet" href="{{config('app.url')}}css/customer-proposal.css" />
	<link rel="stylesheet" href="{{config('app.url')}}css/template.css" />
	<link rel="stylesheet" href="{{config('app.url')}}css/template-preview.css" />	
	<link rel="stylesheet" href="{{config('app.url')}}css/aside.css" />	
	<link rel="stylesheet" href="{{config('app.url')}}css/snack-bar.css" />
	<link rel="stylesheet" href="{{config('app.url')}}js/components/angular-loading-bar/build/loading-bar.min.css" />

	<style>
		
		#loading-bar-spinner{
			top: 30px;
			left: 30px;
		}
		body #loading-bar-spinner {
			background: none;
		}
		body.modal-open {
		    overflow: hidden;
		}
		.pdfobject-container {
			width: 100%;
			max-width: 100%;
			height: 1200px;
			margin: 0;
		}	
		.modal-body .dropzone-container {
			float: left;
			margin: 20px !important;
		}
		.container.main-container {
			min-height: 800px;
		}
	</style>
	<script type="text/javascript" src="{{config('app.url')}}js/components/jquery/dist/jquery.js"></script>
	<?php
		$accepted = 'accepted';
		$rejected = 'rejected';
		// $file = FlySystem::publicUrl(config('jp.BASE_PATH').$proposal->file_path);
		$file = config('app.url').config('jp.BASE_PROPOSAL_PATH').$proposal->token.'/file';
	?>
</head>
<body id="proposal-ctrl" ng-app="jobProgress" ng-controller="ProposalCtrl as Ctrl">
	<div>
	<header>
		<div class="container">
			<div class="row">
				<div class="col-sm-4 col-xs-12 logo-col">
					<div class="logo brand">
						@if($proposal->company->logo)
							<img src="{{FlySystem::getUrl(config('jp.BASE_PATH').$proposal->company->logo)}}/" alt="Company">
						@else
							<span>{{ $proposal->company->name ?? '' }}</span>
						@endif
					</div>
				</div>
				<div class="col-sm-4 col-xs-12">
					<div class="working-details">
						<h1>Proposal</h1>
						<div class="job-id-section">
							<p>Contact Number: {{$proposal->company->office_phone}}</p>
							<p>Proposal #: {{ sprintf("%04d", $proposal->serial_number) }}</p>
						</div>
					</div>
				</div>
				<div class="col-sm-4 col-xs-12 cust-info">
					<p>
						<label>Customer Name: </label>
						{{ $proposal->job->customer->fullname ?? ''}}
					</p>
					<p>
						<label>Job ID: </label>
						{{ $proposal->job->number ?? ''}}

					</p>
					@if(!$proposal->job->isMultiJob())
					<p>
						<label>Trade Types: </label>
						{{ implode(', ', $proposal->job->trades->pluck('name')->toArray()) }}
					</p>
					@endif
				</div>
			</div>
		</div>
	</header>


	<div class="container main-container">
		<div ng-hide="Ctrl.mode == 'edit'" >
			<div  class="button-container">
				<!-- pdf navigation buttons -->
				@if( !in_array($proposal->file_mime_type, config('resources.pdf_types')) )
				<div class="row button-wrap">
					<div class="col-md-12 col-xs-12 right-side-buttons col-sm-12">
						@if(  ($proposal->is_file == '0') && !in_array($proposal->status, [$accepted, $rejected])  )
						<!-- <a  
							ng-if="Ctrl.proposal"
							is-customer-input="Ctrl.proposal"
							customer-attr="Ctrl.customerInput"
							ng-href=""
							style="display: none;"
							class="btn pull-left btn-primary"
							ng-click="Ctrl.editByCustomer(true)">
							Update
						</a> -->
						@endif


						<a  
							ng-href=""
							class="btn btn-primary"
							ng-disabled="Ctrl.disabed"
							ng-style="Ctrl.initialBtn" 
							ng-click="Ctrl.doInitialSignature()">
							Initial
						</a>
						@if( !in_array($proposal->status, [$accepted, $rejected]) )
							<!-- Status Buttons -->
							<a  ng-href=""
								ng-disabled="Ctrl.disabed || Ctrl.requiredCustomerInitial"
								class="btn btn-success"
								ng-click="Ctrl.accept()">
									Accept
							</a>
							<a 	ng-href=""
								ng-disabled="Ctrl.disabed" 
								class="btn btn-danger"
								ng-click="Ctrl.reject()">
								Reject
							</a>
						@endif

						@if( in_array($proposal->file_mime_type, config('resources.pdf_types')) )
							<!-- Print Button -->
							<a ng-href="{{config('app.url').config('jp.BASE_PROPOSAL_PATH').$proposal->token}}/file"
								target="_blank">
								<button class="btn btn-inverse">
									Print
								</button>
							</a>
						@endif

						<!-- Download Button -->
						<a ng-href="{{config('app.url').config('jp.BASE_PROPOSAL_PATH').$proposal->token}}/file?download=1">
							<button class="btn btn-inverse">
								Download
							</button>
						</a>
					</div>
				</div>
				@endif
			</div>
			<!-- view proposal -->
			<div class="template-container-section proposal-page-section">
				<div class="content-wrap">

					<div class="left-arrow prev-page"></div>
					<div class="right-arrow next-page"></div>
					<!-- flags -->
					@if( !in_array($proposal->status, [$accepted, $rejected]) )
						<div class="strip-flag">
							<img src="{{config('app.url')}}/proposal_theme/images/open.png" alt="Open">
						</div>
					@endif

					@if($proposal->status == $accepted)
						<div class="strip-flag">
							<img src="{{config('app.url')}}/proposal_theme/images/accepted.png" alt="Accepted">
						</div>
					@endif

					@if($proposal->status == $rejected)
						<div class="strip-flag">
							<img src="{{config('app.url')}}/proposal_theme/images/rejected.png" alt="Rejected">
						</div>
					@endif

					@if( in_array($proposal->file_mime_type, config('resources.pdf_types')) )
						<!-- view pdf -->
						<!-- <div class="pdf-wrap"> -->
							<customer-page-edit 
								ng-if="Ctrl.proposal.id"
								proposal="Ctrl.proposal" 
								scope="$parent"
								c-data="{!! htmlspecialchars($proposal->company) !!}/"
								token="Ctrl.token" class="modal-content " ></customer-page-edit>
							<!--span is used to append canvas after it in pdf-wrap div-->
							<!-- <span style="display:none"  id="pdf"></span> -->
							<!-- <canvas id="canvas" class="canvas-pdf"></canvas>			 -->
						<!-- </div> -->
					@endif

					@if( in_array($proposal->file_mime_type, config('resources.image_types')) )
						<!-- view image -->
						<div class="pdf-wrap">
							<img src="{{$file}}" alt="proposal"/>
						</div>
					@endif

					@if( !empty($download_thumb) )
						<!-- download file of all formats except pdf and image -->
						<div class="download-text">
							<a href="{{config('app.url').config('jp.BASE_PROPOSAL_PATH').$proposal->token}}/file?download=1">
								<img src="{{$download_thumb}}" alt="download">
								<br><br>
								{{trans('response.error.proposal_download')}}/
							</a>
						</div>
					@endif
				</div>
			</div>	
		</div>



	</div>
	<footer>
		<br>
		<br>
		<!-- <div class="container">
			<div class="text-center">
				<a href="http://www.jobprogress.com/terms-of-use/" target="blank">Terms of Services</a>
				<a href="http://www.jobprogress.com/privacy-policy/" target="blank">Privacy Policy</a>
				
			</div>
		</div> -->
	</footer>


	<script type="text/javascript">
		/*set url variables used in proposal-pdf.js*/
		var pdfUrl = '<?php echo $file; ?>';
		var appUrl = '<?php echo config("app.url"); ?>';
		var pageUrlToken = '<?php echo $proposal->token; ?>'
		var Customer = {
			firstName: '<?php echo addslashes($proposal->job->customer->first_name) ?>',
			lastName: '<?php echo addslashes($proposal->job->customer->last_name) ?>',
			fullName: '<?php echo addslashes($proposal->job->customer->fullname) ?>',
			getFirstletter: function() {
				var txt = this.fullName;
				var matches = txt.match(/\b(\w)/g);              // ['J','S','O','N']
				return matches.join('');        
			}
		};
		var currPage = 1;
		$(".next-page").click(function() {
			if(currPage < totalPages) {
				goToPage(currPage + 1);
			}
		});
		$(".prev-page").click(function(){
			if(currPage > 1) {
				goToPage(currPage - 1);
			}
		});
		$(".first-page").click(function() {
			if(currPage != 1) {-
   				goToPage(1);
   			}
		});
		$(".last-page").click(function() {
			if(currPage != totalPages) {
				goToPage(totalPages);
			}
		});
	</script>

	<script type="text/ng-template" id="signature.html">
		<div class="modal-header">
			<h3 style="padding:0;" class="modal-title" ng-bind="Sign.heading"></h3>
		</div>

		<div class="modal-body">

			<div>
				<div class="checkbox proposal-check">
					<label for="mouse">
						<input
							type="checkbox"
							id="mouse"
							ng-model="Sign.signType.mouse"
							ng-change="Sign.selectType('mouse')">Use Mouse
					</label>
					<label for="text">
					<input
						type="checkbox"
						id="text"
						ng-model="Sign.signType.text"
						ng-change="Sign.selectType('text')">Use Keyboard
					</label>
				</div>

				<div class="server-erros" ng-if="Sign.signError">
					<div class="alert alert-danger">
					  Please Sign First.
					</div>
				</div>

				<div style=""  class="sign-container" ng-show="Sign.signType.mouse">
					<signature-pad 
						accept="accept" 

						clear="clear" 
						height="300" 
						width="550"></signature-pad>
				</div>

				<div style=""  class="sign-container" ng-show="Sign.signType.text">
					<text:signature
						options="Sign.signOpt"
						></text:signature>

				</div>
			</div>
		</div>

		<div class="modal-footer">
			<button style="color:#fff;" class="btn btn-primary" type="button" ng-click="Sign.save()">Accept Proposal</button>
			<button style="color:#fff;" class="btn btn-inverse" type="button" ng-click="clear()" ng-if="Sign.signType.mouse">Clear</button>
			<button style="color:#fff;" class="btn btn-inverse" type="button" ng-click="Sign.close()">Cancel</button>
		</div>
	</script>

	<script type="text/ng-template" id="comment.html">
		<div class="modal-header">
			<h3 style="padding:0;" class="modal-title" ng-bind="Comment.heading"></h3>
		</div>

		<div class="modal-body">

			<div>
				<div style=""  class="sign-container">
					<textarea 
						style="height:100px;width:100%;"
						ng-model="Comment.rejected.comment"></textarea>
				</div>
			</div>
		</div>

		<div class="modal-footer">
			<button 
				style="color:#fff;" 
				class="btn btn-primary" 
				type="button"
				ng-disabled="!Comment.rejected.comment"
				ng-click="Comment.save()">
				Reject Proposal
			</button>
			<button style="color:#fff;" class="btn btn-inverse" type="button" ng-click="Comment.close()">Cancel</button>
		</div>
	</script>

	<script type="text/ng-template" id="signature-directive.html">
		<div class="options-sign">
			<div class="style-options-comp text-options-comp">
				<label>Text </label>
				<input 
					type="text" 
					ng-model="userEntertext" 
					class="input form-control" 
					ng-change="updateImage('text')">
			</div>
			<div class="style-options-comp"> 
				<label>Font Size</label>
				<input 
					type="text" 
					ng-model="userEnterSize" 
					class="input form-control" 
					ng-change="updateImage('size')"  class="font-size">
			</div>
			<div class="style-options-comp">
				<label>Font family </label>
				<select
					ng-model="userEnterFamily"
					name="familyList" 
					ng-change="updateImage('font-family')" 
					id="familyList"
					class="form-control">
					<option ng-repeat="family in familyList"><% family %></option>
				</select>
			</div>
			<div class="style-options-comp">
				<label>Font Style</label>
				<select
					ng-model="userEnterStyle"
					name="styleList" 
					ng-change="updateImage('style')" 
					id="styleList"
					class="form-control">
					<option ng-repeat="style in styleList"><% style %></option>
				</select>
			</div>

			<% data.isValidImage() %>

			<p class="server-errors" style="color:red" ng-if="userEnterSize < 40"> Font size must be greater than or equal to 40. </p>

			<!-- cnvas -->
			<div class="canvas-section">

			</div>
		</div>

	</script>

	<script type="text/javascript" src="{{config('app.url')}}js/components/angular/angular.min.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/components/signature_pad/signature_pad.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/components/angular-bootstrap/ui-bootstrap.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/components/angular-bootstrap/ui-bootstrap-tpls.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/components/angular-loading-bar/build/loading-bar.min.js"></script>

	<script type="text/javascript" src="{{config('app.url')}}js/plugins/signature.js"></script>

	<script type="text/javascript" src="{{config('app.url')}}js/plugins/pdfjs/pdfobject.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/window.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/app.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/config.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/models/proposal.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/controllers/proposal.controller.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/controllers/proposal-initial-modal.controller.js"></script>

	<script type="text/javascript" src="{{config('app.url')}}js/app/controllers/customer-signature.controller.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/controllers/proposal-comment.controller.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/controllers/customer-proposal-update.controller.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/snack-bar.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/topside.js"></script>	
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/blair.js"></script>	
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/quinn.js"></script>	
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/achten.js"></script>	
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/pinnacle.js"></script>	
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/double-d-contractors.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/nanfito-roofing.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/ab-edward-enterprises.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/solar-me.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/daniel-hood-roofing.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/first-class-roofing.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/american-roofing.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/jmac.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/claims-pro-restoration/best2.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/claims-pro-restoration/best1.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/affordable-roofing.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/great-roofing.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/burell-built.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/john-henderson-powerform.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/allied-construction.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/midsouth-construction.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/flores-and-cooney-development.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/heartland/best1.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/heartland/best2.js"></script>


	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/wyoming-roofing.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/linkville-roofing.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/subscribers/pinnacle-remodelling.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/type-signature.directive.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/customer-page-edit/script.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/pdf-viewer.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/selection-container.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/set-table-data.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/disabled-all-input.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/proposal-changed-data.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/scroll-proposal.js"></script>


	<script type="text/javascript" src="{{config('app.url')}}js/app/factory/aside.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/service/events.js"></script>

	@if( in_array($proposal->file_mime_type, config('resources.pdf_types')) )
		<!-- <script type="text/javascript" src="{{config('app.url')}}js/components/pdfjs-bower/dist/pdf.js"></script>
		<script type="text/javascript" src="{{config('app.url')}}js/proposal/proposal-pdf.js"></script> -->
	@endif

</body>
</html>