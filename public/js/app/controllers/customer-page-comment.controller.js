(function() {
	'use strict';

	/**
	*
	* @controller function
	*/
	var CustomerPageCommentController = function($scope, $timeout, $rootScope, $modalInstance, $injector, CommentType, JobToken, Customer) {

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

			viewData.frm = {};
			/**
			*
			* @set type
			*/
			viewData.pageType = CommentType;
			if( viewData.pageType == 'testimonial' ) {

				viewData.heading = 'Testimonial';
			} else {

				viewData.heading = 'Contact Us / Issues';
			}
						
			viewData.frm.type = viewData.pageType;
			viewData.frm.share_token = JobToken;
		};

		/**
		*
		* @close popup
		*/
		viewData.close = function() {

			/* close popup */
			$modalInstance.dismiss();
		};

		/**
		*
		* @show alert msg
		*/
		var alertMsg = function(type, msg){

			$rootScope.$broadcast('show:msg', {
				message: msg,
				type: type
			});
		};	

		/**
		*
		* @save 
		*/
		viewData.save = function() {

			Customer.saveText(viewData.frm).then(function(success) {

				$timeout(function() {
					var msg = success.data.message;
					alertMsg('success', msg);
					
		 	 		/* close popup */
		 	 		$modalInstance.close(true);
				}, null, false);

			}, function(error) {
				var msg = error.data.message || error.data.data.message;
				alertMsg('error', msg);
			});

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
	CustomerPageCommentController.$inject = ['$scope', '$timeout', '$rootScope', '$modalInstance', '$injector', 'CommentType', 'JobToken', 'Customer'];


	/**
	* Module
	*
	*/
	angular
		.module('jobProgress')
		.controller('CustomerPageCommentCtrl', CustomerPageCommentController)
})();