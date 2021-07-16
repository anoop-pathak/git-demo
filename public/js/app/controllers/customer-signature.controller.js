(function() {
	'use strict';

	/**
	*
	* @controller function
	*/
	var CustomerSignatureController = function($scope, $timeout, $rootScope, $modalInstance, $injector, MultipleSignature, IsOldProposal, ProposalData) {

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
			viewData.heading = 'Customer Signature';

			viewData.signType = {};
			viewData.signType.mouse = false;
			viewData.signType.text = true;
			viewData.signOpt = {};
			viewData.showSign = JP.isTrue(IsOldProposal);
			viewData.selectedSigns = [];
			viewData.proposal = ProposalData;

			viewData.comment = angular.copy(viewData.proposal.comment);
			// Signatures
			viewData.signatures = angular.copy(MultipleSignature);
			// if there is no signature
			if( MultipleSignature.length == 0 ) {
				viewData.signatures.push({
					customer: { count: 1 , main: true}
				});
			}
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
				// 'Comic Sans MS',
				// 'Garamond',
				// 'Georgia',
				// 'Tahoma',
				// 'Trebuchet MS',
				// 'Verdana'
			];

			/* style */
			viewData.styleList = [
				'Bold',
				'Normal',
				'Italic'
			];
		};

		/**
		 * selectSign
		 * 
		 */
		viewData.selectSign = function(signFor) {

			if( viewData.selectedSigns.indexOf(signFor) > -1 ) {

				viewData.selectedSigns.splice(viewData.selectedSigns.indexOf(signFor), true);
				return;
			}

			viewData.selectedSigns.push(signFor);
		};

		/**
		 * [showSigns]
		 * 
		 */
		viewData.showSigns = function() {

			viewData.showSign = true;
		};

		/**
		*
		* @select type
		*/
		viewData.selectType = function(type) {

			switch(type) {
				case 'mouse':
					viewData.signType.mouse = true;
					viewData.signType.text = false;
				break;
				case 'text':
					viewData.signType.mouse = false;
					viewData.signType.text = true;
				break;
			}
		};

		viewData.updateImage = function(type, value) {
			$scope.$broadcast('Sign:UpdateFormat', {
				type: type,
				val: value
			});
		};

		/**
		*
		* @if valid sign
		*/
		var validSign = function() {

			var isEmpty = (viewData.signOpt.isValidImage() == false) ? true : false;
			
			return isEmpty;
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
		* @update Proposal
		*/
		viewData.accpetProposal = function() {

			var mainSign = null, extraSign = [], isValid = true;
			viewData.showSignError = false;

			angular.forEach(viewData.signatures, function(val, i) {
					
				if( angular.isUndefined(val.sign) || !angular.isObject(val.sign) ) {
					val.sign = {};
				}

				if( JP.isTrue(viewData.signType.mouse) ) {

					val.sign = val.customer.accept();
				}

				if( JP.isTrue(viewData.signType.text) 
					&& JP.isObject(val.customer) 
					&& JP.isObject(val.customer.typeOpt)) {

					val.sign.dataUrl = val.customer.typeOpt.getImage();
					val.sign.isEmpty = !val.customer.typeOpt.isValidImage();
				}

				if( angular.isDefined(val.customer) 
					&& angular.isDefined(val.customer.main)
					&& val.customer.main  === true 
					&& !JP.isTrue(val.sign.isEmpty) ) {
					
					mainSign = angular.copy(val.sign.dataUrl);
					extraSign.push({
						type: 'c1',
						sign: angular.copy(val.sign.dataUrl)
					})
				}

				if( angular.isDefined(val.customer2) 
					&& !JP.isTrue(val.sign.isEmpty) ) {
					
					extraSign.push({
						type: 'c2',
						sign: angular.copy(val.sign.dataUrl)
					})
				}

				if( angular.isDefined(val.customer3) 
					&& !JP.isTrue(val.sign.isEmpty) ) {
					
					extraSign.push({
						type: 'c3',
						sign: angular.copy(val.sign.dataUrl)
					})
				}

				if( val.sign.isEmpty == true ) {
					
					isValid = false;
				}

			});

			if( !extraSign.length && !JP.isTrue(IsOldProposal) ) {
				viewData.showSignError = true;
				return;
			}

			if( extraSign.length > 0 && !JP.isTrue(IsOldProposal) ) {
				isValid = true;
			}

			if( !JP.isTrue(isValid) && JP.isTrue(IsOldProposal) ) {
				return;
			}

			viewData.saving = true;

			// if( mainSign == null ) {
			// 	mainSign = extraSign[0].sign;
			// }

			$modalInstance.close({
				main: mainSign,
				extraSign: extraSign,
				comment: viewData.comment
			});
		};

		/**
		*
		* @save signature
		*/
		viewData.save = function() {

			if( viewData.signType.mouse == true ) {
				var signature = $scope.accept();
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
 	 		
 	 		/* close popup */
 	 		$modalInstance.close(signature);
		};
		
		viewData.getBtnText = function() {

			if( JP.isTrue(IsOldProposal) ) { return 'Save & Accept Proposal'; }

			var signs = [];

			angular.forEach(viewData.signatures, function(val, i) {
					
				var sign = {};

				if( JP.isTrue(viewData.signType.mouse) ) {
					sign = val.customer.accept();
				}

				if( JP.isTrue(viewData.signType.text) 
					&& JP.isObject(val.customer) 
					&& JP.isObject(val.customer.typeOpt)) {

					sign.dataUrl = val.customer.typeOpt.getImage();
					sign.isEmpty = !val.customer.typeOpt.isValidImage();
				}

				if( angular.isDefined(val.customer) 
					&& angular.isDefined(val.customer.main)
					&& val.customer.main  === true 
					&& !JP.isTrue(sign.isEmpty) ) {
					
					signs.push(sign.dataUrl);
				}

				if( angular.isDefined(val.customer2) 
					&& !JP.isTrue(sign.isEmpty) ) {
					
					signs.push(sign.dataUrl);
				}

				if( angular.isDefined(val.customer3) 
					&& !JP.isTrue(sign.isEmpty) ) {
					
					signs.push(sign.dataUrl);
				}

			});

			if( signs.length == viewData.signatures.length ) { 
				
				return 'Save & Accept Proposal'; 
			}

			return 'Save Proposal'; 
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
	CustomerSignatureController.$inject = ['$scope', '$timeout', '$rootScope', '$modalInstance', '$injector', 'MultipleSignature', 'IsOldProposal', 'ProposalData'];


	/**
	* Module
	*
	*/
	angular
		.module('jobProgress')
		.controller('CustomerSignatureCtrl', CustomerSignatureController)
})();