(function() {
	'use strict';

	var dir  = function (JPEvent, $timeout, $injector) {
		return {
			restrict: 'A',
			link: function (scope, iElement, iAttrs) {
				
				var self = this;
				var TOPSIDE= JPAPP.getConfig().TOPSIDE;

				self.perMonthPercent = 0.0157;
				var init = function() {
					
					self.id = 691;
						
					/***
					* @set Company I
					**/
					$timeout(function() { bindForTopSide();	}, 1500);


					JPEvent.listen('TableOptionYesNo', function(obj, e) {
						
						if( !JP.isObject(obj) 
							|| !JP.isObject(obj.dc)
							|| !JP.haveValue(obj.dc.tId) ) {
							return;
						}

						if( self.isGBBTemplate(obj.dc.tId, false) ) {
							UpdateGBBPricing(obj.table);
						}
					});
				};

				self.isTopSide = function() {
					return ( parseInt(scope.company.id) == TOPSIDE.COMPANY_ID );
				};

				/**
				*
				* @bind events for Topside
				**/
				var bindForTopSide = function() {

					console.info( 'Start' );

					// if not 
					if( !self.isTopSide() ) { return; }

					console.info( 'Enter' );

					$.each( $(iElement).find('.dropzone-container'), function(i, dc) {
						var obj = JP.getObject($(dc).attr('template-ref'));

						console.info( 'Close' );

						if( JP.haveValue(obj.tId) ) {
							$(dc).find('input.public-input').attr('readonly', true);
							 
							if( self.isTopSideTemplate(obj.tId, false) ) {
								self.manageTemplate( $(dc).parent() , {}, true);
								return;
							}

							if( self.isGBBTemplate(obj.tId, false) ) {
								self.updateGBBTemplate( $(dc).parent() );
								return;
							}
						}
					}) 

				};


				/**
				*
				* @add currency
				*/
				var addCurrency  = function(val, withCurrency) { return JP.numberWithCurrency(val, withCurrency);	};

				/**
				*
				* @verify is Roofing Option Template
				*/
				self.isTopSideTemplate = function(id, editMode, element) {

					if( !self.isTopSide() ) {
						return false;
					}

					if( JP.isTrue(editMode) ) {

						var ref = $(element).find('.dropzone-container').attr('template-ref');

						if( JP.haveValue(ref) ) {
							ref = angular.fromJson(ref);
							return self.isTopSideTemplate(ref.tId);
						}
						
						return false;
					}


					if( TOPSIDE.TEMPLATE_ID.indexOf(parseInt(id)) > -1) {
						return true;
					}

					return false;
				};

				/**
				*
				* @verify is Roofing Option Template
				*/
				self.isGBBTemplate = function(id, editMode, element) {

					if( !self.isTopSide() ) {
						return false;
					}

					if( JP.isTrue(editMode) ) {


						var ref = $(element).find('.dropzone-container').attr('template-ref');
						console.log( typeof isNaN(ref) );

						if( JP.haveValue(ref) ) {
							ref = angular.fromJson(ref);
							return self.isGBBTemplate(ref.tId);
						}
						
						return false;
					}

					if( TOPSIDE.GBB_TEMPLATE.indexOf(JP.int(id)) > -1) {
						return true;
					}

					return false;
				};


				var UpdatePricing = function(ele)  {

					var total = 0,
						product = parseFloat(JP.replaceText($(ele).find('.topside-subs-price input').val(), true)), 
						totalEle = $(ele).find('.topside-subs-price-total input'),
						financeEle = $(ele).find('.topside-subs-price-total-finance input');

					$.each($(ele).find('.topside-subs'), function(i, section) {
						
						if( i != 0 ) {
							var yesNO = $(section).find('.topside-subs-price-additional-required .selected-marked').text();
							var price = parseFloat(JP.replaceText($(section).find('input').val(), true));

							if( angular.lowercase(yesNO) == 'yes' 
								&&  !isNaN(price) 
								&& JP.haveValue(price) )  {
								total += price;
							}
						}
					});

					if(  !isNaN(product) && JP.haveValue(product) )  {
						total += product;
					}


					var price = addCurrency(total, true), 
						finance = addCurrency(total*self.perMonthPercent, true);
					$(totalEle)
						.attr('value', price)
						.val(price);

					$(financeEle)
						.attr('value', finance)
						.val(finance);



					JPEvent.fire('input', {
						id: $(financeEle).attr('id'),
						val : finance
					});

					JPEvent.fire('input', {
						id:  $(totalEle).attr('id'),
						val : price
					});
					
					
				};

				var UpdateFinancing = function(input) {

					var cont = $(input).closest('.topside-subs');

					var priceCntr, // $(cont).find('.topside-subs-price'),
						perMontCnt = $(cont).find('.topside-subs-price-finance');

					if( $(cont).find('.topside-subs-price').length > 0 ) {
						priceCntr = $(cont).find('.topside-subs-price');
					} else {
						priceCntr = $(cont).find('.topside-subs-price-additional');
					}

					var perMonth = self.perMonthPercent,
						price    = parseFloat(JP.replaceText($(priceCntr).find('input').val(), true));

					console.log( price, perMonth, $(input).val() )

					if( JP.haveValue(price) && JP.haveValue(perMonth) ) {
						$(perMontCnt)
							.find('input:last')
							.attr('value', addCurrency(price*perMonth, true))
							.val(addCurrency(price*perMonth, true))
					}
				};

				var BindEvents = function(ele) {

					$(ele).on('keyup', '.topside-subs-price input', function() {

						/**
						* @update Financiang
						**/
						UpdateFinancing($(this));

						/**
						* @update Pricing
						**/
						UpdateGBBPricing(ele);
					});

					$(ele).on('keyup', '.topside-subs-price-additional input', function() {

						/**
						* @update Financiang
						**/
						UpdateFinancing($(this));

						/**
						* @update Pricing
						**/
						UpdateGBBPricing(ele);
					});


					$(ele).on('keyup', '.topside-subs-price-finance input', function() {

						/**
						* @update Financiang
						**/
						UpdateFinancing($(this));
					});

					$(ele).on('click', '.click-select-btn-subscribers a', function(e) {

						e.preventDefault();
						e.stopPropagation();
						
						$(this)
							.closest('.dropzone-container')
							.find('.topside-subs .topside-yes-no-btn a')
							.removeClass('selected-marked')
							.removeClass('btn-primary');

						$(this)
							.closest('.dropzone-container')
							.find('.topside-subs .topside-yes-no-btn a:last-child')
							.addClass('selected-marked')
							.addClass('btn-primary');

						$(this)
							.addClass('btn-primary')
							.addClass('selected-marked');

						if( angular.lowercase($(this).text()) != 'no' ) {

							$(this)
								.parent()
								.find('a:last-child')
								.removeClass('btn-primary')
								.removeClass('selected-marked');
						}


						
						UpdateGBBPricing(ele);
					})
				};

				/**
				* @method [Add, Edit] Roofing Option template
				**/
				self.manageTemplate = function(element, routeParams, editMode) {

					/**
					* @bind [Template ID]
					**/
					if( !JP.isTrue(editMode) ) {
						$(element).find('.dropzone-container').attr('template-ref', angular.toJson({ 'tId': routeParams.tid }));
					}

					/**
					* @disbaled Per Month Button
					**/
					$(element).find('.topside-subs-price-finance').find('input').attr('disabled', true)

					var cont = $(element).find('.topside-subs .topside-subs-price-additional-required');

					/**
					* @append Yes noo button
					**/
					/**
					* @add btn [selected-marked]
					**/
					if( !JP.isTrue(editMode) ) {

						if( $(cont).find('.click-select-btn-subscribers').length == 0  ) {
							var addBtn = '<div class="topside-yes-no-btn click-select-btn-subscribers" contenteditable="false">'
										+'<a ref="yes" style="float:left;"  class="btn btn-default btn-subs">Yes</a>'
										+'<a ref="no"  style="float:left;"  class="btn btn-default btn-primary btn-subs selected-marked">No</a>'
										+'</div> ';

							$(cont).find('label.label').after(addBtn);
						}
					} else {
						$(cont).find('.click-select-btn-subscribers').attr('contentEditable', false);
					}
					


					BindEvents(element);
				};

				/**
				*
				* @update price
				*/
				var UpdateGBBPricing = function(ele)  {


					var total = 0,
						// product = parseFloat(JP.replaceText($(ele).find('.topside-subs-price input').val(), true)), 
						totalEle = $(ele).find('.topside-subs-price-total input'),
						financeEle = $(ele).find('.topside-subs-price-total-finance input');

					var parent = $(ele).closest('#proposal-jp-create');

					$.each( $(parent).find('.dropzone-container'), function(i, template) {
						if( self.isGBBTemplate(null, true, $(template).parent() ) ) {
							$.each($(template).find('.topside-subs'), function(i, section) {
								var yesNO = $(section).find('.topside-subs-price-additional-required .selected-marked').text();
								var price = parseFloat(JP.replaceText($(section).find('.topside-subs-price input').val(), true));

								if( angular.lowercase(yesNO) == 'yes' 
									&&  !isNaN(price) 
									&& JP.haveValue(price) )  {
									total += price;
								}
							});

							var table = $(template).find('.topside-subs-price-table table');

							$.each($(table).find('tbody tr'), function(i, tr) {
						
								if( angular.lowercase(JP.getTableCellText($(tr).find('td:eq(1)'))) == 'yes' ) {

									var val = parseFloat(JP.replaceText(JP.getTableCellText($(tr).find('td:eq(2)'))));
									
									if( JP.haveValue(val) && !isNaN(val) ) {
										total += val;
									}
								}

							});

							// FOR Dynamic Template
							$.each($(template).find('.col-dependent-table-public tbody tr'), function(i, tr) {

								var obj = JP.getObject( $(tr).closest('table').attr('ref-rule') );

							
								if( angular.lowercase(JP.getTableCellText($(tr).find('td:eq('+obj.dependentCol.mainCol+')'))) == 'yes' ) {
								// if( angular.lowercase(JP.getTableCellText($(tr).find('td:eq(1)'))) == 'yes' ) {

									var val = parseFloat(JP.replaceText(JP.getTableCellText($(tr).find('td:eq('+obj.dependentCol.depCol+')'))));
									
									if( JP.isValidVal(val) ) {
										total += val;
									}
								}

							});
						}

						if( self.isTopSideTemplate(null, true, $(template).parent() ) ) {
							$.each($(template).find('.topside-subs'), function(i, section) {

								var yesNO = $(section).find('.topside-subs-price-additional-required .selected-marked').text();
								var price = parseFloat(JP.replaceText($(section).find('.topside-subs-price input').val(), true));

								var priceAdditional = parseFloat(JP.replaceText($(section).find('.topside-subs-price-additional input').val(), true));

								if( angular.lowercase(yesNO) == 'yes' 
									&&  !isNaN(price) 
									&& JP.haveValue(price) )  {
									total += price;
								}

								console.log( priceAdditional, yesNO, total );


								if( angular.lowercase(yesNO) == 'yes' 
									&&  !isNaN(priceAdditional) 
									&& JP.haveValue(priceAdditional) )  {
									total += priceAdditional;
								}
							});

						}
					});


					// if(  !isNaN(product) && JP.haveValue(product) )  {
					// 	total += product;
					// }


					
					var price = addCurrency(total, true),
						finance = addCurrency((parseFloat(JP.replaceText(total))*self.perMonthPercent) || 0, true);

						console.log( total, self.perMonthPercent )

					$(parent)
						.find('.topside-subs-price-total input')
						.attr('value', price)
						.val(price);

					$(parent)
						.find('.topside-subs-price-total-finance input')
						.attr('value', finance)
						.val(finance);

					JPEvent.fire('input', {
						id: $(parent).find('.topside-subs-price-total-finance input').attr('id'),
						val : finance
					});

					JPEvent.fire('input', {
						id:  $(parent).find('.topside-subs-price-total input').attr('id'),
						val : price
					});
				};

				var setTrDataForGBBTemplate = function(self) {
					var text  = angular.lowercase($(self).text()),
						tr = $(self).closest('tr');
					
					if( text == 'yes' ) {
						var atr = angular.fromJson($(tr).attr('options'));

						if( JP.haveValue(atr) && JP.haveValue(atr.price)) {
							$(tr).find('td:last-child .cell-text').text(atr.price);

							delete atr.price;
							
							$(tr).attr('options', angular.toJson(atr));
						}
					}

					if( text != 'yes' ) {
						var atr = angular.fromJson($(tr).attr('options'));
						var text = $(tr).find('td:last-child .cell-text').text();


						if( JP.haveValue(text)) {
							$(tr).find('td:last-child .cell-text').text('');
							atr.price = text;
							$(tr).attr('options', angular.toJson(atr));
						}
					}
				};

				/**
				*
				* @bind all events
				*/
				var BindGBBEvents = function(ele) {

					$(ele).on('keyup', '.topside-subs-price input', function() {
						console.log('working');
						/* update Financiang */
						UpdateFinancing($(this));

						/* update Pricing */
						UpdateGBBPricing(ele);
					});


					$(ele).on('click', '.topside-subs-price-additional-required .click-select-btn-subscribers a', function(e) {
						
						e.preventDefault();
						e.stopPropagation();
						
						$(this)
							.closest('.dropzone-container')
							.find('.topside-subs .topside-yes-no-btn a')
							.removeClass('selected-marked')
							.removeClass('btn-primary');

						$(this)
							.closest('.dropzone-container')
							.find('.topside-subs .topside-yes-no-btn a:last-child')
							.addClass('selected-marked')
							.addClass('btn-primary');

						$(this)
							.addClass('btn-primary')
							.addClass('selected-marked');

						if( angular.lowercase($(this).text()) != 'no' ) {

							$(this)
								.parent()
								.find('a:last-child')
								.removeClass('btn-primary')
								.removeClass('selected-marked');
						}

						$.each( $(this).closest('.dropzone-container').find('.topside-subs .topside-yes-no-btn a'), function(i, a) {
							console.log('yo', a);
							JPEvent.fire('a', {
								id: $(a).attr('id'),
								val: $(a).attr('class')
							})
						});

						UpdateGBBPricing(ele)
					});

					/* table Yes/No */
					$(ele).on('click', '.topside-subs-price-table table .click-select-btn-subscribers a', function(e) {
						
						e.preventDefault();
						e.stopPropagation();


						setTrDataForGBBTemplate(this)


						$(this)
							.closest('.click-select-btn-subscribers')
							.find('a')
							.removeClass('btn-primary')
							.removeClass('selected-marked');

						$(this)
							.addClass('btn-primary')
							.addClass('selected-marked');

						$(this)
							.closest('td')
							.find('.cell-text').text($(this).text() );

						UpdateGBBPricing(ele);
					})
				};

				/**
				* @method [Add, Edit] Roofing Option template
				**/
				var getBtn = function(id) {
					return '<div class="topside-yes-no-btn click-select-btn-subscribers" contenteditable="false">'
					+'<a id="'+id+'-yes'+'" ref="yes" style="float:left;"  class="btn btn-default btn-subs">Yes</a>'
					+'<a id="'+id+'-yes'+'" ref="no"  style="float:left;"  class="btn btn-default btn-primary btn-subs selected-marked">No</a>'
					+'</div> ';
				};

				self.manageGBBTemplate = function(element, routeParams, editMode, returnHtml) {

					/**
					* @bind [Template ID]
					**/
					if( !JP.isTrue(editMode) ) {
						$(element).find('.dropzone-container').attr('template-ref', angular.toJson({ 'tId': routeParams.tid }));
					}

					/**
					* @disbaled Per Month Button
					**/
					$(element).find('.topside-subs-price-finance').find('input').attr('disabled', true)

					var cont = $(element).find('.topside-subs .topside-subs-price-additional-required');
					var table = $(element).find('.topside-subs-price-table table');
					var addBtn = '<div class="topside-yes-no-btn click-select-btn-subscribers" contenteditable="false">'
								+'<a ref="yes" style="float:left;"  class="btn btn-default btn-subs">Yes</a>'
								+'<a ref="no"  style="float:left;"  class="btn btn-default btn-primary btn-subs selected-marked">No</a>'
								+'</div> ';

					/**
					* 
					* @append Yes noo button
					* @add btn [selected-marked]
					**/
					if( !JP.isTrue(editMode) ) {

						/* check container */
						if( $(cont).find('.click-select-btn-subscribers').length == 0  ) {

							// $(cont).find('label.label').after(addBtn);

							$.each($(cont).find('label.label'), function(i, label) {
								$(label).after( getBtn(JP.getUniqueVal()+'-'+(i+2)));
							});

						}

						/* check table */
						if( $(table).find('.click-select-btn-subscribers').length == 0  ) {

							$.each($(table).find('tbody tr'), function(i, tr) {
								$(tr).attr('options', angular.toJson({ topsideId: routeParams.tid  }));
								$(tr).find('td:eq(1)').append(addBtn);
							});
						}
					} else {

						$(cont).find('.click-select-btn-subscribers').attr('contentEditable', false);
					}


					if( JP.isTrue(returnHtml) ) {
						return cont;
					}

					BindGBBEvents(element);
				};

				/**
				* @method [Add, Edit] Roofing Option template
				**/
				self.updateGBBTemplate = function(element) {

					/**
					* @disbaled Per Month Button
					**/
					$(element).find('.topside-subs-price-finance').find('input').attr('disabled', true)

					var cont = $(element).find('.topside-subs .topside-subs-price-additional-required');
					var table = $(element).find('.topside-subs-price-table table');
					var addBtn = '<div class="topside-yes-no-btn click-select-btn-subscribers" contenteditable="false">'
								+'<a ref="yes" style="float:left;"  class="btn btn-default btn-subs">Yes</a>'
								+'<a ref="no"  style="float:left;"  class="btn btn-default btn-primary btn-subs selected-marked">No</a>'
								+'</div> ';

					/* check table */
					if( $(table).find('.click-select-btn-subscribers').length == 0  ) {

						$.each($(table).find('tbody tr'), function(i, tr) {
							$(tr).find('td:eq(1)').append(addBtn);
							var txt = $(tr).find('td:eq(1)').find('.cell-text').text();

							if( JP.haveValue(txt) ) {
								
								$(tr)
									.find('[ref=' + angular.lowercase(txt) + ']')
									.addClass('btn-primary')
									.addClass('selected-marked');
								
								$(tr)
									.find('[ref=' + angular.lowercase(txt) + ']')
									.next('a')
									.removeClass('btn-primary')
									.removeClass('selected-marked');
							}
						});
					}

					$(cont).find('.click-select-btn-subscribers').attr('contentEditable', false);

					BindGBBEvents(element);
				};

				/**
				*
				* @after update
				*/
				self.setTopSideTemplateAfterModalUpdate = function(ele, id) {

					var table = $(ele).find('.topside-subs-price-table table');
					var addBtn = '<div class="topside-yes-no-btn click-select-btn-subscribers" contenteditable="false">'
								+'<a ref="yes" style="float:left;"  class="btn btn-default btn-subs">Yes</a>'
								+'<a ref="no"  style="float:left;"  class="btn btn-default btn-primary btn-subs selected-marked">No</a>'
								+'</div> ';

					if( $(table).find('.click-select-btn-subscribers').length == 0  ) {

						$.each($(table).find('tbody tr'), function(i, tr) {
							$(tr).find('td:eq(1)').append(addBtn);
							var txt = ($(tr).find('td:eq(1)').find('.cell-text').text());

							setTrDataForGBBTemplate($(tr).find('td:eq(1)').find('.cell-text'));

							if( JP.haveValue(txt) ) {

								$(tr)
									.find('td:eq(1) a')
									.removeClass('btn-primary')
									.removeClass('selected-marked');

								if( angular.lowercase(txt) == 'yes' ) {
									$(tr)
										.find('td:eq(1) a:first-child')
										.addClass('btn-primary')
										.addClass('selected-marked');
								}

								if( angular.lowercase(txt) != 'yes' ) {
									$(tr)
										.find('td:eq(1) a:last-child')
										.addClass('btn-primary')
										.addClass('selected-marked');							
								}
								
							}

						});

					}

					console.log( $(table).find('.click-select-btn-subscribers') );
					
					UpdateGBBPricing(ele);
				};


				/**
				*
				* @add page dynamic for Merge Proposal
				*/
				self.getGBBPageForMerge = function(content, id) {

					var wrap = $('<div />').append(content);

					$(wrap).find('.dropzone-container').attr('template-ref', angular.toJson({ 'tId': id }));

					return $(wrap).html();
				};

				scope.$on('$destroy', function() {
					JPEvent.destroy('TableOptionYesNo');
					$(iElement).find('*').unbind();
				});

				$injector.invoke(init);
			}
		};
	};

	dir.$inject = ['JPEvent', '$timeout', '$injector'];

	/**
	* jobProgress Module
	*
	*/
	angular
		.module('jobProgress')
		.directive('topside', dir);
})();