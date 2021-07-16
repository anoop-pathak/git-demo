@extends('quickbooks.layout')

@section('content')

{{ URL::forceScheme(config('jp.force_scheme')) }}
<div class="row justify-content-md-center media-row-margin row-flex">
	<aside class="col-md-12 col-width-set">
		<article class="card">
			<div class="card-body">
				<p class="qb-logo">
					<img src="{{config('app.url')}}qb-pay/img/qb_pay_img.png" width="150px" height="auto" alt="QuickBook Payment for Invoice">
					<span class="pull-right charge-amt">
					<label for="">Amount:</label>  
						{{ currencyFormat($amount) }} 
					</span>
				</p>
				<form id="qb-pay" role="form" action="{{ URL::route('quickbooks.payment') }}?@foreach($invoices as $invoice)invoices[]={{ $invoice->id }}&@endforeach
				" method="post" class="qb-form" name="qbpay">
					{{ Form::token() }}
					<input type="hidden" name="amount" value="{{ $amount }}">
					@foreach($invoices as $invoice)
					<input type="hidden" name="invoices[]" value="{{ $invoice->id }}">
					@endforeach
					@if($redirectWeb)
					<input type="hidden" name="redirect-web">
					@endif
					<div class="form-group required-input">
						<label for="username" class="field-label">Name on Card</label>
						<div class="field-input">
							<div class="input-group">
								<input 
									type="text" 
									class="form-control" 
									name="credit_card[name]" 
									ng-model="frm.name" 
				            		value="<%frm.name%>" 
				            		validator="required" 
									placeholder="Enter Full Name">
								<span class="help-block">{{ $errors->first('name') }}</span>
							</div> 
						</div>
					</div> 

					<div class="form-group required-input">
						<label for="cardNumber" class="field-label">Card Number</label>
						<div class="field-input">
							<div class="input-group card-field ">
								<input 
									type="text" 
									class="form-control card-input <%cardType%>" 
									card-type 
									ng-model="frm.cardNumber" 
									validator="required"
									value="<%frm.cardNumber%>"
									ui-mask="?9999-9999-9999-9999" 
									ng-change="checkCard()" 
									ng-model-options="{ 'debounce': 500 }">
								<input 
									type="hidden"
									name="credit_card[number]" 
									value="<%frm.cardNumber%>">
								<span class="help-block">{{ $errors->first('number') }}</span>
							</div> 
						</div>
					</div> 
			        <div class="form-group required-input expiration">
			            <label class="field-label">Expiration</label>
			            <div class="field-input">
			            	<div class="row row-flex">
			            		<div class="col-sm-4 divide">
									{{ Form::selectMonth('month', '', ['class' => 'form-control', 'name' => 'credit_card[expMonth]']) }}
									<span class="help-block">{{ $errors->first('expMonth') }}</span>

				            	</div>
				            	<div class="col-sm-4 divide">
			            			{{ Form::select('year', $years, 'name="credit_card[expYear]"', ['class' => 'form-control', 'name' => 'credit_card[expYear]'])}}
									<span class="help-block">{{ $errors->first('expYear') }}</span>
				            	</div>
							    <div class="col-sm-4">
							        <div class="form-group required-input">
							        	<label class="field-label cvv">CVV</label>
							        	<div class="field-input cvv-input">
							            	<input 
							            		name="credit_card[cvc]" 
							            		class="form-control" 
							            		ng-model="frm.cvv" 
							            		value="<%frm.cvv%>" 
							            		validator="required, number"
							            		maxlength="4" 
							            		type="text">
											<span class="help-block">{{ $errors->first('cvc') }}</span>
							        	</div>
							        </div> 
							    </div>
			            	</div>
			            </div>
			        </div>
			        <div class="text-center">
						<button 
							validation-submit="qbpay" 
							ng-click="save()"
							class="subscribe btn btn-sm btn-primary confirm-btn" 
							type="submit"> Proceed </button>
						<button onclick="parent.window ? parent.window.close() : window.close()" class="subscribe btn btn-sm btn-inverse confirm-btn" type="submit"> Cancel  </button>
			        </div>
				</form>
			</div>
		</article>
	</aside>
</div>
<script>
	$('form.qb-form').submit(function() {
		$('input, button, select').attr('readonly', 'readonly');
	});
	
</script>
@endsection
