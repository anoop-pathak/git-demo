/*
	@license Angular Snackbar version 1.0.1
	ⓒ 2015 AHN JAE-HA http://github.com/eu81273/angular.snackbar
	License: MIT

	<div class="snackbar-container" data-snackbar="true" data-snackbar-duration="3000" data-snackbar-remove-delay="200"></div>
*/

(function ( angular ) {
	"use strict";

	var module = angular.module('jobProgress');

	//snackbar directive
	module.directive( 'snackbar', ['$rootScope', '$compile', '$timeout', 'JPEvent',
		function($rootScope, $compile, $timeout, JPEvent) {

		return function ( scope, element, attrs ) {


			//snackbar container
			var snackbarContainer = angular.element(element);


			//receive broadcating
			JPEvent.listen('createSnackbar', function(received, event) {

				$(snackbarContainer).html('');

				//snackbar template
				var template = "<div class=\"snackbar snackbar-opened\"><span class=\"snackbar-content\">" + received.content|| '' + "</span></div>";

				//add snackbar
				if(!JP.haveValue(received.content)) { return; }

				$(snackbarContainer).append(template);

				var time = JP.haveValue(received.time) ? received.time: 3000;

				if( JP.isTrue(received.ignoreTimeout) ){ return; }
				
				//snackbar duration time
				$timeout(function () {

					//hide snackbar
					// snackbar.removeClass("snackbar-opened");

					//remove snackbar
					$(snackbarContainer).html('');

				}, time);

			});
		};
	}]);

})(angular);