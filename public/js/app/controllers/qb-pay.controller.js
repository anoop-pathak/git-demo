(function() {
	'use strict';

	/**
	*
	* @controller function
	*/
	var Ctrl = function($scope, $injector, CreditCardType, $timeout) {

		/**
		*
		* @default ( init )
		*/
		var init = function() {
			$scope.frm = {
				cardNumber: '',
				cvv: '',
				name
			};

			$scope.cardType = '';
		};

		$scope.save = function() {

			// var t = angular.copy($scope.frm.cardNumber);
			
			// $('.card-input').val(t.replace('-', ''));

			$('#qb-pay').submit();
		};
		/**
		*
		* @check Card
		*/
		$scope.checkCard = function() {
			
			if( $scope.frm.cardNumber.length > 13 ) {
				
				var cardType = angular.lowercase( CreditCardType.getCardType($scope.frm.cardNumber) );
				
				$scope.cardType = cardType;
			} else {
				
				$scope.cardType = '';
			}
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
	Ctrl.$inject = ['$scope', '$injector', 'CreditCardType', '$timeout'];


	/**
	* Module
	*
	*/
	angular
		.module('jobProgress')
		.controller('QbPayCtrl', Ctrl)
})();