<!DOCTYPE html>
<html class="no-js">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title> JobProgress </title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="{{config('app.url')}}css/main.css" />
	<meta name="viewport" content="width=device-width">
	<style type="text/css">
	body {
		background: #fff;
		margin: 0;
		font-family: Helvetica,Arial,sans-serif;
		font-size: 18px;
		color: #333;
	}
	p {
		margin: 0;
	}
	h1,h2,h3,h4,h5,h6 {
		margin: 0;
	}
	.container {
		text-align: left;
    	width: 100%;
		margin: auto;
		background: #fff;
	}
	.jobs-export {
		padding: 15px;
	}
	h1 {
		margin: 4px 0;
		font-size: 26px;
		font-weight: normal;
	}
	.header-part {
		display: inline-block;
		width: 70%;
	}
	.header-part .date-format {
		font-size: 13px;
		margin: 0;
		margin-top: 3px;
	}
	.header-part h1 {
		display: inline-block;
		vertical-align: top;
	}
	.clearfix {
		clear: both;
	}
	.main-logo {
		float: right;
	}
	.main-logo img {
		width: 200px;
		opacity: 0.6;
		margin-bottom: 10px;
	}
	.company-name {
		font-size: 20px;
	    margin-bottom: 10px;
	    margin-top: 5px;
	    /*font-weight: bold;*/
	}
	.filters-section h5 {
		font-weight: bold;
		font-size: 18px;
		margin-bottom: 5px;
	}
	.filters-section label {
		color: #333;
		font-size: 18px;
		font-weight: normal;
	}
	.filters-section label span {
		color: #000;
	}
	.filters-section label span.trade {
		text-transform: uppercase;
	}
	.upper-text {
		text-transform: uppercase;
	}
	
	.label {
		 border-radius: 0.25em;
	    color: #fff;
	    display: inline;
	    font-size: 75%;
	    font-weight: 700;
	    line-height: 1;
	    padding: 0.2em 0.6em 0.3em;
	    text-align: center;
	    vertical-align: baseline;
	    white-space: nowrap;
	}
	.label-default {
	    background-color: #777;
	}
	.table {
		border-collapse: collapse;
		width: 100%;
		border: 1px solid #ddd;
		margin: 20px 0;
		table-layout: fixed;
	}
	.table tr.page-break {
		border-bottom: 1px solid #ccc;
	}
	.table tr.page-break table {
		border-collapse: collapse;
		table-layout: fixed;
	}
	.page-break {
		page-break-inside: avoid;
	}
	.table th {
		border-bottom: 1px solid #ccc;
	}
	.table tr.page-break:nth-child(2n+1) {
	    background-color: #f9f9f9;
	}
	.table th, .table tr.page-break td td {
		padding: 10px;
	}
	.stage i {
		font-style: normal;
	}
	.company-logo {
        border: 1px solid rgb(204, 204, 204);
        text-align: center;
        height: 130px;
        line-height: 128px;
        width: 128px;
        border-radius: 8px;
        box-sizing: border-box;
        display: inline-block;
        margin-right: 10px;
    }
    .company-logo img {
        max-width: 100%;
        max-height: 100%;
        display: inline-block;
        vertical-align: middle;
        box-sizing: border-box;
        transition: all 0.2s ease-in-out 0s;
        -webkit-transition: all 0.2s ease-in-out 0s;
    }
	.reps{
		width: 150px;
	}
	.trades{
		width: 230px;	
	}
	.legend > span{
		float: right;
		font-size: 18px;
	}
	.customer-rep-heading{
		white-space: nowrap;
	}
	.flag{
		display: block;
		margin-bottom: 10px;
	}
	.activity-img{
        width: auto; 
        min-width: 24px; 
        top: 50%;
        height: 24px;
        float: left;
        border-radius: 50%;
        margin-right: 5px;
        background: #ccc;
        text-align: center;
    }
    .appointment-sr-container{
        padding: 10px 0px; 
        float: left; 
    }
    .appointment-sr-container .activity-img p{
    	padding: 2px 0;
    	font-size: 18px;
    	color: #333;
    }
	.upcoming-appointment-title {
		display: inline-block;
		margin-bottom: 5px;
		width: 82%;
	}
	.job-detail-appointment {
	    border-bottom: 1px solid #eee;
	    cursor: pointer;
	    padding: 10px 0;
	    font-size: 18px;
	    margin-left: 40px;
	}
	.job-detail-appointment:last-child {
		border-bottom: none;
	}
	.text-right{
		float: right;
	}
	.today-date p{
		text-align: right;
		padding-bottom: 3px;
		/*font-weight: bold;*/
	}
	.today-date p label {
		color: #333;
	}
	.location-box{
		display: inline-block;
	}
/*	.location-box label, .appointment-desc label {
		font-weight: bold;
	}*/
	.appointment-container{
		margin-left: 0px;
		padding-left: 0px;
	}
	.desc {
		margin-right: 15px;
		padding-right: 15px;
	}
	.customer-desc{
		padding-bottom: 10px;
	}
	.second-part{
		border-top: 1px solid #eee;
		padding-top: 15px;
	}
	.appointment-meta{
    	margin-bottom: 5px;
    }
    .btn-flags {
    	border: none;
	    color: #fff;
	    background: #777;
	    font-size: 14px;
	    cursor: default;
	    height: 16px;
	    width: auto;
	    line-height: 16px;
	    margin-right: 15px;
	    position: relative;
	    padding-left: 8px;
	    padding-right: 8px;
	    border-radius: 3px;
	    display: inline-block;
	    vertical-align: middle;
	    margin-bottom: 5px;
  	}
  	.btn-flags:hover {
    	color: #fff;
    }
  	/*.btn-flags:after {
    	position: absolute;
      	top: 0px;
      	right: -21px;
      	content: "";
      	border-color: transparent transparent transparent #777;
      	border-style: solid;
      	border-width: 11px;
      	height: 0;
      	width: 0;
  	}*/
  	.description {	
		text-align: justify;
		white-space: pre-wrap;
		page-break-inside: avoid;
		/*display: table-row-group*/
  	}
  	/*.assign_to {
  		font-weight: bold;
  	}*/
  	p, span {
  		font-weight: normal;
  	}
  	.no-record {
  		margin: 50px auto;
	 	text-align: center;
	 	font-size: 30px;
	 	padding: 50px;
  	}
  	.job-heading {
	 	/*color: #434343;*/
	 }
	 .text-alignment {
	 	display: inline;
	 }
	 label {
	 	color: #333;
	 	/*color: inherit;*/
	 }
	.appointment-meta .assign_to {
		margin: 3px 0;
	}	
	.appointment-label {
	 	float: left; 
	 	width: 101px;
	 }
	 .appointment-span-desc {
	 	display: block; 
	 	margin-left: 101px;
	 }
	 /*to avoid repeating header*/
	thead, tfoot { display: table-row-group }
    </style>
    <style type="text/css"></style></head>
    <body>
    	<div class="container">
    		<div class="jobs-export">
    			<div class="header-part">
				@if(! empty($company->logo) )
					<div class="company-logo">
						<img src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo) }}" />
					</div>
				@endif
					<h1 style="width: 70%;">{{$company->name ?? ''}}</h1>
					<p class="company-name">Customers Export</p>
				</div>
				<div class="main-logo">
					<img src="{{asset('main-logo.png')}}">
					<div class="today-date">
	                    <p><label>Current Date: </label><?php echo  \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format')) ; ?></p>
	                </div>
				</div>
    			<div class="clearfix"></div>
    			<table class="table">
    				<thead>
    					<tr>
    						<th width="4%">#</th>
    						<th width="14%">Customer Name</th>
    						<th width= "14%">Customer Info.</th>

    						@if(\Auth::user()->isSubContractorPrime())
								<th width= "23%">Address</th>
								<th width= "23%">Billing Address</th>
								<th class="customer-rep-heading" width="17%">Salesman /<br> Customer Rep</th>
    						@else
								<th width= "20%">Address</th>
								<th width= "20%">Billing Address</th>
								<th class="customer-rep-heading" width="12%">Salesman /<br> Customer Rep</th>
								<th width = "11%">Referred by</th>
    						@endif

    						<th width="5%">No of Jobs</th>
    					</tr>
    				</thead>
    				<tbody>
    					<?php $sr = 1; ?>
    					@foreach($customers as $customer)
    					<tr class="page-break">
    						<td colspan="8">
    							<table style="width: 100%;">
    								<tr>
    									<td width="4%" valign="top" >{{ $sr++ }}</td>
			    						<td width="14%" valign="top">
			    							<?php 
			    								$secName = $customer->present()->secondaryFullName; 
			    								$todayAppointments = $customer->todayAppointments;
											   	$upcomingAppointment  = $customer->upcomingAppointments->count();
			    							?>
			    							<span class="job-heading ">{{ $customer->first_name }}
			    							{{ $customer->last_name }}
				    							 @if((!$secName) && !empty($todayAppointments->count() ))
											  		<i class="fsz14 col-red fa fa-calendar"></i>
											   	@elseif ((!$secName) && empty($todayAppointments->count()) && !empty($upcomingAppointment) ) 
												    <i class="fsz14 col-black fa fa-calendar"></i>
											   	@endif
			    							</span><br>
				    						@if($secName)
			    								<span style="font-size: 13px">
				    								<p>({{$secName}})

													   @if(!empty($todayAppointments->count()) )
													  		<i class="fsz14 col-red fa fa-calendar"></i>
													   	@elseif ( empty($todayAppointments->count()) && !empty($upcomingAppointment) ) 
														    <i class="fsz14 col-black fa fa-calendar"></i>
													   @endif
				    								</p>
			    								</span>
				    						@endif
			    						</td>
			    						<td style="word-break: break-all;" width= "14%" valign="top" >
			    							<p> 
			    								@if($customer->email)
			    									{{ $customer->email }} <br>
			    								@endif
												@foreach( $customer->phones as $phone )
													({{substr(ucfirst($phone->label), 0,1)}}) {{ phoneNumberFormat($phone->number, $company_country_code) }} 
													@if($phone->ext)
													  {!! '<br>EXT: '. $phone->ext  !!}
													@endif
													<br/>

												@endforeach
											</p>
			    						</td>
			    						@if(\Auth::user()->isSubContractorPrime())
				    						<td valign="top" width= "23%">
			    								@if(isset($customer->address->address) 
												&& ($customerAddress = $customer->address->present()->fullAddress))
													<?php echo $customerAddress; ?>
												@endif
											</td>
											<td valign="top" width= "23%">
												<?php $billingAddress = null; ?>
												@if(isset($customer->billing->address) 
												&& ($billingAddress = $customer->billing->present()->fullAddress))
													<?php echo $billingAddress; ?>
												@endif
											</td>
											<td valign="top" width="17%">
												@if(isset($customer->rep->first_name) || isset($customer->rep->last_name))
												{{ $customer->rep->first_name ?? '' }} {{ $customer->rep->last_name ?? '' }}
												@else
													Unassigned
												@endif
											</td>
			    						@else
			    							<td valign="top" width= "20%">
			    								@if(isset($customer->address->address) 
												&& ($customerAddress = $customer->address->present()->fullAddress))
													<?php echo $customerAddress; ?>
												@endif
											</td>
											<td valign="top" width= "20%">
												<?php $billingAddress = null; ?>
												@if(isset($customer->billing->address) 
												&& ($billingAddress = $customer->billing->present()->fullAddress))
													<?php echo $billingAddress; ?>
												@endif</td>
											<td valign="top" width="12%">
												@if(isset($customer->rep->first_name) || isset($customer->rep->last_name))
												{{ $customer->rep->first_name ?? '' }} {{ $customer->rep->last_name ?? '' }}
												@else
													Unassigned
												@endif
											</td>
											<td valign="top" width = "11%">

												<?php if($customer->referred_by_type == "customer"): ?>
													{{ $customer->referredBy()->first_name ?? ''}} {{ $customer->referredBy()->last_name ?? ''}}
													<span style="font-size: 13px"><br>(Existing Customer)</span>
												<?php elseif($customer->referred_by_type == 'referral'): ?>
													{{ $customer->referredBy()->name ?? ''}}
												<?php elseif($customer->referred_by_type == 'other'): ?>
													{{ "Note " . $customer->referred_by_note }}
												<?php endif; ?>
											</td>
			    						@endif
										<td valign="top" width="5%">
											{{ $customer->jobs->count() }}
										</td>
			    					</tr>
			    					<tr>
			    						<td></td>
			    						<td colspan="7">
			    						<?php 
					                        // $todayDate = \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->toDateString();  
					                        $appointments = $customer->todayAppointments;
					                    ?>
			    						@if(sizeOf($appointments) || !empty($customer->note) || sizeOf($customer->flags) )
			    							<div class="second-part">
												@if(sizeOf($customer->flags))
													<p class="flag">
													@foreach($customer->flags as $flag)
													<span class="btn-flags label label-default" style="background-color: {{ $flag->color_for_print }}">{{ $flag->title }}</span>
													@endforeach
													</p>
												@endif
				    							@if($customer->note)
				    								<div class="desc customer-desc">
				    									<label class="job-heading">Customer Note: </label>
				    									<span class="description">{{ $customer->note }}</span>
				    								</div>
				    							@endif
				    							@if(sizeOf($appointments))
												<div class="desc appointment-container" >
													<p>Appointments </p>
													@foreach($appointments as $key => $appointment)
					                                    <div class="appointment-sr-container">
					                                        <div class="activity-img notification-badge">
					                                        	<p class="">{{ $key+1 }}</p>
					                                        </div> 
					                                    </div>
														<div class="job-detail-appointment" style="dispaly:flex;">
															<div class="cust-address">
																<div>
																	<p class="upcoming-appointment-title">
																		<span>{{ $appointment->title ?? ''}}</span>
																	</p>
																	<div class="pull-right">
																		<span class="text-alignment" style="white-space: nowrap;">
																			<?php 
																				$dateTime = new Carbon\Carbon($appointment->start_date_time,'UTC');
																				$dateTime->setTimeZone(Settings::get('TIME_ZONE'));
																			?>
																			{{ $dateTime->format(config('jp.date_time_format')) }}
																		</span>
																	</div>
																</div>
																<div>
																<div class="appointment-desc">
																		<label class="appointment-label">Recurring: </label>
																		<span class="description appointment-span-desc">{{ $appointment->present()->recurringText}}</span>
																	</div>
																</div>
																<div class="appointment-meta">
																   <div class="location-box">
										                                <label class="appointment-label">Location: </label>
										                                <span class="appointment-span-desc">{{ $appointment->location ?? '' }}</span>
										                            </div>
										                            <div class="assign_to"> 
										                            	<label class="appointment-label">Assign To:</label>
										                                <span class="text-alignment appointment-span-desc">{{ $appointment->present()->assignedUserName }}</span>
										                            </div>
																	@if($appointment->createdBy)
																		<div>
												                            <div class="appointment-desc">
																				<label class="appointment-label">Created By: </label>
																				<span class="description appointment-span-desc">{{ $appointment->createdBy->full_name }}</span>
																			</div>
																		</div>
																	@endif
																</div>
																@if($appointment->description)
																<div>
										                            <div class="appointment-desc">
																		<label class="appointment-label">Note: </label>
																		<span class="description appointment-span-desc">{{ $appointment->description}}</span>
																	</div>
																</div>
																@endif
															</div>
														</div>
													@endforeach
												</div>
												@endif
											</div>
			    						@endif
			    						</td>
    								</tr>
    							</table>
    						</td>
    					</tr>
    				
    					@endforeach
    				</tbody>
    			</table>
    			@if(!count($customers))
    				<div class="no-record">No Records Found</div>
    			@endif
    		</div>
    	</div>
    </body>
</html>