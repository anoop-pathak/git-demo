(function() {
	'use strict';

	var service = function() {

		/**
		* @method [fire]
		* @desc fired Custom Events
		* @param [name STRING]
		* @param [data OBJECT|STRING|ARRAY|NUMBER|ANY]
		**/
		this.fire =  function(name, data) {


            var event =  new CustomEvent(name, {
                detail: data
            });

            document.dispatchEvent(event)
        };

         /**
		* @method listen
		* @desc listen fired Custom Events
		* @param [name STRING]
		* @param [func FUNCTION]
		**/
        this.listen = function(name, func) {


            document.addEventListener(name, function(e) {
            	func(e.detail, e);
            }, false);
        };


         /**
		* @method [destroy]
		* @desc destroy fired Custom Events
		* @param [name STRING]
		**/
        this.destroy = function(name) {


			if( !JP.haveValue(name) ) {
				throw 'Event name required!';
			}

            document.removeEventListener(name, {}, true);
        };
	};


	/**
	* jobProgress Module
	*
	* Description
	*/
	angular
		.module('jobProgress')
		.service('JPEvent', service);
})();

