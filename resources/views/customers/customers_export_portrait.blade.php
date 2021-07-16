<!DOCTYPE html>
<html class="no-js" ng-app="jobProgress" ng-controller="AppCtrl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title ng-bind="pageTitle">JobProgress </title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{config('app.url')}}css/main.css" />
    <meta name="viewport" content="width=device-width">
    <style type="text/css">
    body {
        background: #fff;
        margin: 0;
        font-family: Helvetica,Arial,sans-serif;
        font-size: 18px;
        color:#333;
    }
    body label{
            color: #333;
    }
    p {
        margin: 0;
    }
    h1,h2,h3,h4,h5,h6 {
        margin: 0;
    }
    .bd-t0{
        border-top: 0 !important;
    }
    .container {
        /*width: 788px;*/
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
    .jobs-list {
        border: 1px solid #ccc;
        margin: 20px 0;
    }
    .jobs-row {
        font-size: 0;
    }
    .jobs-row p {
        margin-bottom: 10px;
        font-size: 18px;
    }
    .job-col h3 {
        font-size: 20px;
        font-weight: normal;
        margin-bottom: 5px;
        /*color: #434343;*/
    }
    .job-col .rep label {
        float: left;
    }
    .job-col .rep span {
        margin-left: 40%;
        display: block;
    }
    .job-col {
        border-right: 1px solid #ccc;
        display: inline-block;
        font-size: 18px;
        margin-top: 18px;
        padding: 0 18px 0 28px;
        vertical-align: top;
        width: 44.3%;
    }
    .job-col:last-child {
        border-color: transparent;
    }
    .job-detail-part label {
        /*  */
        display: inline-block;
        vertical-align: top;
        margin-bottom: 0;
        /*font-weight: bold;*/
    }
    .job-detail-part p {
        display: inline-block;
        vertical-align: top;
    }
    .upper-text {
        text-transform: uppercase;
    }
    .desc {
        margin: 0 15px;
        padding: 15px 0;
        border-top: 1px solid #ccc;
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
    .desc p {
        font-size: 18px;
    }

    .separator {
        border: 1px solid #dfdfdf;
    }
    .job-address label{
        float: left;
    }
    .job-address p {
        display: block;
        margin-left: 130px;
    }
    .stage i {
        font-style: normal;
    }
    .job-address span{
        display: block;
        margin-left: 105px;
        white-space: normal;
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
    .job-detail-part label.work-type-label{
        float: left;
        width: 137px;
    }
    .job-detail-part p.work-type-values{
        display: block;
        padding-left: 137px;
    }
    .legend > span{
        float: right;
        font-size: 18px;
    }

    .today-date p{
        text-align: right;
        padding-bottom: 3px;
        /*font-weight: bold;*/
    }
    .today-date p label {
        color: #333;
    }
    .upcoming-appointment-title {
        display: inline-block;
        margin-bottom: 5px;
        width: 68%;
    }
    .job-detail-appointment {
        border-bottom: 1px solid #eee;
        cursor: pointer;
        padding: 10px 0;
        font-size: 18px;
    }
    .job-detail-appointment:last-child {
        border-bottom: none;
    }
    .text-right{
        float: right;
    }
   
    .location-box{
        display: inline-block;
    }
    /*.appointment-desc label, .location-box label {
        font-weight: bold;
    }*/
     p.flag span{
        margin-bottom: 5px;
        /*display: inline-block;*/
    }
    .appointment-meta{
        margin-bottom: 5px;
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
    }
    .activity-img p {
        font-size: 18px;
        color: #333;
    }
    .flag{
        /*margin-left: 16px;*/
    }
   td .appointment-sr-container{
        padding: 10px 0px;  
    }
    .customer-flag{
        /*margin-top: 5px;*/
    }
    .appoint-count{
        text-align: center;
    }
    .appoint-count p{
        padding: 3px 0;
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
    }
    /*.assign_to {
        font-weight: bold;
    }*/
    p, span {
        font-weight: normal;
    }
    .appointment-container h3{
        /*color: #434343;*/
        font-size: 20px;
        font-weight: normal;
    }
     .text-alignment {
        display: inline;
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
     p.referred-by-desc {
        text-align: justify;
     }
     .clearfix {
        clear: both;
     }
</style>
</head>
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
            <div>
                @forelse($customers as $customer)
                <div class="jobs-list">
                    <div class="jobs-row">
                        <div class="job-col">
                            <h3>{{ $customer->first_name }} {{ $customer->last_name }}
                            <?php
                               $todayAppointments = $customer->todayAppointments;
                               $upcomingAppointment  = $customer->upcomingAppointments->count();
                            ?>
                           @if(!empty($todayAppointments->count()) )
                                <i class="fsz14 col-red fa fa-calendar"></i>
                            @elseif ( empty($todayAppointments->count()) && !empty($upcomingAppointment) ) 
                                <i class="fsz14 col-black fa fa-calendar"></i>
                           @endif
                           </h3>
                           @if( ($customer->address) 
                           && ($customerAddress = $customer->address->present()->fullAddress))
                                <p> <?php echo $customerAddress; ?></p>
                            @endif
                            <p style="word-wrap: break-word;">{{ $customer->email ?? '' }} </p>
                           @if(isset($customer->phones))
                                @foreach( $customer->phones as $key => $phone )
                                    <p>
                                        {{ ucfirst($phone->label) }}: {{ phoneNumberFormat($phone->number, $company_country_code) }}
                                        @if($phone->ext)
                                          {!! '<br>EXT: '. $phone->ext !!}
                                        @endif  
                                    </p>
                                @endforeach
                            @endif
                        </div>
                        <div class="job-col job-detail-part">
                            @if($secName = $customer->present()->secondaryFullName)
                                <div class="job-address"><label style="width: 150px;">Customer Name:
                                    </label>
                                    <p>{{$secName}}</p>
                                </div>
                            @endif
                            @if( (! $customer->is_commercial) && $customer->company_name)
                                <div class="job-address"><label style="width: 150px;">Company Name:
                                    </label>
                                    <p>{{$customer->company_name}}</p>
                                </div>
                            @endif

                            <div class="job-address">
                                <label style="width: 130px;">Salesman / Customer Rep: </label><p>
                                @if(isset($customer->rep->first_name) || isset($customer->rep->last_name))
                                    {{ $customer->rep->first_name ?? '' }} {{ $customer->rep->last_name ?? '' }}
                                @else
                                    Unassigned
                                @endif
                                </p>
                            </div>
                            <div class="clearfix"></div>

                            <!-- hide in case of sub contractor login -->
                            @if(!\Auth::user()->isSubContractorPrime())
                                <?php //$referredBy = $customer->referredBy(); ?>
                                @if($customer->referred_by_type == 'customer')
                                    <div class="job-address"><label>Referred by: </label><p class="referred-by-desc">{{ $customer->referredBy()->first_name ?? ''}} {{ $customer->referredBy()->last_name ?? ''}}<i style="font-size: 13px;font-style: normal;"><br>(Existing Customer)</i></p></div>
                                @elseif($customer->referred_by_type == 'other') 
                                    <div class="job-address"><label>Referred by: </label><p class="referred-by-desc">{{ $customer->referred_by_note }}</p></div>
                                @elseif($customer->referred_by_type == 'referral')
                                    <div class="job-address"><label>Referred by: </label><p class="referred-by-desc">{{ $customer->referredBy()->name ?? ''}}</p></div>
                                @endif
                                @if($customer->canvasser)
                                    <div class="job-address"><label>Canvasser: </label><p>{{ $customer->canvasser }}</p></div>
                                @endif
                            @endif

                            @if($customer->call_center_rep)
                                <div class="job-address"><label>Call Center Rep: </label><p>{{ $customer->call_center_rep }}</p></div>
                            @endif
                            <?php $billingAddress = null; ?>
                            @if(($customer->billing) 
                            && ($billingAddress = $customer->billing->present()->fullAddress))
                                <div class="job-address"><label>Billing Address: </label><p>{!! $billingAddress !!}</p>  </div>
                            @endif                        
                            <div class="job-address"><label>No of Jobs: </label><p>
                                {{ $customer->jobs->count() }}
                            </p></div>
                        </div>
                           
                    </div>
                    @if(sizeOf($customer->flags))
                        <div class="desc bd-t0 customer-flag">
                                <p class="flag">
                                @foreach($customer->flags as $flag)
                                    <span class="btn-flags label label-default" style="background-color: {{ $flag->color_for_print }}">{{ $flag->title }}</span>
                                @endforeach
                                </p>
                        </div>
                    @endif
                    @if($customer->note)
                    <div class="desc">
                        <p><label>Customer Note: </label>
                        <span class="description">{{$customer->note}}</span></p>
                    </div>
                    @endif
                    <?php 
                        // $todayDate = \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->toDateString();  
                        $appointments = $customer->todayAppointments;
                    ?>
                    @if(sizeOf($appointments))
                    <div class="desc appointment-container">
                        <h3>Appointments </h3>
                        <table style="width:100%">
                        @foreach($appointments as $key => $appointment)
                            <tr>
                                <td valign="top">
                                     <div class="appointment-sr-container">
                                        <div class="activity-img notification-badge">
                                            <div class="appoint-count">
                                                <span>
                                                    <p class="">{{ $key+1 }}</p>
                                                </span>
                                             </div>
                                        </div> 
                                    </div>
                                </td>
                                <td>
                                    <div class="job-detail-appointment">
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
                                                    <label class="assign_to appointment-label">Location: </label>
                                                    <span class="appointment-span-desc">{{ $appointment->location ?? '' }}</span>
                                                </div>
                                                <div class="assign_to"> <label class="appointment-label">Assign To: </label>
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
                                </td>
                            </tr>
                        @endforeach
                        </table>
                    </div>
                    @endif
                </div>
                <div class="separator"></div>
                @empty
                <center>No Customer Found</center>
                @endforelse
            </div>
        </div>
    </div>
</body>
</html>