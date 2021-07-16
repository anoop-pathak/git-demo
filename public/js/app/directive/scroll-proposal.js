(function() {
	'use strict';

	var dir = function($injector, $timeout) {
		return {
			restrict: 'A',
			link: function (scope, iElement, iAttrs) {
				
				var init = function() {

					$timeout(function() {
						// $(iElement).on('scroll', function(event) {
							
						// 	console.log( event.target.clientHeight, event.target.scrollHeight, event.target.scrollTop );

						// 	if( (parseInt(event.target.clientHeight) + 100) > ( parseInt(event.target.scrollHeight) - parseInt(event.target.scrollTop) ) ) {               
						// 		console.log('work');
			   //              }
						// });
					});
				};

				$injector.invoke(init);
			}
		};
	};

	dir.$inject = ['$injector', '$timeout'];

	/**
	* jobProgress Module
	*
	* Description
	*/
	angular
		.module('jobProgress')
		.directive('scrollProposalBtn', dir);
})();