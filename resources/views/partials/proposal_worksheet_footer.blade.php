
	@if(ine($footer, 'customer_rep_signature') 
		&& (($customer->rep) 
		&& ($rep = $customer->rep->signature)))
	<div class="pull-left">
		<label style="vertical-align: top">Customer Rep Signature: </label><br>
		<div class="sign section-block"><div class="jp-border jp-signature" style="width:230px;height:80px"> <img src="{{ $rep->signature }}" class="sign-temp ng-isolate-scope" path="img/sign.png"> <div class="sign-date">{{ \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format'))}}</div> </div></div>
	</div>
	@endif

	@if(ine($footer, 'customer_signature'))
		@if($proposal->signature)
		<div class="pull-right">
			<label style="vertical-align: top">Customer Signature: </label><br>
			<div class="sign section-block"><div class="jp-border jp-signature" style="width:230px;height:80px"> <img src="{{ $proposal->signature }}" class="sign-temp ng-isolate-scope" path="img/sign.png"> <div class="sign-date">{{ \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format'))}}</</div> </div></div>
		</div>
		@else
		<div class="pull-right">
			<label style="vertical-align: top">Customer Signature:</label><br> 
			<div class="sign section-block"><div class="jp-border jp-signature" style="width:230px;height:80px"> <img src="{{ config('app.url')}}/placeholder/sign.png" class="sign-temp ng-isolate-scope" path="img/sign.png"> <div class="sign-date">Signature Date</div> </div></div>
		</div>
		@endif
	@endif