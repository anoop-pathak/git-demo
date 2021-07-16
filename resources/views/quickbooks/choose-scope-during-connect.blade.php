<html>
	<head>
		@if(app()->environment() != 'local')
			{{ URL::forceSchema('https') }}
		@endif
		<script src="{{ asset('js/jquery-1.12.3.min.js') }}"></script>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" type="text/css" href="{{ asset('css/bootstrap.css') }}">
		<script src="{{ asset('js/bootstrap.js') }}"></script>
		<!------ Include the above in your HEAD tag ---------->
		<link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}">
		<link rel="stylesheet" href="{{ asset('qb-pay/css/style.css') }}">
		<style>
			.select-payment-mode {
                width: 100%;
                display: flex;
            }
            .select-payment-mode .select-card {
                background: #fbfcfc;
                cursor: pointer;
                padding: 15px;
                border: 2px solid #e0e5e6;
                margin-right: 10px;
                flex: 1;
                text-align: center;
                position: relative;
            }
            .select-payment-mode .select-card:hover,
            .select-payment-mode .select-card.active {
                background: #f2f7fc;
                border-color: #b8d3eb;
            }
            .select-payment-mode .select-card .qb-logo {
                margin-bottom: 0;
            }
			.select-payment-mode  .qb-logo svg {
				fill: #3276b1;
				width: 20px;
				position: absolute;
				right: 10px;
				top: 50%;
				transform: translateY(-50%);
				display: none;
			}
			.select-payment-mode .select-card.active .qb-logo svg {
				display: block;
			}
            @media(max-width: 580px) {
                .select-payment-mode {
                    display: block;
                }
                .select-payment-mode .select-card {
                    margin-bottom: 10px;
                }
            }
            .pay-head {
                margin-top: 0;
                margin-bottom: 20px;
            }
            .with-shadow {
                -webkit-box-shadow: 3px 3px 97px -36px rgba(204,204,204,0.68);
                   -moz-box-shadow: 3px 3px 97px -36px rgba(204,204,204,0.68);
                        box-shadow: 3px 3px 97px -36px rgba(204,204,204,0.68);
            }
            .submit-btn {
                margin-top: 30px;
            }
            .confirm-btn svg {
            	width: 13px;
			    fill: white;
			    vertical-align: middle;
			    margin-top: -3px;
			    margin-left: 3px;
            }
		</style>
	</head>
	<body class="p-5 qb-body" ng-app="jobProgress">
		<div class="row justify-content-md-center media-row-margin row-flex">
			<aside class="col-md-12 col-width-set">
				<article class="card">
					<div class="card-body with-shadow">
						<h3 class="pay-head">Select an Option</h3>
						<form class="qb-form">
							<div class="select-payment-mode">
								<div class="select-card" data-connection="online">
									<p class="qb-logo">
										<img src="{{ asset('qb-pay/img/qb-online.png') }}" width="150px" height="auto" alt="QuickBook Online">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"/></svg>
									</p>
								</div>
								<div class="select-card" data-connection="payments">
									<p class="qb-logo">
										<img src="{{ asset('qb-pay/img/qb-pay-online.png') }}" width="150px" height="auto" alt="QuickBook Online + Payments">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"/></svg>
									</p>
								</div>
							</div>
							<div class="text-center submit-btn">
								<button onclick="connectionPage()" class="subscribe btn btn-sm btn-primary confirm-btn" type="button"> Proceed <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M190.5 66.9l22.2-22.2c9.4-9.4 24.6-9.4 33.9 0L441 239c9.4 9.4 9.4 24.6 0 33.9L246.6 467.3c-9.4 9.4-24.6 9.4-33.9 0l-22.2-22.2c-9.5-9.5-9.3-25 .4-34.3L311.4 296H24c-13.3 0-24-10.7-24-24v-32c0-13.3 10.7-24 24-24h287.4L190.9 101.2c-9.8-9.3-10-24.8-.4-34.3z"/></svg></button>
						</form>
					</div>
				</article>
			</aside>
		</div>
		<script>

			$('.select-card').click(function() {
				$('.select-card').removeClass('active');
				$(this).addClass('active');
			});
			
			var Routes = {
				withoutPayments : "{{ route('quickbook.connection', ['access_token' => Request::input('access_token')]) }}",
				withPayments : "{!! route('quickbook.connection', ['with_payments_scope' => 'true', 'access_token' => Request::input('access_token')]) !!}"
			};

			function connectionPage() {
				scope = $('.select-card.active').data('connection');

				console.log(scope);

				console.log(Routes);

				if(scope == 'payments')
					window.location = Routes.withPayments;
				else
					window.location = Routes.withoutPayments;
			}
		</script>
	</body>
</html>