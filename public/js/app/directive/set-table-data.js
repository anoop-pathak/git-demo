(function() {
	'use strict';

	

	var dir = function ($injector, $timeout, JPEvent) {
		return {
			restrict: 'A',
			link: function (scope, iElement, iAttrs) {
				
				var init = function() {

					$timeout(function() {

						bindMethods();
					}, 500);
				};

				var getSum = function(table, index ) {
					var sum= 0;
					
					$.each( $(table).find('tbody tr'), function(iTr, tr) {
						var tdTxt = parseFloat(JP.replaceText(JP.getTableCellText($(tr).find('td:eq('+index+')'))));
						if( JP.isValidVal(tdTxt) ){ sum += tdTxt; }
					});

					return JP.numberWithCurrency(sum);
				};

				var getColPercent = function(tbl, option, tr) {

					
					var sum = getSum(tbl, option.field), total = 0;

					if( angular.isUndefined(option.cell) ) { return 0; }

					if( option.cell > -1 ) {
						var per = parseFloat(JP.replaceText(JP.getTableCellText($(tr).find('td:eq('+option.cell+')'))) || 0) ;

						if( !JP.isValidVal(per) ) { per = 0; }

						return JP.numberWithCurrency(parseFloat((JP.replaceText(sum)/100)*per), true);
					}

					return JP.numberWithCurrency(0, true);
				};

				var getCelPercent = function(tbl, option, tr) {

					var total = '0.00';

					if( angular.isObject(option.field)
						&& JP.haveValue(option.field.row) ) {
						// var sum = getSum(tbl, option.field), total = 0;
						var mainTr = $(tbl).find('tfoot tr:eq('+option.field.row+')')

						if( angular.isUndefined(option.cell) ) {
							return 0;
						}

						if( option.cell > -1 ) {
							var f = JP.getTableCellText($(mainTr).find('td:eq('+option.field.cell+')'));
							var sum = parseFloat(JP.replaceText(f) || 0) ;
							var per = parseFloat(JP.replaceText(JP.getTableCellText($(tr).find('td:eq('+option.cell+')'))) || 0) ;


							if( !JP.isValidVal(per) ){ per = 0; }

							total = (parseFloat((sum/100)*per));
						}

					}


					return JP.numberWithCurrency(total);
				};

				var getCellSub = function (tbl, option, tr) {

					// return 5;

					// var $filter = $injector.get('$filter');
					var sub = option.subs || [];
					// console.log( 'Vals',  sub, option);

					if( !angular.isArray(sub) ) {
						return 0;
					}

					if( sub.length == 0 ) {
						return 0;
					}

					var isDollor = false;
					var vals = [];
					// var tdlength = tbl.find('tbody tr:eq(0)').find('td').length;
					for (var i = 0; i < sub.length; i++) {
						vals.push(JP.getTableCellText($(tbl).find('tfoot tr:eq('+ sub[i].row+ ')').find('td:eq('+ sub[i].cell+ ')')) );
					}



					var t = []; // replaceText(vals[0]);
					
					for (var i = 0; i < vals.length; i++) {
						t.push((JP.replaceText(vals[i]) || 0));

					}

					return JP.numberWithCurrency(eval(t.join(' - ')));
				};

				var getCellAddition = function(table, fields) {
					var sum= 0;
					$.map(fields, function(td) {

						var  val= JP.getTableCellText($(table).find('tfoot tr:eq('+td.row+')').find('td:eq('+td.cell+')'));
						val = parseFloat( JP.replaceText(val) ) || 0;
						
						if( JP.isValidVal(val) ) { sum += val; }
					});

					return JP.numberWithCurrency(sum);
				};

				var multiCalculation = function(table, obj, tr) {
					var text = '', footer = [];


					$.each( $(table).find('tfoot tr'), function(i, tr)  {
						var row = {
							ele: $(tr),
							tds: []
						};

						$.each($(tr).find('td'), function(i, td) {
							row.tds.push({
								text: JP.getTableCellText(td),
								td: $(td) 
							});
						});

						footer.push(row);
					});

					// var footer = angular.copy(footerData);

					if( JP.haveValue(obj.first.opreation)
						&& angular.isDefined(footer[obj.first.cell1.row].tds[obj.first.cell1.col].text)
						&& angular.isDefined(footer[obj.first.cell2.row].tds[obj.first.cell2.col].text)) {
						text = JP.replaceText(footer[obj.first.cell1.row].tds[obj.first.cell1.col].text) || 0;
						text +=' '+(obj.first.opreation)+' ';
						text +=  (JP.replaceText(footer[obj.first.cell2.row].tds[obj.first.cell2.col].text)) || 0;
						
					}

					$.map(obj.extraCols, function(item) {
						if( angular.isDefined(footer[item.cell.row].tds[item.cell.col].text)
							&& JP.haveValue(item.opreation) ) {
							var val = (JP.replaceText(footer[item.cell.row].tds[item.cell.col].text) || '0');
							if(  JP.haveValue( JP.replaceText(val, true) ) ) {
								text += ' '+(item.opreation)+' ' + val;
							}
						}

					});

					return JP.numberWithCurrency(eval(text));
				};

				var getCellMul = function(tr, obj, tds) {

					var totl = 0;

					if( angular.isObject(obj) 
						&& JP.haveValue(obj.first)
						&& JP.haveValue(obj.second)) {
						totl = parseFloat(JP.replaceText( JP.getTableCellText( $(tr).find('td:eq('+obj.first+')') )) || 0)* parseFloat(JP.replaceText(JP.getTableCellText( $(tr).find('td:eq('+obj.second+')'))) || 0);
					}

					if( JP.isValidVal(totl) ) {
						return JP.numberWithCurrency(totl);
					}

					return JP.numberWithCurrency(totl);
				};

				var UpdateTdValue = function(td, val) {

					JP.setTableCellText( td,  val);


					if( $(td).hasClass('cell-with-dropdown') ) {
		                JPEvent.fire('span', {
							val: val,
							id: $(td).find('.cell-text').attr('id')
						});
		            	return;
		            } 


	                JPEvent.fire('td', {
						val: val,
						id: $(td).attr('id')
					});
					
				};


				var getValue = function(obj, tr, text, refExtra) {

					var oprtn = obj.operation,
						flds  = obj.fields,
						f 	  = JP.replaceText(JP.getTableCellText( $(tr).find('td:eq('+flds.first.tdIndex+')') )),
						s 	  = JP.replaceText(JP.getTableCellText( $(tr).find('td:eq('+flds.second.tdIndex+')') )),
						value;



					if( oprtn.sign == '*' ) {
						value = parseFloat(f||0)*(parseFloat(s||0)); 
					}

					if( oprtn.sign == '+') {
						value = parseFloat(f||0)+(parseFloat(s||0)); 
					}

					if( oprtn.sign == '-') {
						value = parseFloat(f||0)-(parseFloat(s||0)); 
					}

					if( oprtn.sign == '/') {
						value = parseFloat(f||0)/(parseFloat(s||0)); 
					}
				

					if( JP.isArray(refExtra) ) {

						angular.forEach(refExtra, function(item) {

							var text = 	JP.replaceText( JP.replaceText(JP.getTableCellText( $(tr).find('td:eq('+item.field+')') )) );
							
							if( item.sign == '*' ) {
								value = value*parseFloat(text || 0);
							}

							if( item.sign == '-' ) {
								value = value-parseFloat(text || 0);
							}

							if( item.sign == '/' ) {
								value = value/parseFloat(text || 0)
							}

							if( item.sign == '+' ) {
								value = value+parseFloat(text || 0);
							}
						});
						
					}

					return JP.numberWithCurrency(parseFloat(value), true);

				};

				var	UpdateCalculation = function( table,  mainCol, depCols ) {

					var ref = JP.getObject($(table).attr('ref')),
						refExtra = JP.getObject($(table).attr('ref-extra'));




					if(  JP.isArray(ref) ) {

						var obj  = ref.pop();

						// if( JP.isObject(obj) 
						// 	&& JP.isObject(obj.computeDetail)) {

						// 	$.each( $(table).find('tbody tr'), function(iTr, tr) {							
						// 		var val = getValue(obj.computeDetail, tr, JP.getTableCellText($(tr).find('td:eq('+mainCol+')')), refExtra);
						// 		UpdateTdValue($(tr).find('td:eq('+obj.compute+')'), val);
						// 	});
						
						// }
					}

					$.each( $(table).find('tfoot tr'), function(iTr, tr) {
						$.each( $(tr).find('td'), function(iTd, td){

							var obj = angular.fromJson($(td).attr('ref-obj'));


							if( angular.isObject(obj) && JP.haveValue(obj.operation) ) {
								if( angular.lowercase(obj.operation) == 'sum') {
									UpdateTdValue(td, getSum(table, obj.field))
								}

								if( obj.operation == 'percent') {
									UpdateTdValue(td, getColPercent(table, obj, tr ));
								}

								if( obj.operation == 'percent_cell') {
									UpdateTdValue(td, getCelPercent(table, obj, tr ));
								}

								if( obj.operation == 'sub') {
									UpdateTdValue(td, getCellSub(table, obj, tr ));
								}

								if( obj.operation == 'cell_addition') {
									UpdateTdValue(td, getCellAddition(table, obj.additions));
								}

								if( obj.operation == 'cell_mul') {
									UpdateTdValue(td, getCellMul(tr, obj.mul ));
								}

								if( obj.operation == 'multi_cal') {
									UpdateTdValue(td, multiCalculation(table, obj, tr ));
								}
							}

						});
					})

				};

				var manageYesNoClick = function(ref, mainCol, depCols) {
					$(ref).parent().find('a').removeClass('btn-primary')

					$(ref).addClass('btn-primary');

					var span = $(ref).closest('td').find('span'),
						tr   = $(ref).closest('tr');

					$(span).text( $(ref).text() );

					/**
					* @fire Selection table cell 
					**/
					JPEvent.fire('span', {
						id: $(span).attr('id'),
						val: $(span).text()
					});

					var options = angular.fromJson($(tr).attr('options')); 
					
					/**
					* @if btn text [YES]
					***/
					if( angular.lowercase($(ref).text()) == 'yes'
						&& angular.isObject(options)
						&& JP.haveValue(options.depPrice)) {
						JP.setTableCellText( $(tr).find('td:eq('+depCols+')'), options.depPrice);
					}
					

					/**
					* @if btn text [NO]
					***/
					if( angular.lowercase($(ref).text()) == 'no' ) {
						JP.setTableCellText( $(tr).find('td:eq('+depCols+')'), '');
					}
					
					/**
					* @fire Td Cell Data Change Event
					***/
					JPEvent.fire('span', {
						id: $(tr).find('td:eq('+depCols+') span').attr('id'),
						val: JP.getTableCellText( $(tr).find('td:eq('+depCols+')'))
					});
						

					JPEvent.fire('TableOptionYesNo', {
						text: JP.getString($(ref).text()),
						table: $(tr).closest('table'),
						dc: JP.getObject( $(tr).closest('.dropzone-container').attr('template-ref') )
					});
					/**
					* @update calculations
					***/
					UpdateCalculation( $(tr).closest('table'), mainCol, depCols );
				};


				/**
				*
				* @append/bind Yes No Btn 
				*/
				var bindYesNoOption = function(table, mainCol, depCols){


					$.each($(table).find('tbody tr'), function(i, tr) {

						var addBtn = '<div class="click-select-btn-subscribers btn-group" contenteditable="false">'
						+'<a ref="yes" style="float:left;"  class="btn btn-yes btn-xs btn-default btn-subs">Yes</a>'
						+'<a ref="no"  style="float:left;"  class="btn btn-no btn-xs btn-default btn-primary btn-subs selected-marked">No</a>'
						+'</div> ';
						$(tr)
							.find('td:eq('+mainCol+')')
							.css('position', 'relative')
							.append(addBtn);


						var dVal = JP.getTableCellText($(tr).find('td:eq('+depCols+')'));
						if( JP.haveValue(dVal) ){
							$(tr).find('td:eq('+mainCol+')').find('.click-select-btn-subscribers a').removeClass('btn-primary');
							$(tr).find('td:eq('+mainCol+')').find('.click-select-btn-subscribers a.btn-yes').addClass('btn-primary');								
						}
					});


					$(table).on('click', '.click-select-btn-subscribers a', function() {
						manageYesNoClick( this, mainCol, depCols)					
					});

				};

				var bindMethods = function() {

					if( $(iElement).find('.col-dependent-table-public').length == 0 ) {
						return;
					}


					$.each($(iElement).find('.col-dependent-table-public'), function(i, table) {
						var rule = angular.fromJson($(table).attr('ref-rule'));

						if( angular.isObject(rule) 
							&& angular.isObject(rule.dependentCol)
							&& JP.haveValue(rule.dependentCol.mainCol)
							&& JP.haveValue(rule.dependentCol.depCol)) {


							bindYesNoOption(table, rule.dependentCol.mainCol, rule.dependentCol.depCol);
						}
					});
				};

				$injector.invoke(init);		
			}
		};
	};

	dir.$injector = ['$injector', '$timeout', 'JPEvent'];


	/**
	* jobProgress Module
	*
	*/
	angular
		.module('jobProgress')
		.directive('setTableData', dir);

})();