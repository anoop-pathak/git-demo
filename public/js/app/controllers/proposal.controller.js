(function() {
	'use strict';

	//console.info('asd');

	/**
	*
	* @controller function
	*/
	var ProposalController = function($scope, $rootScope, $injector, $timeout, Proposal, $location, $modal, API_PREFIX, $aside, JPEvent, APP_URL) {

		/* view data */
		var viewData = this;

		/**
		*
		* @Default ( init )
		*/
		var init = function() {

			/**
			* @customerInput
			**/
			viewData.customerInput = false;

			/**
			*
			* @Edit Mode
			**/
			viewData.mode = "view";

			// $timeout(function() {
			// 		viewData.mode = "edit";
			// }, 1000);

			/**
			*
			* @page Token
			*/
			viewData.token = pageUrlToken;

			/**
			* @hide initial btn
			*/
			viewData.initialBtn = {'display': 'none'};
			viewData.currentPage = {};
			viewData.initialsCount = 0;

			/**
			*
			* @get proposal
			*/
			getProposal(viewData.token, function() {
				$timeout(function() {

					setPageHeight();
				}, 2000);

				var pageKey = (viewData.proposal.type == 'worksheet') ? 'template_pages' : 'pages';
				
				if( JP.isObject(viewData.proposal[pageKey]) 
					&& JP.arrayHaveValues(viewData.proposal[pageKey].data) 
					&& !haveOldInitials(viewData.proposal[pageKey].data) 
					&& !isOldProposal(viewData.proposal[pageKey].data) 
					&& getSignsCount(viewData.proposal[pageKey].data) >= getInitialsCounts(viewData.proposal[pageKey].data) 
					&& getInitialsCounts(viewData.proposal[pageKey].data) != 0 ) {

					viewData.isOldProposal = true;
				}

				customerRequiredInitial(viewData.proposal);
			});
		};

		var getInitialsCounts = function(pages) {

			var inis = [], c1 = 0, c2 = 0, c3= 0;

    		$.map(pages, function(page) {
    			var wrpr = angular.element('<div id="html" />').append(page.content);
				
				wrpr.find('input[filled-val="CUSTOMER_INITIAL"]').each(function() {

					if( !$(this).hasClass('customer-initial-2') 
						&& !$(this).hasClass('customer-initial-3') ) {

						c1 += 1
					}

				 	if( $(this).hasClass('customer-initial-2') ) {

				 		c2 += 1
				 	}

				 	if( $(this).hasClass('customer-initial-3') ) {

				 		c3 += 1
				 	}
				});
			});

    		if( c1 > 0 ) {
				inis.push('c1')
			}

			if( c2 > 0 ) {
				inis.push('c2')
			}

			if( c3 > 0 ) {
				inis.push('c3')
			}
			
			return inis.length;
		};

		var getSignsCount = function(pages) {

			var signs = [], c1 = 0, c2 = 0, c3= 0;

    		$.map(pages, function(page) {
    			var wrpr = angular.element('<div id="html" />').append(page.content);
				
				wrpr.find('.jp-signature').each(function() {

					if( $(this).hasClass('jp-signature-customer-cp') ) {

						c1 += 1
					}

				 	if( $(this).hasClass('jp-signature-customer-cp-2') ) {

				 		c2 += 1
				 	}

				 	if( $(this).hasClass('jp-signature-customer-cp-3') ) {

				 		c3 += 1
				 	}
				});
			});

    		if( c1 > 0 ) {
				signs.push('c1')
			}

			if( c2 > 0 ) {
				signs.push('c2')
			}

			if( c3 > 0 ) {
				signs.push('c3')
			}
			
			return signs.length;
		};

		/**
		*
		* @Hide Scroll
		*/
		var hideScroll = function(isHide) {

			if( isHide == true ) {

				$('html').css('overflow', 'hidden');
				return;
			}

			$('html').css('overflow', 'auto');
		};

		/**
		*
		* @return filters [HTMl] as [STRING]
		*/
		var changeHtml = function(html) {

			if( viewData.proposal.is_file == 0 ) {
				
				var wrpr = angular.element('<div id="html" />').append(html);
			
				if(  wrpr.find('div.jp-signature-customer-cp').length > 0  && JP.haveValue(viewData.sign) ) {
					
					// Div Attributes
					wrpr.find('div.jp-signature-customer-cp').each(function() {

						var date = new Date();

						if( $(this).find('img').attr('sign-origin') != 'viewer' ) {

							$(this).find('img').attr('src', viewData.sign);
							$(this).find('img').attr('sign-origin', 'viewer');
							$(this).find('.sign-date')
								.text((1+parseInt(date.getMonth()))+'/'+date.getDate()+'/'+date.getFullYear());
						}
					});
				}


				if( angular.isDefined(viewData.extraSign) 
					&& viewData.extraSign.length > 0){
					angular.forEach(viewData.extraSign, function(item) {
						
						var clss = '';

						//console.log(item);

						if( item.type == 'c2' ) {
							clss = 'div.jp-signature-customer-cp-2';
						}

						if( item.type == 'c3' ) {
							clss = 'div.jp-signature-customer-cp-3'
						}

						wrpr.find(clss).each(function() {

							var date = new Date();

							if( $(this).find('img').attr('sign-origin') != 'viewer' ) {

								$(this).find('img').attr('src', item.sign);
								$(this).find('img').attr('sign-origin', 'viewer');
								$(this).find('.sign-date')
									.text((1+parseInt(date.getMonth()))+'/'+date.getDate()+'/'+date.getFullYear());
							}
						});
					})					
				}

				var htmlAsString =  wrpr.html() || '';

				// htmlAsString =  htmlAsString.replace(/\s+/g, " ");
				htmlAsString =  htmlAsString.replace( /<!--[\s\S]*?-->/g, " ");

				return htmlAsString;
			}
		};

		/**
		*
		*@customerRequiredInitial
		**/
		var customerRequiredInitial = function(proposal) {

			var ele  = [];
			var customerInitial = false;
			var requiredCount = 0;

			var pageKey = (viewData.proposal.type == 'worksheet') ? 'template_pages' : 'pages';

			if( JP.isObject(proposal[pageKey]) 
				&& JP.arrayHaveValues(proposal[pageKey].data) ) {

				proposal[pageKey].data.map(function(page) {
					var wrpr = angular.element('<div id="html" />').append(page.content);
					wrpr.find('input[filled-val="CUSTOMER_INITIAL"]').each(function() {
						customerInitial = true;
						var value = $( this ).val();
						//console.log('value',value);
						if ($(this).hasClass('required') && !value) {
							 requiredCount ++;
							return;
						}
						if ($(this).hasClass('required') && value) {

							ele.push(value);
							return;
						}
						if (!$(this).hasClass('required')) {
							ele.push(this);
						}
					})

				});	
			}

			//console.log('pppppppp',ele,requiredCount);
			if (!customerInitial) {
				viewData.requiredCustomerInitial = false;
				return;
			}

			viewData.requiredCustomerInitial = (ele.length > 0 && requiredCount == 0) ? false : true;
			
			if( JP.isTrue(viewData.isOldProposal) ) {
				viewData.requiredCustomerInitial = false;
			}

			//console.log('viewData.requiredCustomerInitial',viewData.requiredCustomerInitial);
		};

		/**
		*
		* @sub section of @function [checkTemplateHaveInitialSign]
		*/
		var haveInitial = function(content) {
			var wrpr = angular.element('<div id="html" />').append(content);
			var ele  = [];

			wrpr.find('input[filled-val="CUSTOMER_INITIAL"]').each(function() {
				if( !JP.haveValue($(this).val()) ) {
					ele.push(this);
				}
			});

			viewData.initialsCount = viewData.initialsCount + ele.length;
			return (ele.length > 0 );
		};

		/**
		* @verify Customer Initail Signature 
		* @return [ARRAY]
		*/
		var checkTemplateHaveInitialSign = function() {
			var status = ['rejected']; //'accepted', 
			if( ['template', 'worksheet'].indexOf(viewData.proposal.type) == -1 ){ return false; }

			if( status.indexOf(viewData.proposal.status) > -1 ){ 
				return false;
			}
			
			var pageKey = (viewData.proposal.type == 'worksheet') ? 'template_pages' : 'pages';
			var l = [];
			
			if( JP.isObject(viewData.proposal[pageKey]) 
				&& JP.arrayHaveValues(viewData.proposal[pageKey].data) ) {

				l = $.map( angular.copy(viewData.proposal[pageKey].data), function(page) {
				 	if( haveInitial(page.content) ) {
				 		
				 		return page;
				 	};	
				});
			}

			return (l.length > 0);
		};

		var setPageHeight = function() {
			
			var ph = window.innerHeight - $('header').height();
			var sh = window.innerHeight - $('header').height();

			if( !JP.isMobile() ) {
				ph = ph - 15;
				ph = ph - $('.right-side-buttons').height();
			
				sh = sh - 25;
			} else {
				sh = sh - 35;
			}

			$('.proposal-page-scroll').attr('style','height:'+ ph + 'px !important;');
			$('.pdfobject-container').attr('style','height:'+ ph + 'px !important;');
			$('.sidebar-scroller').attr('style','max-height:'+ sh + 'px !important;');
		};

		/**
		*
		* @get
		*/
		var getProposal = function(token, callback) {

			Proposal.showProposal(token,{ 'includes[]': ['pages', 'template_pages', 'attachments', 'job'] }).then(function(success) {
				
				/* Proposal */
				viewData.proposal = success.data.data;

				console.log('viewData.proposal',viewData.proposal);

				/**
				* @verify Proposal have initial sign or not
				**/
				if( checkTemplateHaveInitialSign() &&  viewData.proposal.initial_signature == '0') {
					viewData.initialBtn = {
						'display': 'inline-block'
					};
				}
				
				if( JP.isFunction(callback) ) {
					callback();
				}
				
			}, function(error) {  });
		};	

		/**
		* @get pages only
		**/
		var getPages = function() {
			 var pages = $.map( angular.copy(viewData.proposal.pages.data) , function(page) {
			 	page.template = angular.copy(changeHtml(page.content));
			 	delete page.content;
			 	return page;
			 });

			 return pages;
		};

		/**
		* @get user Extra Sign
		*/
		var getExtraSigns = function() {
			if( angular.isUndefined(viewData.extraSign) 
				|| !angular.isArray(viewData.extraSign)) {
				return [];
			}

			if( viewData.extraSign.length == 0 ) {
				return [];	
			}

			return viewData.extraSign;
		};

		/**
		*
		*@setPagesSignure
		**/
		var setPagesSignure = function(pages, mainSign, multipleSignature) {
			return $.map(pages, function(page) {

    			var wrpr = angular.element('<div id="html" />').append(page.content);
    			if (JP.haveValue(mainSign)) {
			 		wrpr.find('.jp-signature-customer-cp').each(function() {
			 			var date = new Date();

						if( $(this).find('img').attr('sign-origin') != 'viewer' ) {

							$(this).find('img').attr('src', mainSign);
							$(this).find('img').attr('sign-origin', 'viewer');
							$(this).find('.sign-date')
								.text((1+parseInt(date.getMonth()))+'/'+date.getDate()+'/'+date.getFullYear());
						}
			 		});
    			}

			 	multipleSignature.map(function(val) {
			 		var clas = '';

			 		if (val.type == 'c2') {
			 			clas = '.jp-signature-customer-cp-2';
			 		}

			 		if (val.type == 'c3') {
			 			clas = '.jp-signature-customer-cp-3';
			 		}

			 		if( JP.haveValue(clas) ) {

			 			wrpr.find(clas).each(function() {
				 			var date = new Date();

							if( $(this).find('img').attr('sign-origin') != 'viewer' ) {

								$(this).find('img').attr('src', val.sign);
								$(this).find('img').attr('sign-origin', 'viewer');
								$(this).find('.sign-date')
									.text((1+parseInt(date.getMonth()))+'/'+date.getDate()+'/'+date.getFullYear());
							}
				 		});
			 		}
			 	});

			 	page.content = wrpr.html();
			 	return page;
			});
		};

		var saveProposalData = function(token, data) {

			Proposal.saveProposal(token, data).then(function(success) {

				location.reload();
				// viewData.proposal = success.data.data;
			}, function(error) {

				if ( JP.haveValue(data.status)
				 	&& data.status == 'accepted'
				 	&& !JP.isTrue(viewData.proposal.is_file)) {

					JPEvent.fire('createSnackbar', { content: '' });
				}


				viewData.errormMessage = error.data.message || error.data.error.message;


				$timeout(function() {	
					/**
					*
					* @hide error Message
					*/
					viewData.errormMessage = {};
				}, 4000)

			}).finally(function() {



				/**
				*
				* @disabled 
				*/
				viewData.disabed = false;
			});
		};
		/**
		*
		* @save proposal 
		*/
		var saveProposal = function(token, initial, isPendingInitial, checkInitial) {

			if( viewData.disabed === true ) { return; }

			// set With  Or without Status
			initial = (initial === true) ?  true : false;

			// if( JP.isTrue(isPendingInitial) 
			// 	&& JP.isTrue(checkInitial)) {
				
			// 	JPEvent.fire('createSnackbar', {
			// 		content: 'Initials pending.',
			// 		ignoreTimeout: true
			// 	});

			// 	$timeout(function() {
			// 		JPEvent.fire('createSnackbar', {content: ''});
			// 	}, 3000);
			// 	return;
			// }

			/**
			*
			* @disabled 
			*/
			viewData.disabed = true;
			
			var data = {
				signature: viewData.sign,
				comment : viewData.comment || '',
				pages:  getPages(), // viewData.template,
				//job_id: viewData.proposal.job_id,
				multiple_signatures: getExtraSigns(),
				thank_you_email: Customer.getThankYouEmail()
			};


			if (JP.isObject(viewData.proposal)
				&& JP.isObject(viewData.proposal.template_pages)
				&& JP.arrayHaveValues(viewData.proposal.template_pages.data)) {
				data.template_pages = setPagesSignure(viewData.proposal.template_pages.data, data.signature, data.multiple_signatures);
				data.template_pages.map(function(val) {

					val.auto_fill_content = 0;
				});
			}
			
			if( !JP.haveValue(data.signature) && JP.arrayHaveValues(getExtraSigns()) ) {
				data.signature = getExtraSigns()[0].sign;
			}

			if( initial == false) {
				data.status = 'accepted';
			}

			if( JP.isTrue(initial) || JP.isTrue(checkInitial)) {
				data.initial_signature = (isPendingInitial === true) ? 0 : 1;
			}


			if ( JP.haveValue(data.status)
			 	&& data.status == 'accepted'
			 	&& !JP.isTrue(viewData.proposal.is_file)) {

				JPEvent.fire('createSnackbar', {
					content: 'Processing your requesting...',
					ignoreTimeout: true
				});
				console.log('twsting...l');
				JPEvent.fire('ProposalAccepted', {
					signatured: true,
					reload: true,
					callback: function(updatedData) {
						
						if( JP.isObject(updatedData) 
							&& JP.isObject(updatedData.pages) 
							&& JP.arrayHaveValues(updatedData.pages.data) ) {

							var newPages = $.map( angular.copy(updatedData.pages.data) , function(page) {
							 	page.template = angular.copy(changeHtml(page.content));
							 	return page;
							});

							newPages.map(function(page) {
								data.pages.map(function(val) {
									if( val.id == page.id ) {
										val.template = page.template;
									}
								});
							});
						}

						saveProposalData(token, data);
					}
				});					
				return;
			}
			
			saveProposalData(token, data);
		};


		/**
		*
		* @save intials
		*/
		var saveInitialSign = function(img) {

			// Date
			var date = new Date();

			/**
			*
			* @set Initial Sign
			*/
			viewData.proposal.pages.data.map(function(page) {

				var wrpr = angular.element('<div id="html" />').append(page.content);

				/**
				*
				* @set Initial Signature
				*/
				wrpr
					.find('.jp-signature-customer-initials img').attr('src', img);

				/**
				*
				* @set Initial Signature Date
				*/
				wrpr
					.find('.jp-signature-customer-initials .sign-date')
					.text((1+parseInt(date.getMonth()))+'/'+date.getDate()+'/'+date.getFullYear());

				/**
				*
				* @set Input Text Value
				*/
				wrpr
					.find('input[filled-val="CUSTOMER_INITIAL"]')
					.val(Customer.getFirstletter())
					.attr('value', Customer.getFirstletter());

				page.content =  wrpr.html();

				return page;
			});

			// viewData.sign =  img;

			/**
			*
			* @save Proposal
			*/
			saveProposal(viewData.token, true);
		};

		/**
		*
		* @check initials
		*/
		var isAllInitialsSigned = function(proposal, callback) {

			if( ['template', 'worksheet'].indexOf(proposal.type) == -1 ){ 
				if( JP.isFunction(callback) ) { callback(false) };
				return false; 
			}

			var pageKey = (proposal.type == 'worksheet') ? 'template_pages' : 'pages';

			var inis = [];

			if( JP.isObject(proposal[pageKey]) 
				&& JP.arrayHaveValues(proposal[pageKey].data) ) {

				var p = $.map( angular.copy(proposal[pageKey].data), function(page) {
				 	
				 	var wrpr = angular.element('<div id="html" />').append(page.content);
					
					wrpr.find('input[filled-val="CUSTOMER_INITIAL"]').each(function() {
						var val = $(this).val();
						if( val == '' || val == null ) {
							inis.push(this);
						}
					});
				});
			}

			if( JP.isFunction(callback) ) {
				callback( (inis.length > 0) );
			}
		};

		var haveOldInitials = function(pages) {
			var old = 0;
			
			$.map(pages, function(page) {
    			var wrpr = angular.element('<div id="html" />').append(page.content);
			 	
			 	if( wrpr.find('input[filled-val="CUSTOMER_INITIAL"]').length  > 0 ) {
			 		
			 		wrpr.find('input[filled-val="CUSTOMER_INITIAL"]').each(function() {

			 			if( !JP.haveValue($(this).attr('id')) ) {
			 				old += 1;
			 			}
			 		});
			 	}
			});

			return (old > 0);
		};

		/**
		*
		* @Do Initial Signature
		*/
		viewData.doInitialSignature = function() {
			hideScroll(true);

			var modal = $modal.open({
	            templateUrl: API_PREFIX()+'/js/app/views/initials.html',
	            controller: 'ProposalInitialModalCtrl as Modal',
	            size: 'lg',
	            backdrop: false,
	            keyboard: false,
	            resolve: {
	            	Token: function() {
	            		return viewData.token;
	            	},
	            	ProposalData: function() {
	            		return angular.copy(viewData.proposal);
	            	},
	            	Signs: function() {
	            		return Customer.getFirstletter();
	            	},
	            	InitialsCount: function() {
	            		return viewData.initialsCount;
	            	},
	            	IsOldProposal: function() {
	            		var pageKey = (viewData.proposal.type == 'worksheet') ? 'template_pages' : 'pages';

	            		return haveOldInitials(viewData.proposal[pageKey].data);
	            	}
	            }
	        }).result;

	        modal.then(function(proposal) {
	            if(proposal) {
	            	
	            	if( JP.isTrue(proposal.reload) ) {

	            		location.reload();
	            		return;
	            	}

	            	if( viewData.proposal.type == 'worksheet' ) {

	            		proposal.template_pages = angular.copy(proposal.pages);
	            		delete proposal.pages;
	            	}

	            	angular.extend(viewData.proposal, proposal);
	            	
	            	isAllInitialsSigned(viewData.proposal, function(status) {

						saveProposal(viewData.token, true, status);
	            	});
	            }
	            hideScroll(false);
	        }, function(error) {
	        	
	        	$timeout(function() {
	        		console.log('working');
		        	$('input[filled-val="CUSTOMER_INITIAL"]')
						.css('background-color', 'transparent')
		        		.parent().removeClass('required-initial');
	        	});
	        	
	        	hideScroll(false);
	        });
		};

		/**
		*
		* @inital Sign
		*/
		// viewData.getInitialSignature = function() {
		// 	// //console.log('a', Customer.getFirstletter());

		// 	var tCtx = document.getElementById('textCanvas').getContext('2d');
			
		// 	tCtx.canvas.width = 350;
		// 	tCtx.canvas.height = 170;

		// 	tCtx.font="60px Arial";
		// 	tCtx.textAlign ="center";
		// 	tCtx.fillStyle = "#4C4C4C";
		// 	tCtx.textBaseline = "middle"; 
		// 	tCtx.fillText(Customer.getFirstletter(), 175, 85);

		// 	/**
		// 	*
		// 	* @set initial sign
		// 	*/
		// 	saveInitialSign(tCtx.canvas.toDataURL());
		// };

		var isOldProposal = function(pages) {
			var old = 0;
			
			$.map(pages, function(page) {
    			var wrpr = angular.element('<div id="html" />').append(page.content);
			 	
			 	if( wrpr.find('.jp-signature-customer-cp').length  > 0 ) {
			 		
			 		wrpr.find('.jp-signature-customer-cp').each(function() {

			 			if( !JP.haveValue($(this).find('img').attr('id')) ) {
			 				old += 1;
			 			}
			 		});
			 	}

			 	if( wrpr.find('.jp-signature-customer-cp-2').length  > 0 ) {
			 		
			 		wrpr.find('.jp-signature-customer-cp-2').each(function() {
			 			
			 			if( !JP.haveValue($(this).find('img').attr('id')) ) {
			 				old += 1;
			 			}
			 		});
			 	}

			 	if( wrpr.find('.jp-signature-customer-cp-3').length  > 0 ) {
			 		
			 		wrpr.find('.jp-signature-customer-cp-3').each(function() {

				 		if( !JP.haveValue($(this).find('img').attr('id')) ) {
			 				old += 1;
			 			}
			 		});
			 	}
			});

			return (old > 0);
		};

		var isNeedSign = function(ele) {

			if( ($(ele).find('img').attr('src')).indexOf(window.location.hostname) > -1  
 				&& JP.haveValue($(ele).find('img').attr('id')) ) {

 				return true;
 			}

			if( ($(ele).find('img').attr('src')).indexOf(window.location.hostname) == -1  
 				&& JP.haveValue($(ele).find('img').attr('id')) 
 				&& $(ele).find('img').attr('sign-origin') != 'viewer' ) {

 				return true;
 			} 			

 			return false;	
		};

		/**
		*
		*@getMultipleSignature
		**/
		var getMultipleSignature = function(pages) {
			console.log('pages----------->>>', pages);
			var multipleSignature = [], cs2 = 0, cs3 = 0, cs= 0;
			var isOld = isOldProposal(pages);
			var signIds = {
				c1: [],
				c2: [],
				c3: []
			};
    		$.map(pages, function(page) {
    			var wrpr = angular.element('<div id="html" />').append(page.content);
			 	
			 	if( wrpr.find('.jp-signature-customer-cp').length  > 0 ) {
			 		
			 		if( JP.isTrue(isOld) ) {
			 			cs += wrpr.find('.jp-signature-customer-cp').length;
			 		}

			 		if( !JP.isTrue(isOld) ) {
			 			
				 		wrpr.find('.jp-signature-customer-cp').each(function() {

				 			if( isNeedSign(this) ) {

				 				signIds.c1.push({
				 					id: $(this).find('img').attr('id'),
				 					date_id: $(this).find('.sign-date').attr('id')
				 				});
				 			}
				 		});

				 		cs += signIds.c1.length;
			 		}
			 	}

			 	if( wrpr.find('.jp-signature-customer-cp-2').length  > 0 ) {
			 		
			 		if( JP.isTrue(isOld) ) {
			 			cs2 += wrpr.find('.jp-signature-customer-cp-2').length;
			 		}

			 		if( !JP.isTrue(isOld) ) {

				 		wrpr.find('.jp-signature-customer-cp-2').each(function() {
				 			
					 		if( isNeedSign(this) ) {

				 				signIds.c2.push({
				 					id: $(this).find('img').attr('id'),
				 					date_id: $(this).find('.sign-date').attr('id')
				 				});
				 			}
				 		});

				 		cs2 += signIds.c2.length;
			 		}
			 	}

			 	if( wrpr.find('.jp-signature-customer-cp-3').length  > 0 ) {
			 		
			 		if( JP.isTrue(isOld) ) {
			 			cs3 += wrpr.find('.jp-signature-customer-cp-3').length;
			 		}

			 		if( !JP.isTrue(isOld) ) {

				 		wrpr.find('.jp-signature-customer-cp-3').each(function() {

					 		if( isNeedSign(this) ) {

				 				signIds.c3.push({
				 					id: $(this).find('img').attr('id'),
				 					date_id: $(this).find('.sign-date').attr('id')
				 				});
				 			}
				 		});

				 		cs3 += signIds.c3.length;
			 		}
			 	}
			});

    		if( cs > 0 ) {
				multipleSignature.push({
					'customer': {
						count: cs,
						main: true
					}
				});
			}

			if( cs2 > 0 ) {
				multipleSignature.push({
					'customer2':{
						count: cs2
					}
				});
			}

			if( cs3 > 0 ) {
				multipleSignature.push({
					'customer3':{
						count: cs3
					}
				})
			}
			
			return {
				signs: multipleSignature,
				signIds: signIds,
				isOld: isOld
			};
		};

		var updateComment = function(comment) {

			Proposal
				.updateProposalComment( angular.copy(viewData.token), { comment: comment }) 
				.then(function(res) {


				});
		};

		var newAccept = function() {

			hideScroll(true);

	        var modal = $modal.open({
	            templateUrl: API_PREFIX()+'/js/app/views/accept.html',
	            controller: 'NewAcceptCtrl as Modal',
	            size: 'lg',
	            backdrop: false,
	            keyboard: false,
	            resolve: {
	            	ProposalData: function() {
	            		return viewData.proposal;
	            	},
	            	Token: function() {
	            		return viewData.token;
	            	}
	            }
	        }).result;

	        modal.then(function(data) {
	            if( JP.isTrue(data.reload) ) {

            		location.reload();
            		return;
            	}
	            
	            angular.extend(viewData.proposal, data.proposal);
	            
	            /* signature */
	           	viewData.sign = data.main;
	           	viewData.comment = data.comment;
	           	viewData.extraSign = data.extraSign;

            	isAllInitialsSigned(viewData.proposal, function(status) {

					saveProposal(viewData.token, false, status);
            	});

	        }, function(error) {
	        	
	        	hideScroll(false);
	        });
		};

		/**
	    * @open popup for signature 
	    */
	    viewData.accept = function() {
	    	
	    	if( JP.isTrue(viewData.isOldProposal) ) {
	    		newAccept();
		    	return;
	    	}

	    	var multipleSignature = {
	    		signs: []
	    	};
	    	//console.log('viewData.proposalhgggyug',viewData.proposal);

	    	if (JP.isObject(viewData.proposal)
	    		&& JP.haveValue(viewData.proposal.worksheet_id)
	    		&& JP.isObject(viewData.proposal.template_pages)
    			&& JP.arrayHaveValues(viewData.proposal.template_pages.data)) {
	    		multipleSignature = getMultipleSignature(viewData.proposal.template_pages.data);
	    	}

	    	if( angular.isUndefined(viewData.proposal)
	    		|| !viewData.proposal.is_file 
	    		|| !JP.isTrue(viewData.proposal.is_file)) {
	    		
	    		// var cs2 = 0, cs3 = 0, cs= 0;
	    		if (angular.isDefined(viewData.proposal)
	    			&& angular.isDefined(viewData.proposal.pages)) {

	    			multipleSignature = getMultipleSignature(viewData.proposal.pages.data);
		   //  		$.map(viewData.proposal.pages.data, function(page) {
		   //  			var wrpr = angular.element('<div id="html" />').append(page.content);
					 	
					//  	if( wrpr.find('.jp-signature-customer-cp').length  > 0 ) {
					//  		cs += wrpr.find('.jp-signature-customer-cp').length;
					//  	}

					//  	if( wrpr.find('.jp-signature-customer-cp-2').length  > 0 ) {
					//  		cs2 += wrpr.find('.jp-signature-customer-cp-2').length;
					//  	}

					//  	if( wrpr.find('.jp-signature-customer-cp-3').length  > 0 ) {
					//  		cs3 += wrpr.find('.jp-signature-customer-cp-2').length;
					//  	}
					// });
	    		}

	   //  		if( cs > 0 ) {
				// 	multipleSignature.push({
				// 		'customer': {
				// 			count: cs,
				// 			main: true
				// 		}
				// 	});
				// }

				// if( cs2 > 0 ) {
				// 	multipleSignature.push({
				// 		'customer2':{
				// 			count: cs2
				// 		}
				// 	});
				// }

				// if( cs3 > 0 ) {
				// 	multipleSignature.push({
				// 		'customer3':{
				// 			count: cs3
				// 		}
				// 	})
				// }
	    	}
	    	
	    	hideScroll(true);

	        var modal = $modal.open({
	            templateUrl: API_PREFIX()+'/js/app/views/signature.html',
	            controller: 'CustomerSignatureCtrl as Sign',
	            size: 'md',
	            backdrop: false,
	            keyboard: false,
	            backdropClass: 'modal-open-signature', 
	            windowClass :'modal-open-signature',
	            resolve: {
	            	MultipleSignature: function() {
	            		return multipleSignature.signs;
	            	}, 
	            	IsOldProposal: function() {
	            		return multipleSignature.isOld;
	            	},
	            	ProposalData: function() {
	            		return viewData.proposal;
	            	}
	            }
	        }).result;

	        modal.then(function(data) {
	            
	            if(data) {
	            	
		           	if( multipleSignature.signs.length > 0 
		           		&& multipleSignature.signs.length != data.extraSign.length 
		           		&& !JP.isTrue(multipleSignature.isOld) ) {
		           	
		           		var signs = {};

		           		data.extraSign.map(function(val) {
		           			signs[val.type] = val.sign;
		           		});

		           		JPEvent.fire('ProposalAccepted', { 
		           			signatures: signs,
		           			signIds: multipleSignature.signIds,
							signatured: true,
							reload: true
						});
		           		updateComment(data.comment);
		           		return;
		           	}
		           	
		           	/* signature */
		           	viewData.sign = data.main;
		           	//console.log('data',data);
		           	viewData.comment = data.comment;
		           	viewData.extraSign = data.extraSign;


		           	// viewData.template = changeHtml(viewData.proposal.template);
		           	isAllInitialsSigned(viewData.proposal, function(status) {

		           	console.log('working...', status);
						saveProposal(viewData.token, false, status, true);
	            	});

		           	hideScroll(false);
	            }
	        }, function(error) {
	        	hideScroll(false);
	        });
	    };

	    /**
	    * @open popup for comment 
	    */
	    viewData.reject = function() {
	    	hideScroll(true);
	        var modal = $modal.open({
	            templateUrl: API_PREFIX()+'/js/app/views/comment.html',
	            controller: 'ProposalCommentCtrl as Comment',
	            size: 'md',
	            backdrop: false,
	            keyboard: false,
	            resolve: {
	            	Token: function() {
	            		return viewData.token;
	            	}
	            }
	        }).result;

	        modal.then(function(data) {
	            
	            if(data) {
	            	
	            	location.reload();
	            }
	            hideScroll(false);
	        }, function(error) {
	        	hideScroll(false);
	        });
	    };
	    	
	    viewData.editByCustomer = function(val) {

	    	if( JP.isTrue(val) ) {
				viewData.mode = "edit";	    		
	    		return;
	    	}

	    	viewData.mode = "view";
	    	return;
	    	// $aside
	    	// 	.open({
	    	// 		templateUrl: API_PREFIX()+'/js/app/views/public-page-update-by-customer.html',
		    //         controller: 'CustomerProposalUpdateCtrl as Modal',
		    //         backdrop: false,
		    //         keyboard: false,
		    //         placement: 'right',
		    //         resolve: {
		    //         	ProposalData: function() {
		    //         		return angular.copy(viewData.proposal);
		    //         	},
		    //         	Token: function() {
		    //         		return angular.copy(viewData.token);
		    //         	}
		    //         }
	    	// 	});
	    };

		/**
		*
		* $injector
		*/
		$injector.invoke( init );
	};

	/**
	*
	* @dependency
	*/
	ProposalController.$inject = ['$scope', '$rootScope', '$injector', '$timeout', 'Proposal', '$location', '$modal', 'API_PREFIX', '$aside', 'JPEvent', 'APP_URL'];

	/**
	* Module
	*
	*/
	angular
		.module('jobProgress')
		.controller('ProposalCtrl', ProposalController)
})();