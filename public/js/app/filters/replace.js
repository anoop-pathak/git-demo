(function() {
	'use strict';

	var fun = function() {

		return function (input, replaceData, replacewith,postfix) {

            // if Input data Undefined
            if( angular.isUndefined(input) || !angular.isString(input) ) {
                return input || '';
            }

            // if Input data Undefined
            if( angular.isUndefined(replaceData) || !angular.isString(replaceData)  ) {
                return input || '';
            }

            // if Input data Undefined
            if( angular.isUndefined(replacewith) || !angular.isString(replacewith) ) {
                return input || '';
            }

            return JP.haveValue(postfix) ? input.replaceAll(replaceData, replacewith)+postfix : input.replaceAll(replaceData, replacewith);
        };
	};
	
	fun.$inject = [];

	/**
	* Module
	*
	*/
	angular
		.module('jobProgress')
		.filter('replaceAll', fun)	
})();