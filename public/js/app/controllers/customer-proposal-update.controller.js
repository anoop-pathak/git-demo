(function() {
	'use strict';

	var Ctrl = function($modalInstance, ProposalData, Token, API_PREFIX, $injector, $sce, Proposal) {

		var vm = this;

		var init = function() {

			// console.log(ProposalData);

			vm.proposal = ProposalData;

			vm.ref = {};
		};

		vm.dismiss = function(){
			$modalInstance.dismiss();
		};

		vm.changedValue = function(arr) {
			// console.log(arr);
		};

		vm.checkedData = function() {
			vm.ref.getList();
		};

		vm.saveProposal = function() {
			// console.log(vm.ref.getList()) ;


			// return;
			var list = vm.ref.getList();

			if( list.length == 0 ) { return; }


			if( JP.isTrue(vm.pleasewait) ) { return; }

			vm.pleasewait = true;

			Proposal
				.updateProposalElements(Token, { data_elements: vm.ref.getList() }) 
				.then(function(res) {
					// console.log(res);
					location.reload();
				}).finally(function() {
					vm.pleasewait = false;
				})

		};

		vm.getHtml = function(html) {
			return $sce.trustAsHtml(html);
		};

		$injector.invoke(init);
	};

	Ctrl.$inject = ['$modalInstance', 'ProposalData', 'Token', 'API_PREFIX', '$injector', '$sce', 'Proposal'];

	/**
	* jobProgress Module
	*
	* Description
	*/
	angular
		.module('jobProgress')
		.controller('CustomerProposalUpdateCtrl', Ctrl);
})();