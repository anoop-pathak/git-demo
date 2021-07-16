(function() {
	'use strict';

	/**
	*
	* @Proposal Model
	*/
	var proposalModel = function($http, $q, API_PREFIX){

		/**
		 * [_proposal @set]
		 */
		var _proposal = {};

		/**
		* @Request Transformer
		**/
		_proposal.transform = function(data) {
			return $.param(data);
		};

		/**
		*@get proposal status
		*/
		_proposal.getStatus = function(token){
			var $dfr = $q.defer();
			
			$http.get(API_PREFIX() + '/proposals/' + token + '/status'				
			).then(function(success){
				$dfr.resolve(success);
				// console.log(success);
			},function(error){
				$dfr.reject(error);
			});

			return $dfr.promise;
		};

		/**
		*@get proposal file
		*/
		_proposal.getFile = function(token){
			var $dfr = $q.defer();
			
			$http.get(API_PREFIX() + '/proposals/' + token + '/file'				
			).then(function(success){
				$dfr.resolve(success);
				// console.log(success);
			},function(error){
				$dfr.reject(error);
			});

			return $dfr.promise;
		};

		/**
		*@show
		*/
		_proposal.showProposal = function(token, query){
			var $dfr = $q.defer();

			if( angular.isUndefined(query) || !angular.isObject(query)) {
				query = {};
			}
			
			$http.get(
				API_PREFIX() + '/proposals/' + token + '/show',
				{
					params: query
				}
			).then(function(success){
				$dfr.resolve(success);
				// console.log(success);
			},function(error){
				$dfr.reject(error);
			});

			return $dfr.promise;
		};

		/**
		*@show
		*/
		_proposal.saveProposal = function(token, params){
			var $dfr = $q.defer();
			
			$http.post(API_PREFIX() + '/proposals/' + token,
			params,
			{
				headers: { 
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				transformRequest: _proposal.transform
			}).then(function(success){
				$dfr.resolve(success);
			},function(error){
				$dfr.reject(error);
			});

			return $dfr.promise;
		};

		/**
		*@show
		*/
		_proposal.rejectProposal = function(token, params){
			var $dfr = $q.defer();
			
			$http.post(API_PREFIX() + '/proposals/' + token,
			params,
			{
				headers: { 
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				transformRequest: _proposal.transform
			}).then(function(success){
				$dfr.resolve(success);
			},function(error){
				$dfr.reject(error);
			});

			return $dfr.promise;
		};

		_proposal.updateProposalElements = function(token, params){
			var $dfr = $q.defer();
			
			$http.put(API_PREFIX() + '/proposals/' + token + '/update_data_elements',
			params,
			{
				headers: { 
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				transformRequest: _proposal.transform
			}).then(function(success){
				$dfr.resolve(success);
			},function(error){
				$dfr.reject(error);
			});

			return $dfr.promise;
		};

		/**
		 * updateProposalComment
		 * 
		 */
		_proposal.updateProposalComment = function(token, params){
			var $dfr = $q.defer();

			$http.put(API_PREFIX() + '/proposals/' + token + '/comment',
			params,
			{
				headers: { 
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				transformRequest: _proposal.transform
			}).then(function(success){
				$dfr.resolve(success);
			},function(error){
				$dfr.reject(error);
			});

			return $dfr.promise;
		};


		return _proposal;
	};

	/**
	*
	* @inject dependencys
	*/
	proposalModel.$inject = ['$http', '$q', 'API_PREFIX'];

	/**
	* 
	*
	* @Proposal
	*/
	angular
		.module('jobProgress')
		.factory('Proposal', proposalModel);

})();