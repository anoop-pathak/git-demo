(function() {
	'use strict';

	/**
	*
	* @Proposal Model
	*/
	var customerModel = function($http, $q, API_PREFIX){

		/**
		 * [_proposal @set]
		 */
		var _customer = {};

		/**
		* @Request Transformer
		**/
		_customer.transform = function(data) {
			return $.param(data);
		};

		/**
		*@show
		*/
		_customer.saveText = function(params){
			var $dfr = $q.defer();
			
			$http.put(API_PREFIX() + '/customer_job_preview/feedback',
			params,
			{
				headers: { 
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				transformRequest: _customer.transform
			}).then(function(success){
				$dfr.resolve(success);
			},function(error){
				$dfr.reject(error);
			});

			return $dfr.promise;
		};

		/**
		*
		* @invoices
		*/
		_customer.getInvoicesList = function(token){
			var $dfr = $q.defer();

 			$http.get(API_PREFIX() + '/customer_job_preview/invoices/' + token)
			.then(function(success){
				$dfr.resolve(success);
			},function(error){
				$dfr.reject(error);
			});

 			return $dfr.promise;
		};

		/**
		 * @addJobToGreensky
		 */
		_customer.addJobToGreensky = function(params, token) {

 			var $dfr = $q.defer();

 			$http.post(API_PREFIX() + '/customer_job_preview/greensky/' + token,
			params,
			{
				headers: { 
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				transformRequest: _customer.transform
			}).then(function(success){
				$dfr.resolve(success);
			},function(error){
				$dfr.reject(error);
			});

 			return $dfr.promise;
		};

 		/**
		*
		* @get Financial Product Image
		*/
		_customer.getGreenskyList = function(param, token) {

 			var _defer = $q.defer();
			$http.get( API_PREFIX()+'/customer_job_preview/greensky/' + token, {
				params: JP.getObject(param),
				ignoreLoadingBar: true
			})
			.then(function(success) {
				_defer.resolve(success);
			}, function(error) {
				_defer.reject(error);
			});

 			return _defer.promise;
		};

		return _customer;
	};

	/**
	*
	* @inject dependencys
	*/
	customerModel.$inject = ['$http', '$q', 'API_PREFIX'];

	/**
	* 
	*
	* @Proposal
	*/
	angular
		.module('jobProgress')
		.factory('Customer', customerModel);

})();