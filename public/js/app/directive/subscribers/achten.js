(function() {
	'use strict';

	var dir  = function (JPEvent, $timeout, $injector) {
		return {
			restrict: 'A',
			link: function (scope, iElement, iAttrs) {
				
				var self = this;
				var ACHTENS = JPAPP.getConfig().ARCHTEN;

				self.perMonthPercent = 0.0157;
				var init = function() {
					
					self.id = 691;
						
					/***
					* @set Company I
					**/
					$timeout(function() { bindForAchten();	}, 1500);
				};

				self.isAchtens = function() {
					return ( parseInt(scope.company.id) == ACHTENS.COMPANY_ID );
				};

				var bindForAchten = function() {

					console.info( 'Start' );

					// if not 
					if( !self.isAchtens() ) { return; }

					console.info( 'Enter' );

					$(iElement).addClass('achten-template');


					$.each( $(iElement).find('.dropzone-container'), function(i, dc) {
						var obj = JP.getObject($(dc).attr('template-ref'));

						console.info( 'Close' );

						if( JP.haveValue(obj.tId) ) {
							$(dc).find('input.public-input').attr('readonly', true);
							if( self.isRoofingOptionTemplate(obj.tId, false) ) {
							   	self.manageRoofingOptionTemplate(dc)
						    	return;
						    }

						    if( self.isAdditionalTemplate(obj.tId, false) ) {
							   	self.manageAdditionalOptionTemplate(dc)
						    	return;
						    }
						}
					}) 
				};

				/**
				*
				* @verify is Roofing Option Template
				*/
				self.isRoofingOptionTemplate = function(id, editMode, element) {

					if( !self.isAchtens() ) {
						return false;
					}

					if( JP.isTrue(editMode) ) {

						var ref = $(element).find('.dropzone-container').attr('template-ref');

						if( JP.haveValue(ref) ) {
							ref = angular.fromJson(ref);
							return self.isRoofingOptionTemplate(ref.tId);
						}
						
						return false;
					}


					if( ACHTENS.ROOFING_OPTION_TEMPLATES.indexOf(JP.int(id)) > -1) {
						return true;
					}

					return false;
				};

				/**
				*
				* @verify is Additional Option Template
				*/
				self.isAdditionalTemplate = function(id, editMode, element) {
					if( !self.isAchtens() ) {
						return false;
					}

					if( JP.isTrue(editMode) ) {

						var ref = $(element).find('.dropzone-container').attr('template-ref');

						if( JP.haveValue(ref) ) {
							ref = angular.fromJson(ref);
							return self.isAdditionalTemplate(ref.tId);
						}
						
						return false;
					}


					if( ACHTENS.ADDITIONAL_OPTION_TEMPLATES.indexOf(JP.int(id)) > -1) {
						return true;
					}

					return false;
				};


				/*****************************************************************************
											TEMPLATE 1
				******************************************************************************/
				var UpdateTotalPricingForAdditionalProduct = function(additionalElement, block) {

					var tCont, totalPrice = 0;
					if( !JP.haveValue(additionalElement) ) {

						var tCont  =  $(block).closest('#proposal-jp-create');

						$.each($(tCont).find('.dropzone-container'), function(i, tc) {
							var ref = $(tc).attr('template-ref');

							if( JP.haveValue(ref) ) {
								ref = angular.fromJson(ref);
								if( self.isRoofingOptionTemplate(ref.tId) ) {
									var active = $(tc).find('.sub-achten-container a.selected-marked'),
									sub = $(active).closest('.sub-achten-container');

									if( JP.haveValue($(sub).find('.sub-achten-container-total input').val()) ){
										var v = parseFloat(JP.replaceText($(sub).find('.sub-achten-container-total input').val(), true));
										
										totalPrice += v;
									}
								}
							}
						});

						console.log( 'asdasd' )

						additionalElement = $(tCont).find('.achten-additional-product-container').closest('.dropzone-container');
					}
					
					var proposalPrice = $(additionalElement).find('.achten-total-price-container-proposal input'),
						tax = parseFloat(JP.replaceText($(additionalElement).find('.achten-total-price-container-tax input').val(), true)),
						totalPriceEl = $(additionalElement).find('.achten-total-price-container-total input');

					$.each($(additionalElement).find('.sub-achten-additional-product-container'), function(i, sub) {

						if( $(sub).find('.click-select-btn-subscribers a').hasClass('selected-marked') 
							&& JP.haveValue($(sub).find('.achten-additional-product-container-price input').val())) {
							totalPrice += parseFloat(JP.replaceText( $(sub).find('.achten-additional-product-container-price input').val(), true))
						}


						var a =  $(sub).find('.click-select-btn-subscribers a');


						if(JP.haveValue($(a).attr('id'))) {
							
							JPEvent.fire('tags', {
								id: $(a).attr('id'),
								tag: 'a',
								val: $(a).text()
							});

							JPEvent.fire('a', {
								id: $(a).attr('id'),
								val: $(a).attr('class')
							})
						}


					});

					var tp = totalPrice;
					if( tax > 0 ) {
						tp += tax;
					}

					/**********************/
					var p = JP.numberWithCurrency(totalPrice, true)
					$(proposalPrice)
							.val(p)
							.attr('value', p)

					JPEvent.fire('input', {
						id: $(proposalPrice).attr('id'),
						val: p
					});

					/**********************/
					var pp  = JP.numberWithCurrency(tp, true)
					$(totalPriceEl)
							.val(pp)
							.attr('value', pp)

					JPEvent.fire('input', {
						id: $(totalPriceEl).attr('id'),
						val: pp
					});
					/**********************/

				};

				/**
				*
				* @manage events for Roofing Option Page
				*/
				var bindEventsForRoofingOptions = function(block, element) {

					$(block).on('click', '.click-select-btn-subscribers a', function() {

						var updateCurrentBtn = true;
						if( $(this).hasClass('selected-marked') ) {
							updateCurrentBtn = false;
						}

						$(block)
							.find('.click-select-btn-subscribers a.btn')
							.removeClass('selected-marked')
							.removeClass('btn-primary')
							.addClass('btn-default')
							.text('Click to Select');


						if( !JP.isTrue(updateCurrentBtn) ) { return; }

						/**
						*
						* @CURRENT MARK SELECTED
						*/
						$(this).text('Selected');
						$(this)
							.addClass('selected-marked')
							.addClass('btn-primary')
							.removeClass('btn-default');


						$.each( $(block).find('.click-select-btn-subscribers'), function() {
							var a =  $(this).find('a.btn');

							JPEvent.fire('tags', {
								id: $(a).attr('id'),
								tag: 'a',
								val: $(a).text()
							});


							JPEvent.fire('a', {
								id: $(a).attr('id'),
								val: $(a).attr('class')
							})
						});



						UpdateTotalPricingForAdditionalProduct(null, block); // ADDITIONAL()
					});
				};

				/**
				* @method [Add, Edit] Roofing Option template
				**/
				self.manageRoofingOptionTemplate = function(element, routeParams, editMode) {



					/**
					*
					* @get Template block to bind click Evenets
					*/
					var cont = $(element).find('.achten-container');

					/**
					*
					* @bind Events
					*/
					bindEventsForRoofingOptions(cont, element);
				};

				/*****************************************************************************
											TEMPLATE 2
				******************************************************************************/
				
				/**
				*
				* @manage events for Additional Options
				*/
				var bindEventsForAdditionalOptions = function(block, element) {

					var len = $(element).closest('#proposal-jp-create').find('.dropzone-container').length,
						param1 = element, 
						param2 = {};

					if( len > 1 ) {
						param1 = null;
						param2 = block;
					}

					$(block).on('click', '.click-select-btn-subscribers a', function() {
						

						if( $(this).hasClass('selected-marked') ) {
							$(this)
								.removeClass('selected-marked')
								.removeClass('btn-primary')
								.addClass('btn-default')
								.text('Click to Select');

							UpdateTotalPricingForAdditionalProduct(param1, param2);
							return;
						}


						/**
						*
						* @CURRENT MARK SELECTED
						*/
						$(this).text('Selected');
						$(this)
							.addClass('selected-marked')
							.addClass('btn-primary')
							.removeClass('btn-default');


						UpdateTotalPricingForAdditionalProduct(param1, param2); // ADDITIONAL()
					});
				};

				self.manageAdditionalOptionTemplate = function(element) {

					/**
					*
					* @get Template block to bind click Evenets
					*/
					var cont = $(element).find('.achten-additional-product-container');

					/**
					*
					* @bind Events
					*/
					bindEventsForAdditionalOptions(cont, element);
				};
				

				$injector.invoke(init);
			}
		};
	};

	dir.$inject = ['JPEvent', '$timeout', '$injector'];

	/**
	* JP Module
	*
	*/
	angular
		.module('jobProgress')
		.directive('achten', dir);
})();