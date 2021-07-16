(function() {
	'use strict';

	var dir = function ($injector) {
		return {
			restrict: 'E',
			template: ' <button class="btn btn-default slide-btn" ng-click="updateToggle()"><span class="slide-toggle-icon"></span></button>',
			link: function (scope, iElement, iAttrs) {
			
				scope.updateToggle = function() {

					if( $('.'+ iAttrs.elementClass).hasClass(iAttrs.classToBePass) ) {
						$('.'+ iAttrs.elementClass).removeClass(iAttrs.classToBePass);
						return;
					}

					$('.'+ iAttrs.elementClass).addClass(iAttrs.classToBePass);
				};
			}
		};
	};

	dir.$inject = ['$injector'];

	/**
	* jobProgress Module
	*
	* @disabled all unwanted inputs
	*/
	angular
		.module('jobProgress')
		.directive('sidebarToggle', dir);

})();