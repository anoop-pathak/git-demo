(function() {
	'use strict';

	var dir = function ($injector, $timeout, $http, $filter, JPEvent) {
		return {
			restrict: 'A',
			link: function (scope, iElement, iAttrs) {

				var self = this;
				var BLAIR_REMOD= JPAPP.getConfig().PINNACLE;

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
						total  = 0,
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
						}

						if( self.isTemplate2(ref.tId) ) {
							var selectedMarked = $(dc).find('.blair-product-container .selected-marked');


							if( selectedMarked.length > 0 ) {
								var sub = $(selectedMarked).closest('.sub-blair-product-container');
							
								var price  = parseFloat(JP.replaceText($(sub).find('.blair-product-deposit input').val())) || 0;

								
								if( JP.isValidVal(price) ) {
									// total += price;

									var vp = addCurrency(price, true);

									$(deposit)
										.attr('value', vp)
										.val(vp);

									JPEvent.fire('input', {
										id: $(deposit).attr('id'),
										val: vp
									});
								}
							}

						}

					});

					if( JP.haveValue(JP.replaceText($(deposit).val(), true)) ) {
						var dep = parseFloat(JP.replaceText($(deposit).val()));

						if( JP.isValidVal(dep)) {
							depVal =  dep;
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

				var UpdateTotalPricing = function(element) {
					// 
					var dropzoneContainer = $(element).closest('.dropzone-container'), 
						totalPriceCntnr = $(dropzoneContainer).find('.blair-dropdown-options-total-price'),
						total = 0;


					$.each($(dropzoneContainer).find('.blair-dropdown-options'), function(i, section) {
						$.each($(section).find('li'), function(i, lineItem) {
							var text = JP.replaceText($(lineItem).find('.blair-option-price').text());

							if( JP.haveValue(text) ) {
								total += parseFloat(text);
							}
						});
					});

					var val = JP.replaceText($(dropzoneContainer).find('.'+BLAIR_REMOD.PRODUCTS.PRODUCT_NAME_PRICE+ ' input').val(), true) || 0;

					if( JP.haveValue(val) ) {
						total += parseFloat(val);
					}

					var price = addCurrency(total, true);
					$(totalPriceCntnr)
						.find('input')
						.attr('value', price)
						.val( price );

					JPEvent.fire('input', {
						id: $(totalPriceCntnr).find('input').attr('id'),
						val: price
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

					console.log( ($(element).find('label.label').html()).toString() );

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

							
							console.log( dc );
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
									var obj = $filter('PinnacleRoofingSelectionModalFilterData')(list, showPricing, {
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
		.directive('pinnacle', dir)
		.filter('PinnacleRoofingSelectionModalFilterData', ['$filter', function($filter) {

			var getPrice = function(val, Options) {

				if( !JP.haveValue(val.price) ) {
					return val.price;
				}

				// product.price
				var refinedVal = parseFloat(JP.replaceText(val.price));

				if( JP.haveValue(val.multiple) 
					&& JP.isValidVal(parseFloat(Options[val.multiple])) 
					&& JP.isValidVal(refinedVal) ) {

					refinedVal = (refinedVal*Options[val.multiple])
				
					return JP.numberWithCurrency(parseFloat(refinedVal), true);
				}
				// console.log(val, JP.isValidVal(val.multiple),  JP.isValidVal(parseFloat(Options[val.multiple])) );



				if( JP.isValidVal(refinedVal) ) {
					return JP.numberWithCurrency(parseFloat(refinedVal), true);
				}

				return val.price || 0;
			};

			return function(data, showPricing, Options) {	
				var html = ''; // '<ul>';
				var selectedList = [];
				$.map(data, function(section) {
					var ul = '<ul class="blair-datalist" style="line-height:20px;">';// '<li><h4>'+ section.name +'</h4><ul>';

					var lis = $.map(section.products, function(product) {
						selectedList.push(product);
						return ( '<li>'
						+'<div class="blair-option-description" >'+section.name+': '+product.name+'</div>'
						+'<div class="blair-option-price" >'
						+ ((JP.isTrue(showPricing)) ? getPrice(product, Options):('<span style="display:none;">'+getPrice(product, Options)+'</span>'))
						+'</div>'
						+'</li>');
					});

					ul += (lis).join(' ');
					ul += '</ul>';

					html += ul;
				});

				// html += '<ul>';

				return { html: html, selectedList: selectedList };
			};
		}]);
})();