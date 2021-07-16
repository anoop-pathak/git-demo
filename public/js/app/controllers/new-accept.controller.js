(function() {
	'use strict';

	/**
	*
	* @controller function
	*/
	var Ctrl = function($timeout, $rootScope, $modalInstance, $injector, ProposalData, $sce, $modal, API_PREFIX, APP_URL, Proposal, Token) {

		/**
		*
		* @view data
		*/
		var viewData = this;

		/**
		*
		* @default ( init )
		*/
		var init = function() {

			viewData.proposal = angular.copy(ProposalData);
			viewData.comment = angular.copy(viewData.proposal.comment);
			viewData.currentInis = [];
			viewData.currentSigns = [];
			viewData.colors = {
				c1: '#D9EAD3',
				c2: '#FFF2CC',
				c3: '#CFE2F3'
			};

			viewData.currentIndex = -1;
			viewData.currentPage = {};
			viewData.selectedIni = '';
			viewData.initials = [];
			viewData.pages = [];
			viewData.signs = '';

			var pageKey = (viewData.proposal.type == 'worksheet') ? 'template_pages' : 'pages';

			if( JP.isObject(viewData.proposal[pageKey]) 
				&& JP.arrayHaveValues(viewData.proposal[pageKey].data) ) {
				viewData.pages = viewData.proposal[pageKey].data;
			}

			getInitialsCount(function() {

				if( viewData.currentInis.length == 1 ) {

					viewData.initials.map(function(val) {

						if( viewData.currentInis[0] == val.type ) {

							val.selected = true;
							viewData.selectedIni = val.type;
						}
					});
		        }

				viewData.selectTab(0);
			});
		};

		/**
		 * [getInitialsCount]
		 * 
		 */
		var getInitialsCount = function(callback) {

			var c1 = 0, c2 = 0, c3 = 0;
			var filled = { c1: 0, c2: 0, c3: 0 };

			viewData.pages.map(function(page) {

			 	page.count = { c1: 0, c2: 0, c3: 0 };

			 	var wrpr = angular.element('<div id="html" />').append(page.content);

				wrpr.find('input[filled-val="CUSTOMER_INITIAL"]').each(function() {

					$(this).parent().addClass('initials');

					if( $(this).hasClass('customer-initial-2') ) {

						if( JP.haveValue($(this).val()) ) { filled.c2 += 1; }

				 		c2 += 1;
				 		page.count.c2 += 1;
				 	}

				 	if( $(this).hasClass('customer-initial-3') ) {

				 		if( JP.haveValue($(this).val()) ) { filled.c3 += 1; }

				 		c3 += 1;
				 		page.count.c3 += 1;
				 	}

				 	if( !$(this).hasClass('customer-initial-2') 
				 		&& !$(this).hasClass('customer-initial-3') ) {

				 		if( JP.haveValue($(this).val()) ) { filled.c1 += 1; }

				 		c1 += 1;
				 		page.count.c1 += 1;
				 	}
				});

				wrpr.find('.jp-signature').each(function() {

					if( $(this).hasClass('jp-signature-customer-cp') ) {

						if( $(this).find('img').attr('sign-origin') == 'viewer' ) { filled.c1 += 1; }

				 		c1 += 1;
				 		page.count.c1 += 1;

				 		if( $(this).find('img').attr('sign-origin') != 'viewer' 
				 			&& viewData.currentSigns.indexOf('c1') == -1 ) {
				 			viewData.currentSigns.push('c1');
				 		}
				 	}

				 	if( $(this).hasClass('jp-signature-customer-cp-2') ) {

				 		if( $(this).find('img').attr('sign-origin') == 'viewer' ) { filled.c2 += 1; }

				 		c2 += 1;
				 		page.count.c2 += 1;

				 		if( $(this).find('img').attr('sign-origin') != 'viewer' 
				 			&& viewData.currentSigns.indexOf('c2') == -1 ) {
				 			viewData.currentSigns.push('c2');
				 		}
				 	}

				 	if( $(this).hasClass('jp-signature-customer-cp-3') ) {

				 		if( $(this).find('img').attr('sign-origin') == 'viewer' ) { filled.c3 += 1; }

				 		c3 += 1;
				 		page.count.c3 += 1;

				 		if( $(this).find('img').attr('sign-origin') != 'viewer' 
				 			&& viewData.currentSigns.indexOf('c3') == -1 ) {
				 			viewData.currentSigns.push('c3');
				 		}
				 	}
				});


			});

			if( c1 > 0 ) {

				viewData.initials.push({
					name: '1st Signature', 
					count:c1, 
					selected: false, 
					color: "#D9EAD3", 
					type: 'c1',
					allDone: (c1 == filled.c1)
				});

				if( c1 != filled.c1 ) {

					viewData.currentInis.push('c1');
				}
			}

			if( c2 > 0 ) {

				viewData.initials.push({
					name: '2nd Signature', 
					count:c2, 
					selected: false, 
					color: "#FFF2CC", 
					type: 'c2',
					allDone: (c2 == filled.c2)
				});

				if( c2 != filled.c2 ) {

					viewData.currentInis.push('c2');
				}
			}

			if( c3 > 0 ) {

				viewData.initials.push({
					name: '3rd Signature', 
					count:c3, 
					selected: false, 
					color: "#CFE2F3", 
					type: 'c3',
					allDone: (c3 == filled.c3)
				});

				if( c3 != filled.c3 ) {

					viewData.currentInis.push('c3');
				}
			}

			if( JP.isFunction(callback) ) { callback(); }
		};

		/**
		 * [checkIniFilled]
		 * 
		 */
		var checkIniFilled = function() {

			var filled = false;

			viewData.pages.map(function(page) {

			 	var wrpr = angular.element('<div id="html" />').append(page.content);

				wrpr.find('input[filled-val="CUSTOMER_INITIAL"]').each(function() {

					if( $(this).hasClass('customer-initial-2') 
						&& viewData.selectedIni == 'c2' 
						&& JP.haveValue($(this).val()) ) {

						filled = true;
				 	}

				 	if( $(this).hasClass('customer-initial-3') 
						&& viewData.selectedIni == 'c3' 
						&& JP.haveValue($(this).val()) ) {

						filled = true;
				 	}


				 	if( !$(this).hasClass('customer-initial-2') 
				 		&& !$(this).hasClass('customer-initial-3') 
				 		&& viewData.selectedIni == 'c1' 
						&& JP.haveValue($(this).val()) ) {

				 		filled = true;
				 	}
				});

				wrpr.find('.jp-signature').each(function() {

					if( $(this).hasClass('jp-signature-customer-cp') 
						&& $(this).attr("signed") == 'now' 
						&& viewData.selectedIni == 'c1' ) {

						filled = true;
				 	}

					if( $(this).hasClass('jp-signature-customer-cp-2') 
						&& $(this).attr("signed") == 'now' 
						&& viewData.selectedIni == 'c2' ) {

						filled = true;
				 	}

				 	if( $(this).hasClass('jp-signature-customer-cp-3') 
						&& $(this).attr("signed") == 'now' 
						&& viewData.selectedIni == 'c3' ) {

						filled = true;
				 	}
				});
			});

			return  filled;
		};

		/**
		 * [selectInitial]
		 * 
		 */
		viewData.selectInitial = function(ini, index, callback) {

			if( JP.isTrue(ini.selected) ) { return; }
			if( JP.isTrue(ini.allDone) ) { return; }

			checkInitialInPage(function() {

				var modal = $modal.open({
		            templateUrl: API_PREFIX()+'/js/app/views/ini-confirm.html',
		            // size: 'lg',
		            backdrop: false,
		            keyboard: false,
		            controller: ['InitialData', '$modalInstance', 'RemoveFilled',
		            function(InitialData, $modalInstance, RemoveFilled) {

		            	var vm = this;
		            	vm.ini = ini;
		            	vm.haveFilledVals = JP.isTrue(RemoveFilled);

		            	vm.close = function(status) {

							$modalInstance.close(status);
						};
		            }],
		            controllerAs: "Modal",
		            resolve: {
		            	InitialData: function() {
		            		return ini;
		            	}, 
		            	RemoveFilled: function() {
		            		return checkIniFilled();
		            	}
		            }
		        }).result;

		        modal.then(function(status) {

		            if( !JP.isTrue(status) ) { return; }

		            viewData.initials.map(function(val) {
		            	val.selected = false;
		            });

		            ini.selected = JP.isTrue(status);

		            if( !JP.isTrue(ini.selected) ) { return; }

		            viewData.selectedIni = ini.type;
		            viewData.signs = '';

		            viewData.selectTab(index ||0);

		            if( JP.isFunction(callback) ) { callback(); }
		        }, function(error) {

		        });
	        });
		};

		/**
		*
		* @check Initial In Page
		*/
		var checkInitialInPage = function(callback) {

			/* set page updated page content */
			angular.forEach(viewData.pages, function(page, index) {

				if( page.id == viewData.currentPage.id ) {
					viewData.pages[index].content = angular.element('div#template').html();
				}
			});

			if( JP.isFunction(callback) ) { callback(); }
		};	

		/**
		 * [setEvents (click)]
		 */
		var setEvents = function() {
			console.log('ttttt',$('.estimate-tab-content').find('input[filled-val="CUSTOMER_INITIAL"]').parent());

			/* remove events */
			$('.estimate-tab-content')
				.find('input[filled-val="CUSTOMER_INITIAL"]')
				.parent().unbind();
			$('.estimate-tab-content')
				.find('.jp-signature').unbind();

			/* add events */
			$('.estimate-tab-content')
				.find('input[filled-val="CUSTOMER_INITIAL"]')
				.parent().click(function() {

				if( JP.haveValue(viewData.selectedIni) ) { return; }

				var ini = '', ele = $(this).find('input');

				if( !ele.hasClass('customer-initial-2') 
					&& !ele.hasClass('customer-initial-3') ) {

					if ( viewData.currentInis.indexOf('c1') > -1 ) ini = 'c1';

					if ( viewData.currentInis.indexOf('c1') == -1 ) return;
			 	}

			 	if( ele.hasClass('customer-initial-2') ) {

			 		if ( viewData.currentInis.indexOf('c2') > -1 ) ini = 'c2';

					if ( viewData.currentInis.indexOf('c2') == -1 ) return;
			 	}

			 	if( ele.hasClass('customer-initial-3') ) {

			 		if ( viewData.currentInis.indexOf('c3') > -1 ) ini = 'c3';

					if ( viewData.currentInis.indexOf('c3') == -1 ) return;
			 	}

			 	if( JP.haveValue(ini) && ini != viewData.selectedIni ) {

			 		var i = viewData.initials.filter(function(val) { return (val.type == ini); });

					viewData.selectInitial(i[0], viewData.currentIndex);
			 	}
			});

			$('.estimate-tab-content').find('.jp-signature').click(function() {

				if( !JP.haveValue(viewData.selectedIni) ) { 

					var ini = '';

					if( $(this).hasClass('jp-signature-customer-cp') ) {

						if ( viewData.currentInis.indexOf('c1') > -1 ) ini = 'c1';

						if ( viewData.currentInis.indexOf('c1') == -1 ) return;
				 	}

				 	if( $(this).hasClass('jp-signature-customer-cp-2') ) {

				 		if ( viewData.currentInis.indexOf('c2') > -1 ) ini = 'c2';

						if ( viewData.currentInis.indexOf('c2') == -1 ) return;
				 	}

				 	if( $(this).hasClass('jp-signature-customer-cp-3') ) {

				 		if ( viewData.currentInis.indexOf('c3') > -1 ) ini = 'c3';

						if ( viewData.currentInis.indexOf('c3') == -1 ) return;
				 	}

				 	if( JP.haveValue(ini) && ini != viewData.selectedIni ) {

				 		var i = viewData.initials.filter(function(val) { return (val.type == ini); });

						viewData.selectInitial(i[0], viewData.currentIndex, function() {

							doSign();
						});

		            	return;
				 	}
				}

				if( $(this).hasClass('jp-signature-customer-cp') 
					&& viewData.selectedIni == 'c1' ) {

					doSign();
				}

				if( $(this).hasClass('jp-signature-customer-cp-2') 
					&& viewData.selectedIni == 'c2' ) {

					doSign();
				}

				if( $(this).hasClass('jp-signature-customer-cp-3') 
					&& viewData.selectedIni == 'c3' ) {

					doSign();
				}
			});
		};

		/**
		 * [setBlank initials]
		 * 
		 */
		var setBlank = function(ele) {

			// $(ele).css('background-color', 'transparent');
	 		$(ele).val('');
	 		$(ele).attr('value', '');
	 		// $(ele).parent().removeClass('required-initial');
		};

		/**
		 * [setRequiredIni]
		 * 
		 */
		var setRequiredIni = function(ele) {

			if( $(ele).hasClass('required') 
		 		&& !JP.haveValue($(ele).val()) ) {

		 		$(ele).parent().addClass('required-initial');
		 	}
		};

		/**
		 * [setIniColor]
		 */
		var setIniColor = function(ini) {

			if( $(ini).hasClass('customer-initial-2') ) {

		 		$(ini).css('background-color', '#FFF2CC');
		 	}

		 	if( $(ini).hasClass('customer-initial-3') ) {

		 		$(ini).css('background-color', '#CFE2F3');
		 	}

		 	if( !$(ini).hasClass('customer-initial-2') 
		 		&& !$(ini).hasClass('customer-initial-3') ) {

		 		$(ini).css('background-color', '#D9EAD3');
		 	}
		};

		/**
		 * [setInis ]
		 * 
		 */
		var setInis = function() {

			/* initial text box */
			$('input[filled-val="CUSTOMER_INITIAL"]').each(function() {

			 	/* check initial */
			 	$(this).removeAttr("readonly");
			 	$(this).parent().removeClass('not-selected-ini');

			 	if( !JP.haveValue($(this).val()) ) {
			 		$(this).parent().addClass('initials')
			 	}

			 	setIniColor(this);

			 	var dis = false;

			 	if( $(this).hasClass('customer-initial-2')
			 		&& viewData.currentInis.indexOf('c2') > -1 ) {

			 		setRequiredIni(this);

			 		if( viewData.selectedIni != 'c2' ) {
				 		setBlank(this);
				 		dis = true;
			 		}

			 	}

			 	if( $(this).hasClass('customer-initial-3') 
			 		&& viewData.currentInis.indexOf('c3') > -1 ) {

			 		setRequiredIni(this);

			 		if( viewData.selectedIni != 'c3' ) {
			 			setBlank(this);
			 			dis = true;
			 		}
			 	}

			 	if( !$(this).hasClass('customer-initial-2') 
			 		&& !$(this).hasClass('customer-initial-3') 
			 		&& viewData.currentInis.indexOf('c1') > -1 ) {

			 		setRequiredIni(this);


			 		if( viewData.selectedIni != 'c1' ) {
			 			setBlank(this);
			 			dis = true;
			 		}
			 	}

			 	if( JP.isTrue(dis) 
			 		|| JP.haveValue($(this).val()) ) {

		 			// $(this).attr("disabled", "disabled");
		 			$(this).attr('readonly', true);

		 			if( JP.haveValue(viewData.selectedIni) 
		 				&& !JP.haveValue($(this).val()) ) {
		 				$(this).parent().addClass('not-selected-ini');
		 			}
			 	}
			});

			/* update initials */
			$('input[filled-val="CUSTOMER_INITIAL"]').blur(function() {

			 	var val = $(this).val();
		 		$(this).val(val);	
		 		$(this).attr('value', val);
			});
		};

		/**
		 * [removeSign clear signature]
		 * 
		 */
		var removeSign = function(ele) {

			// $(ele).css('background-color', 'transparent');

			if( JP.haveValue(viewData.selectedIni) ) {

				$(ele).addClass('not-selected-sign');
			}

			if( $(ele).attr("signed") != 'now' ) { return; }
			$(ele).find('img').attr('src', APP_URL() + '/img/sign.png');
		};

		/**
		 * [set color]
		 * 
		 */
		var setSignColor = function(ele) {

			$(ele).removeClass('not-selected-sign');

			if( $(ele).hasClass('jp-signature-customer-cp') ) {

			 	$(ele).find('.sign-date').css('background-color', '#D9EAD3');

		 		if( viewData.selectedIni != 'c1' 
		 			&& viewData.currentInis.indexOf('c1') > -1 ) {

			 		removeSign(ele);
		 		}
		 	}

		 	if( $(ele).hasClass('jp-signature-customer-cp-2') ) {

			 	$(ele).find('.sign-date').css('background-color', '#FFF2CC');

		 		if( viewData.selectedIni != 'c2' 
		 			&& viewData.currentInis.indexOf('c2') > -1 ) {

			 		removeSign(ele);
		 		}
		 	}

		 	if( $(ele).hasClass('jp-signature-customer-cp-3') ) {

			 	$(ele).find('.sign-date').css('background-color', '#CFE2F3');

		 		if( viewData.selectedIni != 'c3' 
		 			&& viewData.currentInis.indexOf('c3') > -1 ) {

		 			removeSign(ele);
		 		}
		 	}
		};

		/**
		 * [signature element]
		 * 
		 */
		var setSigns = function() {

			$('.estimate-tab-content').find('.jp-signature').each(function() {

				setSignColor(this);
			});

			viewData.pages.map(function(page) {

				/* remove all attributes */
				var element = angular.element('<div id="html" />').append(page.content);

				setSignColor(this);

				page.content = element.html();
			});
		};

		/**
		*
		* @select Tab
		*/
		viewData.selectTab = function(index) {

			checkInitialInPage(function() {
				console.log('viewData.pages', viewData.pages);

				viewData.currentIndex = index;
				viewData.currentPage = angular.copy(viewData.pages[index]);
				// console.log('viewData.currentPage', viewData.currentPage.content, $sce.trustAsHtml(viewData.currentPage.content));
				viewData.currentPage.content = $sce.trustAsHtml(viewData.currentPage.content);
				$timeout(function() {

					/* disable all element */
					angular.element('div.editor-containr')
						.find('input, textarea')
						.attr("readonly", true);

					setEvents();

					// if( !JP.haveValue(viewData.selectedIni) ) { return; }

					setInis();

					setSigns();
				}, 300);
			});
		};

		/**
		 * [doSign]
		 * 
		 */
		var doSign = function() {

			var modal = $modal.open({
	            templateUrl: API_PREFIX()+'/js/app/views/new-sign.html',
	            controller: 'NewSignCtrl as Sign',
	            size: 'md',
	            backdrop: false,
	            keyboard: false,
	            backdropClass: 'modal-open-signature', 
	            windowClass :'modal-open-signature',
	            resolve: {
	            	ProposalData: function() {
	            		return viewData.proposal;
	            	}
	            }
	        }).result;

	        modal.then(function(data) {

	        	var signClasses = {
	        		c1: '.jp-signature-customer-cp',
	        		c2: '.jp-signature-customer-cp-2',
	        		c3: '.jp-signature-customer-cp-3'
	        	};

	        	viewData.pages.map(function(page) {

					/* remove all attributes */
					var element = angular.element('<div id="html" />').append(page.content);
					element.find(signClasses[viewData.selectedIni]).each(function() {

						$(this).find('img').attr("src", data.dataUrl);
						$(this).attr("signed", "now");
					});

					$('.estimate-tab-content').find(signClasses[viewData.selectedIni]).each(function() {

						$(this).find('img').attr("src", data.dataUrl);
						$(this).attr("signed", "now");
					});

					page.content = element.html();
				});

				viewData.signs = data.dataUrl;
				viewData.proposal.comment = data.comment;
	        });
		};

		/**
		 * [getInitials]
		 * 
		 */
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

		/**
		 * [getSignList]
		 * 
		 */
		var getSignList = function(ele) {
			var list = [];

			if( $(ele).attr("signed") == "now" ) {

				list.push({
					attribute: 'id' ,
					attribute_value: $(ele).find('img').attr('id'),
					value: viewData.signs,
					tag: 'img',
					update_attribute: 'src'
				});

				list.push({
					attribute: 'id' ,
					attribute_value: $(ele).find('img').attr('id'),
					value: 'viewer',
					tag: 'img',
					update_attribute: 'sign-origin'
				});

				var date = new Date();

				list.push({
					attribute: 'id' ,
					attribute_value: $(ele).find('.sign-date').attr('id'),
					value: (1+parseInt(date.getMonth()))+'/'+date.getDate()+'/'+date.getFullYear(),
					tag: 'div',
					// update_inner_html: true
				});
			}

			return list;
		};

		/**
		 * [getList]
		 * 
		 */
		var getList = function() {

			var list = [];

			viewData.pages.map(function(page) {

				/* remove all attributes */
				var element = angular.element('<div id="html" />').append(page.content);

				element.find('input[filled-val="CUSTOMER_INITIAL"]').each(function() {

				 	var val = $(this).val();
				 	console.log('val', $(this));

				 	if( $(this).hasClass('customer-initial-2') 
				 		&& viewData.selectedIni == 'c2' ) {

			 			list = list.concat(getInitials(this));
				 	}

				 	if( $(this).hasClass('customer-initial-3') 
				 		&& viewData.selectedIni == 'c3' ) {

				 		list = list.concat(getInitials(this));
				 	}

				 	if( !$(this).hasClass('customer-initial-2') 
				 		&& !$(this).hasClass('customer-initial-3') 
				 		&& viewData.selectedIni == 'c1' ) {

				 		list = list.concat(getInitials(this));
				 	}
				});

				element.find('.jp-signature').each(function() {

					if( $(this).hasClass('jp-signature-customer-cp') 
						&& viewData.selectedIni == 'c1' ) {

						list = list.concat(getSignList(this));
				 	}

				 	if( $(this).hasClass('jp-signature-customer-cp-2') 
						&& viewData.selectedIni == 'c2' ) {

				 		list = list.concat(getSignList(this));
				 	}

				 	if( $(this).hasClass('jp-signature-customer-cp-3') 
						&& viewData.selectedIni == 'c3' ) {

				 		list = list.concat(getSignList(this));
				 	}
				});
			});

			return list;
		};

		/**
		 * [accept]
		 * 
		 */
		var accept = function() {
			checkInitialInPage(function() {

				var pageKey = (viewData.proposal.type == 'worksheet') ? 'template_pages' : 'pages';

				if( JP.isObject(viewData.proposal[pageKey]) 
					&& JP.arrayHaveValues(viewData.proposal[pageKey].data) ) {

					viewData.pages.map(function(page) {

						/* remove all attributes */
						var element = angular.element('<div id="html" />').append(page.content);

						element.find('input[filled-val="CUSTOMER_INITIAL"]')
							.css('background-color', 'transparent')
							.parent().removeClass('required-initial')
							.removeClass('initials')
							.removeClass('not-selected-ini');

						element.find('input[filled-val="CUSTOMER_INITIAL"]').removeAttr('readonly');

						element.find('.jp-signature')
							.css('background-color', 'transparent')
							.removeAttr('signed')
							.removeClass('sign-done');

						element.find('.jp-signature')
							.find('.sign-date')
							.css('background-color', 'transparent');

						page.content = element.html();
					});

					viewData.proposal[pageKey].data = viewData.pages;
				}

				var extraSign = [];

				if( viewData.selectedIni != 'c1' ) {

					extraSign.push({
						type: viewData.selectedIni,
						sign: angular.copy(viewData.signs)
					});
				}

				var data = {
					comment: viewData.proposal.comment,
					proposal: viewData.proposal,
					extraSign: extraSign
				};

				if( viewData.selectedIni == 'c1' ) {
					data.main = angular.copy(viewData.signs);
				}

				$modalInstance.close(data);
			});
		};

		/**
		 * [checkReqInitial]
		 * 
		 */
		var checkReqInitial = function() {
			var list = [];

			viewData.pages.map(function(page) {

				/* remove all attributes */
				var element = angular.element('<div id="html" />').append(page.content);

				element.find('input[filled-val="CUSTOMER_INITIAL"]').each(function() {

				 	if( $(this).hasClass('customer-initial-2') 
				 		&& $(this).hasClass('required') 
				 		&& viewData.selectedIni == 'c2' 
				 		&& !JP.haveValue($(this).val()) ) {

			 			list.push(this);
				 	}

				 	if( $(this).hasClass('customer-initial-3') 
				 		&& $(this).hasClass('required') 
				 		&& viewData.selectedIni == 'c3' 
				 		&& !JP.haveValue($(this).val()) ) {

				 		list.push(this);
				 	}

				 	if( !$(this).hasClass('customer-initial-2') 
				 		&& !$(this).hasClass('customer-initial-3') 
				 		&& $(this).hasClass('required') 
				 		&& viewData.selectedIni == 'c1' 
				 		&& !JP.haveValue($(this).val()) ) {

				 		list.push(this);
				 	}
				});

			});

			return list.length;
		};

		var updateComment = function(comment) {

			Proposal
				.updateProposalComment( angular.copy(Token), { comment: comment }) 
				.then(function(res) {

					$modalInstance.close({reload: true});
				});
		};	

		var haveSigned = function() {

			var signed = false;

			viewData.pages.map(function(page) {

				/* remove all attributes */
				var element = angular.element('<div id="html" />').append(page.content);

				element.find('.jp-signature').each(function() {

					if( $(this).hasClass('jp-signature-customer-cp') 
						&& viewData.selectedIni == 'c1' 
						&& $(this).find('img').attr('sign-origin') == 'viewer' ) {

						signed = true;
				 	}

				 	if( $(this).hasClass('jp-signature-customer-cp-2') 
						&& viewData.selectedIni == 'c2' 
						&& $(this).find('img').attr('sign-origin') == 'viewer' ) {

				 		signed = true;
				 	}

				 	if( $(this).hasClass('jp-signature-customer-cp-3') 
						&& viewData.selectedIni == 'c3' 
						&& $(this).find('img').attr('sign-origin') == 'viewer' ) {

				 		signed = true;
				 	}
				});
			});

			return signed;
		};
		/**
		*
		* @save
		*/
		viewData.save = function() {

			checkInitialInPage(function() {

				$timeout(function() {
					viewData.signError = '';
				}, 5000);

				if( checkReqInitial() > 0 ) {
					viewData.signError = 'Please complete the required initials which are still pending.';
					return
				}

				if( !JP.haveValue(viewData.signs) 
					&& !haveSigned() ) {
					viewData.signError = 'Please complete signatures which are still pending.';
					return;
				}

				if( viewData.currentSigns.length == 1 
					&& viewData.currentSigns[0] == viewData.selectedIni ) {

					accept();
					return;
				}

				var list = getList();

				if( !JP.arrayHaveValues(list) ) { return; }

				Proposal
					.updateProposalElements( angular.copy(Token), { data_elements: list }) 
					.then(function(res) {

						updateComment(viewData.proposal.comment)
					}).finally(function() {
						viewData.pleasewait = false;
					});
			});
		};

		/**
		*
		* @close popup
		*/
		viewData.close = function() {

			$('input[filled-val="CUSTOMER_INITIAL"]')
				.css('background-color', 'transparent')
				.parent()
				.removeClass('required-initial')
				.removeClass('initials')
				.removeClass('not-selected-ini');

			/* close popup */
			$modalInstance.dismiss();
		};

		/**
		*
		*@$injector
		*/
		$injector.invoke(init);
	};

	/**
	*
	* @inject dependencys
	*/
	Ctrl.$inject = ['$timeout', '$rootScope', '$modalInstance', '$injector', 'ProposalData', '$sce', '$modal', 'API_PREFIX', 'APP_URL', 'Proposal', 'Token'];


	/**
	* Module
	*
	*/
	angular
		.module('jobProgress')
		.controller('NewAcceptCtrl', Ctrl)
})();