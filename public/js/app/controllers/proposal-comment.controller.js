(function() {
	'use strict';

	/**
	*
	* @controller function
	*/
	var ProposalCommentController = function($scope, $timeout, $rootScope, $modalInstance, $injector, Proposal, Token) {

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
			viewData.heading = 'Enter Your Comment';

			viewData.rejected = {};

			viewData.token = Token;
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
		* @save signature
		*/
		viewData.save = function() {

			var data = {
				status: 'rejected',
				comment: viewData.rejected.comment,
				thank_you_email: Customer.getThankYouEmail()
			};

			Proposal.rejectProposal(viewData.token,data).then(function(sucess) {

	 	 		/* close popup */
	 	 		$modalInstance.close(sucess);
			}, function(error) {});
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
	ProposalCommentController.$inject = ['$scope', '$timeout', '$rootScope', '$modalInstance', '$injector', 'Proposal', 'Token'];


	/**
	* Module
	*
	*/
	angular
		.module('jobProgress')
		.controller('ProposalCommentCtrl', ProposalCommentController)
})();