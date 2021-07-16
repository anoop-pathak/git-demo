(function() {
	'use strict';

	/**
	*
	* @controller function
	*/
	var customerPageController = function($scope, $timeout, $rootScope, $modal, $injector, API_PREFIX) {

		/**
		*
		* @view data
		*/
		var viewData = this;

		/**
		*
		* @default ( init )
		*/
		var init = function() {

			/**
			*
			* @modal heading
			*/
			viewData.heading = '';

			viewData.jobToken = jobToken;

			viewData.job = job;
			viewData.jobToView = jobToView;

			$scope.message = {};

			$scope.proposalTooltip = '<div class="proposal-legends">' +
								'<div class="draft"> <span></span> Draft </div>' +
								'<div class="sent"> <span></span> Sent </div>' +
								'<div class="viewed"> <span></span> Viewed </div>' +
								'<div class="accepted"> <span></span> Accepted </div>' +
								'<div class="rejected"> <span></span> Rejected </div>' +
							'</div>';
							console.log('shareTokens', shareTokens);
		};

		/**
		*
		* @Set Calendar
		*/
		var setCalendar = function() {

			
		};

		/**
		*
		* open comment box
		*/
		viewData.comment = function(type) {

			var modal = $modal.open({
	            templateUrl: 'page-comment.html',
	            controller: 'CustomerPageCommentCtrl as Page',
	            size: 'md',
	            backdrop: false,
	            keyboard: false,
	            resolve: {
	            	CommentType: function() {
	            		return type;
	            	},
	            	JobToken: function() {
	            		return viewData.jobToken;
	            	}	
	            }
	        });
		};
		
		var clearView = function() {

			$timeout(function() {
				
				delete $scope.message.message; 
				delete $scope.message.type; 
				
				$scope.message = {};

				$scope.$apply();
			}, 3000, false);
		};

		var unbind = $rootScope.$on('show:msg', function(event, args) {

			$scope.message.message = args.message;
			$scope.message.type = args.type;

			clearView();
		});

		$scope.$on('$destroy', function() {
			unbind();
		});

		/**
		*
		* @invoices
		*/
		viewData.viewInvoices = function(j) {

 			var pageJob;

 			if( JP.haveValue(j) ) {
				pageJob = JP.isObject(j) ? j : JSON.parse(j);
			} else {
				pageJob = JP.isObject(currentJob) ? currentJob : JSON.parse(currentJob);
			}

 			var job =  pageJob;
			var token = '';

 			if( angular.isArray(shareTokens) ) {

 				shareTokens.map(function(val) {
					if( val.project == job.number ) { token = val.token; }
				});
			} else {

 				token = shareTokens;
			}

 			var result = $modal.open({
	            templateUrl: API_PREFIX()+'/js/app/views/invoice-list.html',
	            controller: 'InvoiceListingCtrl as Modal',
	            size: 'md',
	            backdrop: false,
	            keyboard: false,
	            resolve: {
	            	IsQuickBooksConnected: function() {
	            		return quickbooksConnected;
	            	},
	            	ShareToken: function() {
	            		return token;
	            	}, 
	            	JobData: function() {
	            		return job;
	            	}, 
	            	PaymenUrl: function() {
	            		return paymentPageRoute;
	            	}
	            }
	        }).result;

 			result.then(function() {
				window.location.reload();
			});

 			console.log('quickbooksConnected', quickbooksConnected, shareTokens);
		};

		/**
		*
		*@$injector
		*/
		$injector.invoke(init);
	};

	/**
	*
	* @inject dependencys
	*/
	customerPageController.$inject = ['$scope', '$timeout', '$rootScope', '$modal', '$injector', 'API_PREFIX'];


	/**
	* Module
	*
	*/
	angular
		.module('jobProgress')
		.controller('customerPageCtrl', customerPageController)
})();