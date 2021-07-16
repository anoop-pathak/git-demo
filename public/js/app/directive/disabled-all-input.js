(function() {
	'use strict';

	var dir = function ($injector, $timeout) {
		return {
			restrict: 'A',
			link: function (scope, iElement, iAttrs) {
					
				var init = function() {


					$timeout(function(){
						$(iElement)
							.find('input')
							.attr('readonly', true);

						$(iElement)
							.find('textarea')
							.attr('readonly', true);

						$(iElement)
                            .find('input[type="checkbox"]')
                            .attr('disabled', true);
                            
						$(iElement)
							.find('*')
							.attr('contentEditable', false);
					}, 1000);
				};

				$injector.invoke(init);
			}
		};
	};

	dir.$inject = ['$injector', '$timeout'];

	/**
	* jobProgress Module
	*
	* @disabled all unwanted inputs
	*/
	angular
		.module('jobProgress')
		.directive('disabledAllInput', dir);

})();