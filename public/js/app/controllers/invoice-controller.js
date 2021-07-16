(function() {

 	'use strict';

 	/**
	*
	* @Controller function
	*/
	var Ctrl = function($injector, IsQuickBooksConnected, ShareToken, JobData, $modalInstance, Customer, PaymenUrl, $interval) {

 		var viewData = this, windowData = {};

 		/**
		*
		* @default init
		*/
		var init = function() {
			viewData.IsQBConnected = (IsQuickBooksConnected === 'connected');
			viewData.job = JobData;
			viewData.token = ShareToken;
			viewData.isAllSelected = false;
			viewData.selectedInvoice = [];

 			/* get list */
			getInvoiceList()
		};

 		/**
		*
		* @get invoice listing
		*/
		var getInvoiceList = function() {

 			Customer.getInvoicesList(viewData.token).then(function(success) {

 				viewData.invoices = success.data.data;

 				viewData.paidInvoices = viewData.invoices.filter(function(val) { return !JP.isTrue(val.open); });
			});
		};

 		/**
		*
		* @select all invoices
		*/
		viewData.selectAll = function(status) {

 			viewData.invoices.map(function(val){ 
				if( JP.isTrue(val.open) ) {

 					val.selected = status; 
				}
			});

 			viewData.selectInvoice(status);
		};

 		/**
		*
		* @select all invoices
		*/
		viewData.selectInvoice = function(status) {

 			if( !JP.isTrue(status) ) {
				viewData.isAllSelected = false;
			}

 			viewData.selectedInvoice = $.map(viewData.invoices,function(val){ 

 				if( JP.isTrue(val.selected) ) {
					return val.id;
				}
			});
		};

 		/**
		*
		* @check window
		*/
		var checkWindow = function() {
			if ( angular.isDefined(windowData.childWindow) && windowData.childWindow.closed) {

 				getInvoiceList();
				viewData.selectedInvoice = [];
				viewData.isAllSelected = false;

 				/* Clear Interval */
				$interval.cancel(windowData.childWindowIntervalID);
				delete windowData.childWindowIntervalID;
			}
		};

 		/**
		*
		* @pay 
		*/
		viewData.pay = function() {

 			if( JP.haveValue(windowData.childWindowIntervalID) ) {
				return;
			}

 			var url = PaymenUrl + '?invoices[]=' + viewData.selectedInvoice.join('&invoices[]=');
			var iframe = '<html>'+
							'<head><title>QuickBooks Payment</title> '+
								'<style>body, html {width: 100%; height: 100%; margin: 0; padding: 0}</style>'+
							'</head>'+
							'<body>'+
								'<iframe src="' + url + '" style="height:calc(100% - 4px);width:calc(100% - 4px)"></iframe>'+
							'</body>'+
						 '</html>';

 			windowData.childWindow = window.open("", 'QuickBooks Payment', "title=hello,width=600,height=500,toolbar=no,menubar=no,resizable=yes");
			windowData.childWindow.document.write(iframe);

 			windowData.childWindowIntervalID = $interval(function() {
				checkWindow();
			}, 100);	
		};

 		/**
		*
		* @close popup
		*/
		viewData.close = function() {

 			/* close popup */
			$modalInstance.close();
		};

 		/**
		*
		* @call on load
		*/
		$injector.invoke( init );
	};

 	/**
	*
	* @Dependency
	*/
	Ctrl.$inject = ['$injector', 'IsQuickBooksConnected', 'ShareToken', 'JobData', '$modalInstance', 'Customer', 'PaymenUrl', '$interval'];

 	/**
	* @Module (:jobProgress)
	* @Controller (:InvoiceListingCtrl)
	* @Use: show invoice listing with view and QB pay options
	*/
	angular
		.module('jobProgress')
		.controller('InvoiceListingCtrl', Ctrl)
})();