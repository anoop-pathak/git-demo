(function() {
	'use strict';

	var dir = function ($injector, $timeout, $http, $filter, JPEvent) {
		return {
			restrict: 'A',
			link: function (scope, iElement, iAttrs) {

				var self = this;
				var BLAIR_REMOD = JPAPP.getConfig().AFFORDABLE_ROOFING;

		        /**
		        * @method [init] Setup the data
		        *
		        */
		        var init = function() {

		        	self.list = [];


					
					/***
					* @set Company I
					**/
					$timeout(function() { bindForBlair();	}, 1500);
				};

				
				/**
				*
				* @check template Exist then set events accordinggly
				*/
				var bindForBlair = function() {

					if( !self.isBlair() ) { return; }

					getBlairList();

					$.each( $(iElement).find('.dropzone-container'), function(i, dc) {
						var obj = JP.getObject($(dc).attr('template-ref'));

						if( JP.haveValue(obj.tId) && self.isTemplate2(obj.tId, false)) {
							$(dc).find('input.public-input').attr('readonly', true);
							self.addTemplate2($(dc).parent(), {}, true);
						}

						if( JP.haveValue(obj.tId) && self.isDropoDownTemplate(obj.tId, false)) {
							$(dc).find('input.public-input').attr('readonly', true);
							self.addTemplate2($(dc).parent(), {}, true);
						}
					}) 
				};	


				var addCurrency  = function(val, withCurrency) { return JP.numberWithCurrency(val, withCurrency);	};


				/**
				* @selected product data
				**/
				var UpdatePropoductTitle = function(sub) {

					var label = $(sub).find('.blair-product-name-container label.label .item').text();
					var price = parseFloat(JP.replaceText($(sub).find('.blair-product-price input').val()));

					if( !JP.isValidVal(price) ) {
						price = 0;
					}

					var parent = $(sub).closest('#proposal-jp-create'),
						price = addCurrency(price, true);

					$(parent)
						.find('.blair-product-template-product-price input')
						.attr('value', price)
						.val(price);

					$(parent)
						.find('.blair-product-template-product-name input')
						.attr('value', label)
						.val(label);



					JPEvent.fire('input', {
						id: $(parent).find('.blair-product-template-product-price input').attr('id'),
						val: price
					});

					JPEvent.fire('input', {
						id: $(parent).find('.blair-product-template-product-name input').attr('id'),
						val: label
					});
				};

				/**
				* @updaste sales
				***/
				var UpdateSalesPrice = function(ele) {

					var parent = $(ele).closest('#proposal-jp-create'),
						sp = $(parent).find('.blair-total-sales-price input'),
						deposit = $(parent).find('.blair-total-sales-deposit input'),
						balance = $(parent).find('.blair-total-sales-balance input'),
						promotions = $(parent).find('.blair-total-sales-promotion input'),
						total  = 0,
						depVal = 0,
						promo  = 0;
						depVal = 0;

					$.each( $(parent).find('.dropzone-container'), function(i, dc) {

						var ref = JP.getObject( $(dc).attr('template-ref') );

						if( self.isDropoDownTemplate(ref.tId) ) {
							if( JP.haveValue(JP.replaceText($(dc).find('.blair-dropdown-options-total-price input').val(), true)) ) {
								var price = parseFloat(JP.replaceText($(dc).find('.blair-dropdown-options-total-price input').val()));

								if( JP.isValidVal(price) ) {
									total += price
								}
							}

							if( JP.haveValue(JP.replaceText($(dc).find('.blair-additional-total-price input').val(), true)) ) {
								var price = parseFloat(JP.replaceText($(dc).find('.blair-additional-total-price input').val()));

								if( JP.isValidVal(price) ) {
									total += price
								}
							}

							if( JP.haveValue(JP.replaceText($(dc).find('.blair-product-template-product-price input').val(), true)) ) {
								var price = parseFloat(JP.replaceText($(dc).find('.blair-product-template-product-price input').val()));

								if( JP.isValidVal(price) ) {
									total += price
								}

							}

							if( promotions.length  > 0 ) {
								$.each( $(dc).find('.arithmetic-table-proposal table tfoot tr'), function(i, tr) {
									$.each( $(tr).find('td'), function(ti, td) {
										var ref = JP.getObject( $(td).attr('ref-obj') );

										if( JP.haveValue(ref.operation) && ref.operation == "sum" ) {

											var t = parseFloat(JP.replaceText( JP.getTableCellText(td) )) || 0;
											total += (JP.isValidVal(t) ? t : 0);
										}
									})
								});
							}
						}

						if( self.isTemplate2(ref.tId) ) {
							var selectedMarked = $(dc).find('.blair-product-container .selected-marked');


							if( selectedMarked.length > 0 ) {
								var sub = $(selectedMarked).closest('.sub-blair-product-container');
							
								var price  = parseFloat(JP.replaceText($(sub).find('.blair-product-deposit input').val())) || 0;

								if( promotions.length  > 0 ) {
									var p = parseFloat(JP.replaceText($(sub).find('.blair-product-promo input').val())) || 0;
									var t = parseFloat(JP.replaceText($(sub).find('.blair-product-price input').val())) || 0;

									promo = JP.isValidVal(p) ? addCurrency(p, true) : 0;
									total += (JP.isValidVal(t) ? t : 0);
								}
								
								price =  JP.isValidVal(price) ? addCurrency(price, true) : 0;

								

								/*** PROMOTIONS *******/
								if( promotions.length  > 0 ) {
									$(promotions)
										.attr('value', promo)
										.val(promo);

									JPEvent.fire('input', {
										id: $(promotions).attr('id'),
										val: promo
									});
								}
								/*** PROMOTIONS *******/
								/*** DEPOSIT PRICE ****/
								$(deposit)
									.attr('value', price)
									.val(price);

								JPEvent.fire('input', {
									id: $(deposit).attr('id'),
									val: price
								});
								/*** DEPOSIT PRICE ****/
							}

						}

					});

					if( JP.haveValue(JP.replaceText($(deposit).val(), true)) ) {
						var dep = parseFloat(JP.replaceText($(deposit).val()));

						if( JP.isValidVal(dep)) {
							depVal =  dep;
						}

						if( JP.haveValue(promo)) {
							depVal +=  parseFloat(JP.replaceText(promo.toString()));
						}
					}


					var tp = addCurrency(total, true),
						bal = addCurrency(total-depVal, true);

					$(sp)
						.attr('value', tp)
						.val(tp);


					$(balance)
						.attr('value', bal)
						.val(bal);


					JPEvent.fire('input', {
						id: $(sp).attr('id'),
						val: tp
					});

					JPEvent.fire('input', {
						id: $(balance).attr('id'),
						val: bal
					});

				};

				/**
				*
				* @get Blait List
				*/
				var getBlairList = function() {

					if( self.list.length > 0 ){ return; }
					// 
					$http.get(JP.getAPPUrl()+'/scripts/services/subscribers/blair/data.json', {
						ignoreLoadingBar: true
					}).then(function(res) {
						self.jsonData = res.data || {};
						self.list = angular.copy(self.jsonData.Products);
					});
				};


				var getBlairListForSection = function(dataFor, element) {
					var refList  = $(element).find('label.label').attr('ref-list');

					if( JP.haveValue(refList) ) {
						return angular.fromJson(refList);
					}

					return $.map(self.list, function(section) {
						if( section.name == dataFor ) {
							return section;
						}
					});
				};

				var getBlairDropdownOptionsDiffTotalPrice = function(element) {

					var dropzoneContainer = $(element).closest('.dropzone-container'),
						sqr =  (parseFloat(JP.replaceText($(dropzoneContainer).find('.blair-product-template-product-price-qty input').val()))),
						price = 0, 
						fixedPrice = 0;

					sqr  = JP.isValidVal(sqr) ? sqr : 1;


					$.each($(dropzoneContainer).find('.blair-dropdown-options'), function(i, section) {
							
						var arr  = JP.getObject( $(section).find('label.label').attr('ref-list') ),
							obj = { products: [] };

						if( JP.isArray(arr) && arr.length > 0) {
							obj = angular.copy(arr.pop());
						}


						$.each(obj.products, function(pi, prod) {
							if( JP.isTrue(prod.checked) ) {

								var opreation = '+';
								if( JP.haveValue(prod.opreation) ) {
									opreation = prod.opreation;
								}

								var p = parseFloat(JP.replaceText(prod.price)),
									fP = parseFloat(JP.replaceText(prod.fixedPrice) || 0);

								if( JP.haveValue(prod.multiple) && prod.multiple == 'qty' && JP.haveValue(sqr)) {
									p  = sqr*p;
									fP  = sqr*fP;
								}

								if( JP.isValidVal(p) ) {
									price 		= (opreation == '+') ? (price+p)		: (price-p);
								}

								if( JP.isValidVal(fP) ) {
									fixedPrice  = (opreation == '+') ? (fixedPrice+(fP||0))  : (fixedPrice-(fP||0));
								}
							}
						});

					});

					return (price - fixedPrice);

				};


				var UpdateTemplate2Pricing = function(input, notUpdateNextPagePricing) {

					// console.log('work', );

					var sub = $(input).closest('.sub-blair-product-container');

					var rsp = JP.replaceText($(sub).find('.blair-product-price input').val()),
						promo = JP.replaceText($(sub).find('.blair-product-promo input').val()), 
						deposit = JP.replaceText($(sub).find('.blair-product-deposit input').val()), 
						optionsFrompage2 = JP.replaceText($(sub).find('.blair-product-price-options-from-page2 input').val()), 
						totalInput = $(sub).find('.blair-product-total input');


					rsp = (JP.isValidVal( parseFloat(JP.replaceText(rsp, true)) ) ) ?  parseFloat(rsp): 0;
					promo = (JP.isValidVal( parseFloat(JP.replaceText(promo, true)) ) ) ?  parseFloat(promo): 0;
					deposit = (JP.isValidVal( parseFloat(JP.replaceText(deposit, true)) ) ) ?  parseFloat(deposit): 0;
					optionsFrompage2 = (JP.isValidVal( parseFloat(JP.replaceText(optionsFrompage2, true)) ) ) ?  parseFloat(optionsFrompage2): 0;

					var totalInputVal = (rsp+optionsFrompage2)-(promo+deposit);

					/**
					* @set Total Value
					**/
					JPEvent.fire('input', {
						id: $(totalInput).attr('id'),
						val: addCurrency(totalInputVal, true)
					});
					

					$(totalInput)
						.attr('value', addCurrency(totalInputVal, true) )
						.val(  addCurrency(totalInputVal, true) );

					/**
					*
					* @set Calculation for Product Plans
					*/
					$.each($(sub).find('.blair-product-finance'), function(i, section) {
						var pm = JP.replaceText($(section).find('.blair-product-finance-months input').val()),
							val = 0;

							pm = ((JP.isValidVal( JP.replaceText(pm, true) )) ? parseFloat(pm): 0);

							if( JP.isValidVal(pm) && pm > 0 ) {
								val = (totalInputVal/pm);
								// val = (totalInputVal*0.0157);
							}

							JPEvent.fire('input', {
								id: $(section).find('.blair-product-finance-per-month input').attr('id'),
								val: addCurrency(val, true) 
							});
							

							$(section)
								.find('.blair-product-finance-per-month input')
								.attr('value', addCurrency(val, true) )
								.val(  addCurrency(val, true) );
					});
				};


				var UpdateTotalPricing = function(element) {
					// 
					// 
					var dropzoneContainer = $(element).closest('.dropzone-container'), 
						totalPriceCntnr = $(dropzoneContainer).find('.blair-dropdown-options-total-price'),
						total = 0;
					var price = getBlairDropdownOptionsDiffTotalPrice(element);

						// console.log( price );


					$.each( $(dropzoneContainer).closest('#proposal-jp-create').find('.dropzone-container'), function(i, dc) {

						var ref = JP.getObject( $(dc).attr('template-ref') );

						if( self.isTemplate2(ref.tId) ) {
							var selectedMarked = $(dc).find('.blair-product-container .selected-marked');
							if( selectedMarked.length > 0 ) {
								var sub = $(selectedMarked).closest('.sub-blair-product-container');
								$(sub)
									.find('.blair-product-price-options-from-page2 input')
									.attr('value', addCurrency(price, true))
									.val(addCurrency(price, true));

								JPEvent.fire('input', {
									id: $(sub).find('.blair-product-price-options-from-page2 input').attr('id'),
									val: addCurrency(price, true)
								});

								UpdateTemplate2Pricing( $(sub).find('.blair-product-price-options-from-page2') , true);
							}
						}

					});
						

					$(totalPriceCntnr)
						.find('input')
						.attr('value',  addCurrency(price, true))
						.val(  addCurrency(price, true) );

					JPEvent.fire('input', {
						id: $(totalPriceCntnr).find('input').attr('id'),
						val:  addCurrency(price, true)
					});

					UpdateSalesPrice(element)

				};

				var UpdateDropdwonFortemplate2 = function(rsponse, element) {
					$(element).find('label.label').find('.blair-datalist').remove();
					$(element).find('label.label').append(rsponse.html);
					$(element).find('label.label').attr('ref-list', angular.toJson(rsponse.list));
					$(element).find('label.label').attr('ref-show-pricing', rsponse.showPricing);

					JPEvent.fire('label', {
						id: $(element).find('label.label').attr('id'),
						val: angular.toJson(rsponse.list)
					})
						
					if( rsponse.selectedList.length == 0) {
						$(element).find('label.label .item').css('display', 'block');
					}

					if( rsponse.selectedList.length > 0) {
						$(element).find('label.label .item').css('display', 'none');
					}

					$timeout(function() {
						JPEvent.fire('tags', {
							id: $(element).find('label.label').attr('id'),
							val: ($(element).find('label.label').html()),
							replaceHtml: true,
							tag: 'label'
						});
					});


				};

				var SelectProductItems = function(number, block){

					var parent = $(block).closest('#proposal-jp-create');

					$.each( $(parent).find('.dropzone-container'), function(i, dc) {

						if( JP.haveValue(JP.getObject( $(dc).attr('template-ref') ).tId) 
							&& self.isDropoDownTemplate(JP.getObject( $(dc).attr('template-ref') ).tId)) {

							
							// .select-option-btn
							$.each( $(dc).find('.blair-dropdown-options'), function(i, option) {
								var label = $(option).find('label.label');
								var selectedDropDownList = getBlairListForSection($(option).attr('col-data'), $(label).parent()); 

								var needToUpdatePricing = false;

								var list = $.map(angular.copy(selectedDropDownList), function(item, itemIndex) {

									var products = angular.copy(item.products);

									item.products = $.map(item.products, function(product, productIndex) {

										delete selectedDropDownList[itemIndex].products[productIndex].checked;

										if( JP.getArray(product.for).length > 0
											&& product.for.indexOf(JP.int(number)) > -1 ) {
											selectedDropDownList[itemIndex].products[productIndex].checked = true;
											return product;
										}
									});

									if( JP.isArray(item.products)  ) { return item; }
								});


								// console.log(list)

								if( JP.getArray(list).length > 0 &&  JP.getArray(list[0].products).length > 0 ) {

									/** UPDATE PRICING ***/
									needToUpdatePricing = true;

									/**
									* @get Filter data
									**/
									var showPricing = JP.haveValue($(label).attr('ref-show-pricing')) ? $(label).attr('ref-show-pricing') : true;
									var obj = $filter('BlairRemodllingSelectionModalFilterData')(list, showPricing, {
										"qty" : $(dc).find('.blair-product-template-product-price-qty input').val()
									});

									/**
									* @modify obj
									*/
									var mObj = angular.copy(obj);
									mObj.list = selectedDropDownList,
									mObj.selectedList = JP.getArray(list[0].products),
									mObj.showPricing = showPricing;

									/**
									* @modify content 
									**/
									UpdateDropdwonFortemplate2(mObj, $(label).parent());
								}
								
								/**
								*
								* @update 
								*/
								if( JP.isTrue( needToUpdatePricing ) ){
									UpdateTotalPricing( $(label).parent() );
								}
							});


						}
					});

				};


				/**
				* @bind events for template2
				**/
				var bindEventsForTemplate2 = function(ele) {

					$(ele).on('click', '.click-select-btn-subscribers a.btn' , function(e) {
						e.stopPropagation();
						e.preventDefault()

						var sub = $(this).closest('.sub-blair-product-container');


						$(sub)
							.closest('.blair-product-container')
							.find('.click-select-btn-subscribers a.btn')
							.removeClass('selected-marked')
							.removeClass('btn-primary')
							.addClass('btn-default')
							.text('Click to Select');

						/**
						*
						* @CURRENT MARK SELECTED
						*/
						$(this).text('Selected');
						$(this)
							.addClass('selected-marked')
							.addClass('btn-primary')
							.removeClass('btn-default');


						$.each( $(this).closest('.blair-product-container').find('.sub-blair-product-container'), function() {
							var a =  $(this).find('.click-select-btn-subscribers a');

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


						
						UpdatePropoductTitle(sub);

						/**
						*
						* @update products
						*/
						SelectProductItems( (($(this).closest('.blair-product-container').find('.sub-blair-product-container').index(sub))+1), this);


						/**
						* @update Pricing
						**/
						UpdateSalesPrice(sub);
					});
				};


				/**
				* @check subscriber
				**/
				self.isBlair = function() {
					return ( parseInt(scope.company.id) == BLAIR_REMOD.COMPANY_ID );
				};

				/**
				* @check is Template belong to [funv Template2]
				**/
				self.isTemplate2  = function(id, editMode, element) {

					if( JP.isTrue(editMode) ) {

						var ref = JP.getObject($(element).find('.dropzone-container').attr('template-ref'));
						if( JP.haveValue(ref.tId) ) {
							return self.isTemplate2(ref.tId);
						}
					}

					if( JP.haveValue(id) && BLAIR_REMOD.TEMPLATE_2.indexOf( JP.int(id) ) > -1) {
						return true
					}

					return false;
				};

				self.isDropoDownTemplate = function(id, editMode, element) {


					if( JP.isTrue(editMode) ) {

						var ref = JP.getObject($(element).find('.dropzone-container').attr('template-ref'));

						if( JP.haveValue(ref.tId) ) {
							return self.isDropoDownTemplate(ref.tId);
						}
					}

					if( JP.haveValue(id) && (BLAIR_REMOD.TEMPLATE_ID.indexOf( JP.int(id)  ) > -1 )) {
						return true;
					}

					return false;
				};

				/**
				* @set Data For selected content
				**/
				self.addTemplate2 = function(element, routeParams, notbindId) {
					
					var cont = $(element).find('.blair-product-container')
					$(cont).find('.click-select-btn-subscribers').attr('contenteditable', false);
		

					bindEventsForTemplate2(element);
				};




		        /**
		        * @on load event
		        **/
				$injector.invoke(init);
			}
		};
	};


	dir.$inject = ['$injector', '$timeout', '$http', '$filter', 'JPEvent'];

	/**
	* jobProgress Module
	*
	* @manage BLAIR DEMODELLING TEMPLATES
	*/
	angular
		.module('jobProgress')
		.directive('affordableRoofing', dir);
})();