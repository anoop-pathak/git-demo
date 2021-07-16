(function() {
	'use strict';

	var dir = function ($injector, API_PREFIX, Proposal, $sce, JPEvent, $timeout, $window) {
		return {
			restrict: 'EA',
			replace: true,
			scope: {
				ProposalData: '=proposal',
				Token: '=token',
				parent:'=scope',
				company: '=cData'
			},
			templateUrl: API_PREFIX()+'/js/app/views/public-page-update-by-customer.html',

			controller: ['$scope', '$element',  function ($scope, $element) {

				var vm = this;

				var init = function() {
					console.log('company', $scope.company);
					vm.$parent = $scope.$parent.Ctrl;
					
					vm.updateCount = 0;

					// console.log($scope.parent.Ctrl);

					vm.proposal = angular.copy($scope.ProposalData);	
					vm.ref = {};
					vm.downloadFile = API_PREFIX()+'/proposals/'+$scope.Token+'/file';


					JPEvent.listen('ProposalAccepted', function(e) {
						console.log( e );
						vm.saveProposal(e);
					});

					$timeout(function() {

						if(JP.isTrue(vm.isAccepetedOrRejected())) {
							$($element).find('*').attr('contenteditable', false);
						}

						/**proposalScale**/
						proposalScale();
						
					}, 2500)
				};

				/**
				*
				*@proposalScale
				**/
				var proposalScale = function() {
  
					var el = $($element).find('.dropzone-container');
					var wrapper = $($element).find('.proposal-page-scroll');

					var isMobileIpadAgent = /Android|iPad|iPhone/i.test(window.navigator.userAgent);
					var isMobileAgent = /Android|iPhone/i.test(window.navigator.userAgent);

					var size = {
					    width: wrapper.width(),
					    height: wrapper.height()
					}

					if (JP.isTrue(isMobileIpadAgent)) {
						console.log(size.height, el[0].clientHeight, size.height, el[0].clientHeight)
						el.css({
						    transform: "scale(" + size.width / el[0].clientWidth + ")",
							transformOrigin: 'left top'
						});


						$timeout(function() {

							var h = parseInt(el[0].getBoundingClientRect().height);
							$($element).find('.proposal-page-scroll').attr('style', 'height:' + h + 'px !important;');
							
							$($element).find('.dropzone-container').each(function() {
								var he = parseInt($(this)[0].getBoundingClientRect().height);	
								$(this).closest('.html-container').attr('style', 'height:' + he + 'px !important;');
							});
						}, 5000);
						// if (JP.isTrue(isMobileAgent)) {

						// 	$($element).find('.fix-width-container').css({
						// 		position: 'absolute'
						// 	})
						// }
					}  

				};

				vm.dismiss = function(){

					if( vm.updateCount > 0 ) {
						location.reload();
						return;	
					}

					$scope.parent.Ctrl.editByCustomer(false);
				};
				
				vm.checkedData = function() {
					console.log(vm.ref.getList());
				};

				vm.isAccepetedOrRejected = function() {

					if( !JP.haveValue(vm.proposal.status) ) {
						return false;
					}

					if( vm.proposal.status == 'accepted' ) {
						return true;
					}

					if( vm.proposal.status == 'rejected' ) {
						return true;
					}

					return false;
				};

				vm.getData = function() {
					console.log( vm.ref.getList() );
				};

				var reloadPage = function(){
				
					location.reload();
				};

				var getAttrList = function(val, sign) {
					var list = [];

					list.push({
						attribute: 'id' ,
						attribute_value: val.id,
						value: sign,
						tag: 'img',
						update_attribute: 'src'
					});

					list.push({
						attribute: 'id' ,
						attribute_value: val.id,
						value: 'viewer',
						tag: 'img',
						update_attribute: 'sign-origin'
					});

					var date = new Date();

					list.push({
						attribute: 'id' ,
						attribute_value: val.date_id,
						value: (1+parseInt(date.getMonth()))+'/'+date.getDate()+'/'+date.getFullYear(),
						tag: 'div',
						// update_inner_html: true
					});

					return list;
				};

				var getSigns = function(obj) {

					var list = [];

					if( !JP.isObject(obj.signIds) ) { return list; }

					if( !JP.isObject(obj.signatures) ) { return list; }

					if( JP.arrayHaveValues(obj.signIds.c1) 
						&& JP.haveValue(obj.signatures.c1) ) {

						obj.signIds.c1.map(function(val) {

							list = list.concat(getAttrList(val, obj.signatures.c1));
						});
					}

					if( JP.arrayHaveValues(obj.signIds.c2) 
						&& JP.haveValue(obj.signatures.c2) ) {

						obj.signIds.c2.map(function(val) {

							list = list.concat(getAttrList(val, obj.signatures.c2));
						});
					}

					if( JP.arrayHaveValues(obj.signIds.c3) 
						&& JP.haveValue(obj.signatures.c3) ) {

						obj.signIds.c3.map(function(val) {

							list = list.concat(getAttrList(val, obj.signatures.c3));
						});
					}

					return list;
				};

				vm.saveProposal = function(obj) {
					// console.log(vm.ref.getList()) ;

					var ob = JP.isObject(obj) ? obj : {};
					var list = [];

					if( JP.isFunction(vm.ref.getList) ) {

						list = list.concat(vm.ref.getList());
					}
					
					var signs = getSigns(ob);
					list = list.concat(signs);
					
					if( JP.isFunction(obj.callback) && list.length == 0 ) {

						obj.callback({});
						return;
					}

					if( list.length == 0 && !JP.isTrue(ob.signatured)) { 
						JPEvent.fire( 'createSnackbar', {content: 'No changes to save.' } );
						return; 
					}

					if( JP.isTrue(vm.pleasewait) ) { return; }
					vm.pleasewait = true;

						if( list.length == 0 && JP.isTrue(ob.signatured)) { 
						reloadPage();
						return; 
					}

					if( JP.isTrue(ob.signatured)) { 
						JPEvent.fire( 'createSnackbar', {content: 'Sending your data..', ignoreTimeout: true} );
					}

					Proposal
						.updateProposalElements( angular.copy($scope.Token), { data_elements: list, 'includes[]': 'pages' }) 
						.then(function(res) {

							if( JP.isFunction(obj.callback) ) {
								obj.callback(res.data.data);
								return;
							}

							console.log( res );
							
							JPEvent.fire('ClearChanges');
							vm.updateCount += 1;

							var msg = res.data.message;

							if( signs.length > 0 ) {
								msg = 'Signatures updated.'
							}

							JPEvent.fire( 'createSnackbar', {content: msg} );

							if( JP.isTrue(obj.reload) ) {
								$timeout(function() { reloadPage();	}, 2000);
							}

						}).finally(function() {
							vm.pleasewait = false;	
							JPEvent.fire( 'createSnackbar', {content: ''} );
						});

				};

				vm.getHtml = function(html) {
					return $sce.trustAsHtml(html);
				};

				$injector.invoke(init);
				
			}],
			controllerAs: 'Dir',
			link: function (scope, iElement, iAttrs) { }
		};
	};

	dir.$inject = ['$injector', 'API_PREFIX', 'Proposal', '$sce', 'JPEvent', '$timeout', '$window'];

	/**
	* jobProgress Module
	*
	* Description
	*/
	angular
		.module('jobProgress')
		.directive('customerPageEdit', dir);
})();