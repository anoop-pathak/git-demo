<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta content="width=device-width, initial-scale=1" name="viewport" />
	<!-- <meta name="viewport" content ="width=device-width,initial-scale=1,user-scalable=no" /> -->
	<title>Proposal | JobProgress</title>
	<meta name="google" content="notranslate">
	<link type="image/x-icon" href="//www.jobprogress.com/app/favicon.ico" rel="shortcut icon">
	<link href="https://fonts.googleapis.com/css?family=Roboto:400,500" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Satisfy" rel="stylesheet">
	<link rel="stylesheet" href="{{getPathWithVersion('css/font-awesome.min.css')}}">
	<link rel="stylesheet" href="{{getPathWithVersion('css/main.css')}}" />
	<link rel="stylesheet" href="{{getPathWithVersion('css/vendor.css')}}" />
	<link rel="stylesheet" href="{{getPathWithVersion('css/customer-proposal.css')}}" />
	<link rel="stylesheet" href="{{getPathWithVersion('css/template.css')}}" />
	<link rel="stylesheet" href="{{getPathWithVersion('css/template-preview.css')}}" />
	<link rel="stylesheet" href="{{getPathWithVersion('css/aside.css')}}" />
	<link rel="stylesheet" href="{{getPathWithVersion('css/snack-bar.css')}}" />
	<link rel="stylesheet" href="{{getPathWithVersion('js/components/angular-loading-bar/build/loading-bar.min.css')}}" />
	<link rel="stylesheet" href="{{getPathWithVersion('js/plugins/perfect-scrollbar/css/perfect-scrollbar.css')}}" />

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
		.required-initial:after {
		    content: "*";
		    color: red;
		    position: absolute;
		    font-size: 20px;
		    right: 0;
		    top: 12px;
		    width: 10px;
		}
	</style>
	<script type="text/javascript" src="{{getPathWithVersion('js/components/jquery/dist/jquery.js')}}"></script>
	<?php
		$accepted = 'accepted';
		$rejected = 'rejected';
		// $file = FlySystem::publicUrl(config('jp.BASE_PATH').$proposal->file_path);
		$file = config('app.url').config('jp.BASE_PROPOSAL_PATH').$proposal->token.'/file';
	?>
</head>
<body id="proposal-ctrl" ng-app="jobProgress" ng-controller="ProposalCtrl as Ctrl" class="public-proposal-page">
	<header>
		<div class="container">
			@if(($division = $proposal->job->division) && ($address = $division->address))
				<?php $companyAddress = $address->present()->fullAddress ?>
			@else
				<?php $companyAddress = $proposal->company->present()->fullAddress; ?>
			@endif
			@if($proposal->company->logo)
			<div class="proposal-header-wrap">
			@else
			<div class="proposal-header-wrap header-nopadding">
			@endif
				<div class="proposal-header-col logo-col">
					@if($proposal->company->logo)
					<div class="logo brand proposal-page-logo">
						<img src="{{FlySystem::getUrl(config('jp.BASE_PATH').$proposal->company->logo)}}" alt="Company">
						<!-- <img src="http://www.doubledconstructionnj.com/wp-content/uploads/2017/03/cropped-double-d-construction-logo.png" alt=""> -->
					</div>
					<div class="header-user-info job-info-wrap">
						<h3>{{ $proposal->company->name ?? '' }}</h3>
						<div class="field-section block-field-section">
							<span class="comp-email">{{ $proposal->company->office_email ?? '' }}</span>
						</div>
						<div class="field-section block-field-section">
							<span>{!! companyAddress !!}</span>
						</div>
						@if($proposal->company->present()->licenseNumber)
						<div class="lic-no">
							<label for="">Contractor Licence #:</label>
							<span>{{ $proposal->company->present()->licenseNumber }}</span>
						</div>
						@endif
					</div>
					@else
					<div class="header-user-info job-info-wrap info-without-logo">
						<h3>{{ $proposal->company->name ?? '' }}</h3>
						<div class="field-section block-field-section">
							<span class="comp-email">{{ $proposal->company->office_email ?? '' }}</span>
						</div>
						<div class="field-section block-field-section">
							<span>{!! companyAddress !!}</span>
						</div>
						@if($proposal->company->present()->licenseNumber)
						<div class="lic-no">
							<label for="">Contractor Licence #:</label>
							<span>{{ $proposal->company->present()->licenseNumber }}</span>
						</div>
						@endif
					</div>
					@endif
				</div>
				<div class="proposal-header-col cust-info">
					<h3>Hi {{ $proposal->job->customer->fullname ?? ''}}!</h3>
					<div class="job-info-wrap">
						<!-- <div class="field-section">
							<label for="">Job ID:</label>
							<span>{{ $proposal->job->number ?? ''}}</span>
						</div>
						<div class="field-section">
							<label for="">Proposal #:</label>
							<span>{{ sprintf("%04d", $proposal->serial_number) }}</span>
						</div> -->
						@if(!$proposal->job->isMultiJob())
						<div class="field-section">
							<!-- <label for="">Trade Types:</label> -->
							<span>{{ implode(', ', $proposal->job->trades->pluck('name')->toArray()) }}</span>
						</div>
						@endif
						<div class="field-section">
							<!-- <label for="">Contact Number:</label> -->
							<span>{{ phoneNumberFormat($proposal->company->office_phone, $proposal->company->country->code) }}</span>
						</div>
						<!-- <div class="field-section">
							<label for="">Contractor Licence #:</label>
							<span>EEE-9999</span>
						</div> -->
						<?php $address = $proposal->job->address; ?>
						@if(($address) && $address->present()->fullAddressOneLine)
						<div class="field-section block-field-section">
							<label for="">Job Address:</label>
							<span>
								{{ $address->city }}
								<br>
								{{ $address->present()->fullAddressOneLine }}
							</span>
						</div>
						@endif
					</div>
				</div>
			</div>

		</div>
	</header>
<div class="clearfix"></div>

	<div class="container main-container">
		<!-- <div class="clearfix"></div> -->
		<div class="row d-flex ps-relative margin0">
			<div class="col-md-3 collapse-toggle collapsed padding0">
				<div class="proposal-sidebar">
					<sidebar-toggle element-class="collapse-toggle" class-to-be-pass="collapsed"></sidebar-toggle>
					<!-- <div class="lic-no">
						<label for="">Contractor Licence #:</label>
						<span>EEE-9999</span>
					</div> -->

					<div class="clearfix"></div>
					<div class="side-menus">
						<perfect-scrollbar
				    		class="sidebar-scroller"
				    		wheel-propagation="false"
				    		swipe-propagation="false"
				    		wheel-speed="10"
				    		style="max-height:450px;">
							<ul>
								<li>
									<a href="{{ url('proposals/'.$proposal->token.'/view') }}" class="{{ (!Request::get('id')) ? 'active' : ' ' }}">JP Viewer
										<div class="sub-text">
											<div class="field-section block-field-section">
												<!-- <label for="">Job ID:</label> -->
												<span>{{ $proposal->job->number ?? ''}} / {{ implode(', ', $proposal->job->trades->pluck('name')->toArray()) }}</span>
											</div>
											<div class="field-section">
												<label for="">Proposal #:</label>
												<span>@if(strpos($proposal->serial_number, '-') !== false){{$proposal->serial_number}} @else{{ sprintf("%04d", $proposal->serial_number) }}@endif</span>
											</div>
											@if($proposal->job->alt_id)
											<div class="field-section">
												<label for="">Job #:</label>
												<span>{{ $proposal->job->full_alt_id }}</span>
											</div>
											@endif
										</div>
									</a>
								</li>
								@foreach($proposal_viewers as $proposal_viewer)
								<li>
									<a href="{{ url('proposals/'.$proposal->token.'/view?id='.$proposal_viewer->id) }}" class="{{ ($proposal_viewer->id == Request::get('id')) ? 'active' : '' }}">
										<?php echo $proposal_viewer->title; ?>
									</a>
								</li>
								@endforeach
							</ul>
						</perfect-scrollbar>
					</div>
				</div>
			</div>
			<div class="col-md-12 col-sm-12 right-content">
				<div class="content-padd">
					@if( Request::has('id') && in_array($proposal->file_mime_type, config('resources.pdf_types')) )
					<div  class="button-container">
						<!-- pdf navigation buttons -->

							<div class="row button-wrap">
								<div class="col-md-12 col-xs-12 right-side-buttons col-sm-12">
									<h3>
										<sidebar-toggle element-class="collapse-toggle" class-to-be-pass="collapsed"></sidebar-toggle>
										<!-- Proposal -->
									</h3>
								</div>
							</div>
					</div>
					@endif
					@if( !in_array($proposal->file_mime_type, config('resources.pdf_types')) )
					<div  class="button-container">
						<!-- pdf navigation buttons -->

							<div class="row button-wrap">
								<div class="col-md-12 col-xs-12 right-side-buttons col-sm-12">
									<h3>
										<sidebar-toggle element-class="collapse-toggle" class-to-be-pass="collapsed"></sidebar-toggle>
										<!-- Proposal -->
									</h3>
									@if(! Request::has('id'))
						                <div class="pull-right btn-wrap" ng-hide="Ctrl.mode == 'edit'">
											<a
												ng-href=""
												class="btn btn-primary"
												ng-hide="Ctrl.isOldProposal"
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
									@endif
								</div>
							</div>
					</div>
					@endif
					<?php
						$class = '';
						if( in_array($proposal->file_mime_type, config('resources.image_types'))  ) {
							$class  = 'proposal-as-image';
						}
						if( in_array($proposal->file_mime_type, config('resources.pdf_types'))  ) {
							$class  = 'proposal-as-pdf';
						}
					?>
					<!-- view proposal -->
					<div class="template-container-section proposal-page-section {{$class}}">
						<div class="content-wrap">

							<div class="left-arrow prev-page"></div>
							<div class="right-arrow next-page"></div>
							@if(!Request::has('id'))
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
							@endif

							@if(Request::has('id'))
								<div class="proposal-page-scroll proposal-text-ui">
									<?php
										echo $get_Proposal_Viewer->description;
									?>
								</div>
							@else
								@if( in_array($proposal->file_mime_type, config('resources.pdf_types')) )
									<!-- view pdf -->
									<!-- <div class="pdf-wrap"> -->
										<customer-page-edit
											ng-if="Ctrl.proposal.id"
											proposal="Ctrl.proposal"
											scope="$parent"
											c-data="{!! htmlspecialchars($proposal->company) !!}"
											token="Ctrl.token" class="modal-content " ></customer-page-edit>
										<!--span is used to append canvas after it in pdf-wrap div-->
										<!-- <span style="display:none"  id="pdf"></span> -->
										<!-- <canvas id="canvas" class="canvas-pdf"></canvas>			 -->
									<!-- </div> -->
								@endif
							@endif

							@if(!Request::has('id'))
								@if( in_array($proposal->file_mime_type, config('resources.image_types')) )
									<!-- view image -->
									<div class="proposal-page-scroll pdf-wrap">
										<img src="{{$file}}" alt="proposal"/>
									</div>
								@endif

								@if( !empty($download_thumb) )
									<!-- download file of all formats except pdf and image -->
									<div class="proposal-page-scroll download-text pdf-wrap">
										<a href="{{config('app.url').config('jp.BASE_PROPOSAL_PATH').$proposal->token}}/file?download=1">
											<img src="{{$download_thumb}}" alt="download">
											<br><br>
											{{trans('response.error.proposal_download')}}/
										</a>
									</div>
								@endif
							@endif
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<!-- 	<footer>

		<div class="container">
			<div class="text-center">
				<a href="http://www.jobprogress.com/terms-of-use/" target="blank">Terms of Services</a>
				<a href="http://www.jobprogress.com/privacy-policy/" target="blank">Privacy Policy</a>

			</div>
		</div>
	</footer> -->

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
			},
			getThankYouEmail: function() {
				var request = '<?php echo app('request')->input('thank_you_email') ?>';

 				if( request == '' ) {
					return 1;
				}

 				return request
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
	<script type="text/javascript" src="{{getPathWithVersion('js/components/angular/angular.min.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/components/signature_pad/signature_pad.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/components/angular-bootstrap/ui-bootstrap.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/components/angular-bootstrap/ui-bootstrap-tpls.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/components/angular-loading-bar/build/loading-bar.min.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/components/angular-validation/dist/angular-validation.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/plugins/angular-validation-rules.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/plugins/perfect-scrollbar/js/perfect-scrollbar.jquery.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/plugins/perfect-scrollbar.js')}}"></script>

	<script type="text/javascript" src="{{getPathWithVersion('js/plugins/signature.js')}}"></script>

	<script type="text/javascript" src="{{getPathWithVersion('js/plugins/pdfjs/pdfobject.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/window.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/plugins/mask.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/app.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/config.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/models/proposal.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/controllers/proposal.controller.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/controllers/proposal-initial-modal.controller.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/controllers/new-accept.controller.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/controllers/new-sign.controller.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/controllers/customer-signature.controller.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/controllers/proposal-comment.controller.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/controllers/customer-proposal-update.controller.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/snack-bar.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/topside.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/blair.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/quinn.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/achten.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/pinnacle.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/double-d-contractors.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/nanfito-roofing.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/ab-edward-enterprises.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/solar-me.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/daniel-hood-roofing.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/first-class-roofing.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/american-roofing.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/jmac.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/claims-pro-restoration/best2.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/claims-pro-restoration/best1.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/affordable-roofing.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/great-roofing.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/burell-built.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/john-henderson-powerform.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/allied-construction.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/midsouth-construction.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/flores-and-cooney-development.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/heartland/best1.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/heartland/best2.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/sidebar-toggle.js')}}"></script>

	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/thoroughbred-roofing.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/george-keller.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/north-wood-roofing.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/keith-gauvin-roofing.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/versatile-roofing.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/sparrow-exteriors.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/dior-construction.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/five-boro-remodeling.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/gb-home-improvements.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/dynamic-home-exteriors.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/wyoming-roofing.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/linkville-roofing.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/pinnacle-remodelling.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/commonwealth-roofing.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/roofing-right.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/residential-roofing-llc.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/a-and-h-exteriors.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/stone-heating-inc.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/pressure-point-roofing-inc.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/pressure-point-roofing-eugene.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/envision-roofing-llc.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/a-and-m-roofing.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/pro-built-homes.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/roof-tech/best1.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/roof-tech/best2.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/subscribers/hudson-contracting/best1.js')}}"></script>

	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/type-signature.directive.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/customer-page-edit/script.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/pdf-viewer.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/selection-container.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/set-table-data.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/disabled-all-input.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/proposal-changed-data.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/scroll-proposal.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/directive/power-form/best.js')}}"></script>

	<script type="text/javascript" src="{{getPathWithVersion('js/app/factory/aside.js')}}"></script>
	<script type="text/javascript" src="{{getPathWithVersion('js/app/service/events.js')}}"></script>

	@if( in_array($proposal->file_mime_type, config('resources.pdf_types')) )
		<!-- <script type="text/javascript" src="{{getPathWithVersion('js/components/pdfjs-bower/dist/pdf.js')}}"></script>
		<script type="text/javascript" src="{{getPathWithVersion('js/proposal/proposal-pdf.js">')}}</script> -->
	@endif

</body>
</html>l