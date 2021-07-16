(function() {
	'use strict';

	/**
	*
	* @controller function
	*/
	var Ctrl = function($scope, $modalInstance, $injector, Token, ProposalData, $sce, Signs, $timeout, InitialsCount, Proposal, IsOldProposal) {
		
		var viewData = this;

		/**
		*
		* @default ( init )
		*/
		var init = function() {
			
			/* proposal */
			viewData.proposal = angular.copy(ProposalData);
			
			if( viewData.proposal.type == 'worksheet' ) {
				viewData.proposal.pages = angular.copy(viewData.proposal.template_pages);
			}

			/* current page */
			viewData.currentPage = {};
			viewData.initialsCount = InitialsCount || 0;
			viewData.showSelection = false; 
			viewData.selectedSigns = [];
			viewData.selectionList = [];
			viewData.isOld = JP.isTrue(IsOldProposal);

			viewData.counts = {
				customer: 0,
				customer2: 0,
				customer3: 0	
			};

			/* current index */
			viewData.currentIndex = -1;

			$timeout(function() {

				setPages();

				/* select tab */
				// viewData.selectTab(0);
			});
		};	
		
		var setPages = function() {

			viewData.proposal.pages.data.map(function(page) {

				/* remove all attributes */
				var element = angular.element('<div id="html" />').append(page.content);

				/* remove color */
				element.find('input[filled-val="CUSTOMER_INITIAL"]').each(function() {
					var val = $(this).val();

					if( JP.haveValue(val) && $(this).attr('initial-origin') == 'viewer' ) {
						return;
				 	}

				 	if( $(this).hasClass('customer-initial-2') ) {
				 		viewData.counts.customer2 += 1;
				 		return;
				 	}

				 	if( $(this).hasClass('customer-initial-3') ) {
				 		viewData.counts.customer3 += 1;
				 		return;
				 	}

				 	viewData.counts.customer += 1;
				});

				page.content = element.html();
			});

			viewData.selectTab(0);
		};	

		/**
		*
		* @check Initial In Page
		*/
		var checkInitialInPage = function(callback) {

			/* set page updated page content */
			angular.forEach(viewData.proposal.pages.data, function(page, index) {
				if( page.id == viewData.currentPage.id ) {

					viewData.proposal.pages.data[index].content = angular.element('div#template').html();
				}
			});

			if( angular.isDefined(callback) && angular.isFunction(callback) ) {

				callback();
			}
		};	

		viewData.showSigns = function() {
			viewData.showSelection = false;
			viewData.selectTab(0);
		};

		viewData.selectSign = function(name) {

			if( viewData.selectedSigns.indexOf(name) > -1 ) {

				viewData.selectedSigns.splice(viewData.selectedSigns.indexOf(name) , true);
				return;
			}

			viewData.selectedSigns.push(name);
		};

		/**
		*
		* @select Tab
		*/
		viewData.selectTab = function(index) {

			checkInitialInPage(function() {

				viewData.currentIndex = index;
				viewData.currentPage = angular.copy(viewData.proposal.pages.data[index]);
				viewData.currentPage.content = $sce.trustAsHtml(viewData.currentPage.content);
				$timeout(function() {

					/* disable all element */
					angular.element('div.editor-containr')
						.find('input, textarea')
						.attr("disabled", "disabled");
					
					/* initial text box */
					$('input[filled-val="CUSTOMER_INITIAL"]').each(function() {
					 	
					 	var val = $(this).val();
					 	// console.log('vallllllllllllllll',$(this));
					 	/* check initial */
					 	$(this).removeAttr("disabled");

					 	// if( JP.haveValue(val) && $(this).attr('initial-origin') == 'viewer') {
							// return;
					 	// }

					 	// if( !JP.haveValue(val) 
					 	// 	|| (JP.haveValue(val) && $(this).attr('initial-origin') == 'viewer') 
					 	// 	|| JP.isTrue(IsOldProposal) ) {
					 	// 	$(this).removeAttr("disabled");
					 	// }
					 	
					 	if ($(this).hasClass('required')) {

					 		$(this).parent().addClass('required-initial');
					 		// $(this).css('background-color', '#ffcccc');
					 	}

					 	if( $(this).hasClass('customer-initial-2') 
					 		&& !JP.isTrue(IsOldProposal) ) {

					 		$(this).css('background-color', '#FFF2CC');
				 			return;
					 	}

					 	if( $(this).hasClass('customer-initial-3') 
					 		&& !JP.isTrue(IsOldProposal) ) {

					 		$(this).css('background-color', '#CFE2F3');
					 		return;
					 	}

					 	if( (!$(this).hasClass('customer-initial-2') 
					 		&& !$(this).hasClass('customer-initial-3')) 
					 		|| JP.isTrue(IsOldProposal) ) {

					 		$(this).css('background-color', '#D9EAD3');
					 		return;
					 	}

				 		$(this).removeAttr("disabled");
					});

					/* update initials */
					$('input[filled-val="CUSTOMER_INITIAL"]').blur(function() {
					 	
					 	var val = $(this).val();
				 		$(this).val(val);	
				 		$(this).attr('value', val);
					});

				}, 300);
			});
		};

		/**
		*
		* @close popup
		*/
		viewData.close = function() {

			/* save & close */
			$modalInstance.dismiss();
		};

		var getInitials = function(ele) {
			var list = [];

			list.push({
				attribute: 'id' ,
				attribute_value: $(ele).attr('id'),
				value: $(ele).val(),
				tag: 'input',
				update_attribute: 'value'
			});

			list.push({
				attribute: 'id' ,
				attribute_value: $(ele).attr('id'),
				value: 'viewer',
				tag: 'input',
				update_attribute: 'initial-origin'
			});

			return list;
		};

		var getList = function() {

			var list = [];

			viewData.proposal.pages.data.map(function(page) {

				/* remove all attributes */
				var element = angular.element('<div id="html" />').append(page.content);
				
				element.find('input[filled-val="CUSTOMER_INITIAL"]').each(function() {
					 	
				 	var val = $(this).val();
				 	console.log('val', $(this));

				 	// if( !JP.haveValue(val) 
				 	// 	|| $(this).attr('initial-origin') == 'viewer' ) {
						// return;
				 	// }
				 	
				 	if( $(this).hasClass('customer-initial-2') ) {
			 			
			 			list = list.concat(getInitials(this));
				 	}

				 	if( $(this).hasClass('customer-initial-3') ) {
				 		
				 		list = list.concat(getInitials(this));
				 	}

				 	if( !$(this).hasClass('customer-initial-2') 
				 		&& !$(this).hasClass('customer-initial-3') ) {
				 		
				 		list = list.concat(getInitials(this));
				 	}
				});
			});

			return list;
		};

		/**
		*
		* @save signature
		*/
		var saveInitials = function() {

			checkInitialInPage(function() {

				var list = getList();

				if( !JP.arrayHaveValues(list) ) { return; }

				Proposal
					.updateProposalElements( angular.copy(Token), { data_elements: list }) 
					.then(function(res) {

						$modalInstance.close({reload: true});
					}).finally(function() {
						vm.pleasewait = false;
					});
			});
		};

		/**
		*
		* @save signature
		*/
		viewData.save = function() {

			if( !JP.isTrue(IsOldProposal) ) {
				saveInitials();
				return;
			}

			checkInitialInPage(function() {

				viewData.proposal.pages.data.map(function(page) {

					/* remove all attributes */
					var element = angular.element('<div id="html" />').append(page.content);
					element
						.find('input, textarea')
						.removeAttr("disabled")
						.removeAttr("new-initial");

					/* remove color */
					element
						.find('input[filled-val="CUSTOMER_INITIAL"]')
						.css('background-color', 'transparent')
						.parent().removeClass('required-initial');

					page.content = element.html();
				});

				$timeout(function() {

					/* save & close */
					$modalInstance.close(viewData.proposal);
				}, 200);
			});
		};
		
		/**
		*
		* @call on load
		*/
		$injector.invoke( init );
	};

	/**
	*
	* @Dependency
	*/
	Ctrl.$inject = ['$scope', '$modalInstance', '$injector', 'Token', 'ProposalData', '$sce', 'Signs', '$timeout', 'InitialsCount', 'Proposal', 'IsOldProposal'];

	/**
	* Module ( :jobProgress )
	*
	* Controller ( :ProposalInitialCtrl )
	*/
	angular
		.module('jobProgress')
		.controller('ProposalInitialModalCtrl', Ctrl);
})();