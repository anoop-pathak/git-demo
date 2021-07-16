(function() {
	'use strict';

	var Dir  = function ($injector, $timeout, $modal, Customer, API_PREFIX, WEB_APP) {
		return {
			template: 	'<div class="btn-group new-design-dropdown finance-greensky">' +
							'<button type="button" class="btn btn-sm btn-success">' +
								'<img src="images/greensky.png" alt="GreenSky Icon">GreenSky Finance' +
							'</button>' +
							'<button type="button" class="btn btn-sm btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
								'<i class="fa fa-angle-down" aria-hidden="true"></i>' +
							'</button>' +
							'<ul class="dropdown-menu dropdown-menu-right">' +
								'<li><a ng-href="" ng-click="GS.connect()">Apply</a></li>' +
								'<li><a ng-href="" ng-click="GS.viewList()">History</a></li>' +
							'</ul>' +
						'</div>',
			replace: true,
			restrict: 'E',
			scope: {
				job: '=',
				project: '=?'
			},
			controller: ['$scope', '$element', '$attrs', function($scope, $element, $attrs) {
				
				var viewData = this;
					
				/**
				* 
				* @Default ( init )
				*/
				var init  = function() {
					
					viewData.job = $scope.job;
					viewData.customer = $scope.job.customer;

					console.log('viewData.job-----**************----------', $scope.projectId);
					console.log('viewData.customer---------***********-------', viewData.customer);
					/* set data */
					viewData.payload = {
                        applicant: {
                            firstName: viewData.customer.first_name,
                            lastname: viewData.customer.last_name,
                            email: viewData.customer.email,
                        },
                        merchant: {
                            callbackurl: WEB_APP
                        },
                        order: {
                            orderId: new Date().getTime(),
                            totalAmount: ''
                        }
                    };

                    /* add Listener to window */
                    addWindowListener();

                    /* amount */
                    if( JP.isObject(viewData.job.financial_details) ) {

                    	viewData.payload.order.totalAmount = viewData.job.financial_details.pending_payment;
                    }

                    // IF Project
                    if( JP.isObject($scope.project) && JP.haveValue($scope.project.id) ) {
						viewData.payload.order.totalAmount = $scope.project.amount;
					}

                    /* set phone */
                    if( JP.isObject(viewData.customer.phones) 
                    	&& JP.arrayHaveValues(viewData.customer.phones.data) ) {

                    	viewData.payload.applicant.phone = viewData.customer.phones.data[0].number;
                    }

                    /* set address of job */
                    if( JP.isObject(viewData.job.address) ) {

                    	viewData.payload.applicant.streetaddress = viewData.job.address.address;
                    	viewData.payload.applicant.city = viewData.job.address.city;
                    	viewData.payload.applicant.zip = viewData.job.address.zip;

                    	if( JP.isObject(viewData.job.address.state) ) {
                    		viewData.payload.applicant.state = viewData.job.address.state.code || '';
                    	}

                    	if( JP.isObject(viewData.job.address.country) ) {
                    		viewData.payload.applicant.country = viewData.job.address.country.name || '';
                    	}
                    }
				};

				/**
				 * [addWindowListener greensky Listeners]
				 */
				var addWindowListener = function() {

					/* call on offer success */
					window.addEventListener("greensky.offer", function(ev) { 

						var data = ev.detail.data;
                    	
                    	var res = {
                        	application_id: data.applicationId,
                        	status: data.applicationStatus,
                        	meta: JSON.stringify(data)
                        };

                        saveData(res);
                    });

					/* call when update state */
                    window.addEventListener("greensky.state", function(ev) { 

                    	console.log('viewData.activeState = true;', viewData.activeState, viewData.payload);

                    	var data = ev.detail.data;
                    	
                    	var res = {
                        	application_id: data.applicationId,
                        	status: data.applicationStatus,
                        	meta: JSON.stringify(data)
                        };

                        saveData(res);
                    });

                    /* if application success */
                    window.addEventListener("greensky.application.success", function(ev) {

                    	var data = ev.detail.data;
                    	
                    	var res = {
                        	application_id: data.applicationId,
                        	status: data.applicationStatus,
                        	meta: JSON.stringify(data)
                        };

                        saveData(res);
                    });

                    /* if declined */
     //                window.addEventListener("greensky.declined", function(ev) {

     //                    console.info("greensky.declined", ev.detail.data);
     //                });

					// /* call on close greensky popup */
					// window.addEventListener("greensky.closemodal", function(ev) { 

     //                    var data = ev.detail.data;

     //                    if( !JP.haveValue(data.applicationId) ) { return; }

     //                    var res = {
     //                    	application_id: data.applicationId,
     //                    	status: 'in progress'
     //                    }
     //                    console.info("GSKY modal was closed", data, ev);       
                        
     //                    if( JP.isTrue(data.declined) ) {
     //                    	res.status = 'declined';
     //                    }
                        
     //                    if( JP.isTrue(data.success) ) {
     //                        res.status = 'success';
     //                    }

     //                    // saveData(res);
     //                });
				};

				/**
				 * 
				 * @save data 
				 */
				var saveData = function(resData) {

					if( !JP.isTrue(viewData.activeState) ) { return; };
					
					var data = angular.copy(resData);

					data.job_id = viewData.job.id;
					data.customer_id = viewData.customer.id;

					var token = viewData.job.share_token;

					if( JP.isObject($scope.project) && JP.haveValue($scope.project.id) ) {
						data.job_id = $scope.project.id;
						token = $scope.project.share_token;
					}

					Customer.addJobToGreensky(data, token).then(function(success) {

						/* Sucess Message */
						// WindowMessageService.success(success.data.message);
					}, function(error) {

						/* Error Alert Message */
						// WindowMessageService.error(error.data.message || error.data.error.message);
					});
				};

				/**
				 * 
				 * @connect greensky
				 */
				viewData.connect = function() {

					viewData.activeState = true;
					
					gsc_Checkout(viewData.payload).then(function(success) {
                        viewData.activeState = false;
                    }, function(error) {
                        viewData.activeState = false;
                    });
				};

				/**
				 * 
				 * @list
				 */
				viewData.viewList = function() {

					var modalInstance = $modal.open({
		                templateUrl: API_PREFIX()+'/js/app/views/greensky-list.html',
		                controller: 'GreenSkyListCtrl as Modal',
		                backdrop: false,
		                keyboard: false, 
		                resolve: {
		                	JobData: function() {

		                		var job  = angular.copy(viewData.job);

		                		if( JP.isObject($scope.project) && JP.haveValue($scope.project.id) ) {
									job.id = $scope.project.id;
									job.share_token = $scope.project.share_token;
								}


		                		return job;
		                	}
		                }
		            }).result;
				};

				// /**
				// *
				// * @onchnage
				// */
				// var setJob = $scope.$watchCollection('job', function(newValue) {
				// 	init();
				// }, false);

				// // Unbind watch
				// $scope.$on('$destroy', function() {
				// 	setJob();
				// });

				/**
				*
				* @call on load
				*/
				$injector.invoke(init);
			}],
			controllerAs: 'GS'
		};
	};


	Dir.$inject = ['$injector', '$timeout', '$modal', 'Customer', 'API_PREFIX', 'WEB_APP'];

	/**
	* jobProgress Module
	*/
	angular
		.module('jobProgress')
		.directive('greenSky', Dir);
})();