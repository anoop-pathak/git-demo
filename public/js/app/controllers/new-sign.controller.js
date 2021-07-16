(function() {

	'use strict';

	/**
	 * [Ctrl controller function]
	 * 
	 */
	var Ctrl = function($timeout, $modalInstance, $injector, ProposalData, $scope) {

		var viewData = this;

		/**
		 * [init default]
		 * 
		 */
		var init = function() {

			viewData.proposal = ProposalData;
			viewData.comment = viewData.proposal.comment;

			viewData.signOpt = {};
			viewData.signType = { mouse: false, text: true };
			viewData.sign = {};
			viewData.fields = {
				font: 60,
				style: 'Bold',
				family: 'Satisfy'
			};

			/* fonts */
			viewData.familyList  = [
				'Satisfy',
				'Arial',
				'Sans Serif',
				'Serif'
			];

			/* style */
			viewData.styleList = [
				'Bold',
				'Normal', 
				'Italic'
			];
		};

		viewData.updateImage = function(type, value) {
			$scope.$broadcast('Sign:UpdateFormat', {
				type: type,
				val: value
			});
		};

		/**
		*
		* @select type
		*/
		viewData.selectType = function(type) {
			viewData.signError = false;

			viewData.signType = {};
			viewData.signType[type] = true;
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
		* @if valid sign
		*/
		var validSign = function() {

			var isEmpty = (viewData.signOpt.isValidImage() == false) ? true : false;

			return isEmpty;
		};

		viewData.signed = function() {

			if( viewData.signType.mouse == true ) {
				var signature = viewData.sign.accept();
			}

			if( viewData.signType.text == true ) {

				var signature = {};
				signature.isEmpty = validSign();
				signature.dataUrl = viewData.signOpt.getImage();
			}

			/* check if empty */
			if(signature.isEmpty  == true) {
 	 			viewData.signError = true;
 	 			return;
 	 		}

 	 		signature.comment = viewData.comment;

 	 		/* close popup */
 	 		$modalInstance.close(signature);
		};

		/**
		 * call onload
		 * 
		 */
		$injector.invoke(init);
	};

	/**
	 * [$inject dependency]
	 * 
	 */
	Ctrl.$inject = ['$timeout', '$modalInstance', '$injector', 'ProposalData', '$scope'];

	/**
	* Module
	*
	*/
	angular
		.module('jobProgress')
		.controller('NewSignCtrl', Ctrl)	
})();