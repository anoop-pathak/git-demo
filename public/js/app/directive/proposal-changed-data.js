(function() {
	'use strict';

	var dir = function ($injector, $timeout, JPEvent) {
		return {
			restrict: 'A',
			scope: {
				proposalChangedData: '='
			},
			link: function (scope, iElement, iAttrs) {
					
				var obj;
				var setObjectBlank = function() {
					obj  = { input: {}, span: {}, td: {}, a: {}, tags: {}, label: {}, textarea: {}};
				};
				var init = function() {

					setObjectBlank();

					/***
					*
					* @set Table to be editable
					*/
					$timeout(function() {
						enableEditableContent();

						$(iElement).find('.dropzone-container').height(1100);
						if( typeof iAttrs.pageType == 'string' && iAttrs.pageType == 'legal-page'  ) {
							$(iElement).find('.dropzone-container').height(1285);
						}
						$('.image-actions').hide();
						$(iElement).removeAttr('style');
						$(iElement).closest('.modal-body').addClass('scroll-y');
						$(iElement).closest('.modal-content').find('.aside-close-btn').removeAttr('style');
						$(iElement).closest('.modal-body').find('.loader').remove();	
					}, 1200)


					/**
					* @bind Events
					**/
					bindChangeEvent(); 

					/**
					* @listen Span Event
					**/
					JPEvent.listen('span', function(data) {
						if( !JP.haveValue(data.id) ){ return; }
						obj.span[data.id] = data.val;
					});

					/**
					* @listen td Event
					**/
					JPEvent.listen('td', function(data) {
						if( !JP.haveValue(data.id) ){ return; }
						obj.td[data.id] = data.val;
					});

					/**
					* @listen tags Event
					**/
					JPEvent.listen('tags', function(data) {
						if( !JP.haveValue(data.id) ){ return; }

						obj.tags[data.id] = {
							val: data.val,
							tag: data.tag,
							attr: JP.getString(data.attr),
							replaceHtml: JP.isTrue(data.replaceHtml)
						};
					});

					/**
					* @listen a Event
					**/
					JPEvent.listen('a', function(data) {
						if( !JP.haveValue(data.id) ){ return; }
						obj.a[data.id] = data.val;
					});

					/**
					* @listen label Event
					**/
					JPEvent.listen('label', function(data) {
						if( !JP.haveValue(data.id) ){ return; }
						obj.label[data.id] = data.val;
					});

					/**
					* @listen input Event
					**/
					JPEvent.listen('input', function(data) {
						if( !JP.isObject(data) || !JP.haveValue(data.id) ){ return; }
						obj.input[data.id] = data.val;
					});

					/**
					* @listen clear all data Event
					**/
					JPEvent.listen('ClearChanges', function() {
						setObjectBlank();
					});
				};

				var enableEditableContent = function() {
					$(iElement)
						.find('input.public-input')
						.addClass('highlight-input')
						.attr('readonly', false)
						.attr('disabled', false);

					$(iElement)
						.find('textarea.public-input')
						.addClass('highlight-input')
						.attr('readonly', false)
						.attr('disabled', false);
				};

				var bindChangeEvent = function() {

					$(iElement).on('keyup', 'input.public-input', function() {
						obj.input[$(this).attr('id')] = $(this).val();
					});

					$(iElement).on('change', 'textarea.public-input', function(e) {
		            	e.stopPropagation();
		            	e.preventDefault();
		            	
		            	$(this).text($(this).val());
		            	obj.textarea[$(this).attr('id')] = $(this).val();
		            });
				};

				var getArray = function(arr, tag, isId, attr) {
					console.log('arr', arr);
					return Object.keys(arr).map(function(key) {
						var obj  = {
							attribute: (JP.isTrue(isId) ? 'class' : 'id') ,
							attribute_value: key,
							value: arr[key],
							tag: tag
						};

						if( JP.haveValue(attr) ) { obj.update_attribute = attr;	}
						return obj;
					});
				};

				var getArrayTags = function(arr) {
					return Object.keys(arr).map(function(key) {
						var obj  = {
							attribute: 'id' ,
							attribute_value: key,
							value: arr[key].val,
							tag: arr[key].tag
						};

						console.log( arr[key] );

						if( JP.haveValue( arr[key].attr) ) { obj.update_attribute =  arr[key].attr;	}
						if( JP.isTrue( arr[key].replaceHtml) ) { obj.update_html =  1;	}
						return obj;
					});
				};

				scope.proposalChangedData.getList = function() {
					
					console.log('test', getArray(obj.textarea, 'textarea'));
					var arr = [];

					arr = arr.concat( getArray(obj.input, 'input') );
					arr = arr.concat( getArray(obj.textarea, 'textarea') );
					arr = arr.concat( getArray(obj.span, 'span') );
					arr = arr.concat( getArray(obj.td, 'td') );
					arr = arr.concat( getArray(obj.a, 'a', false, 'class') );
					arr = arr.concat( getArray(obj.label, 'label', false, 'ref-list') );
					arr = arr.concat( getArrayTags(obj.tags) );

					return arr;
				};

				scope.$on('destroy', function(){
					JPEvent.destroy('span');
					JPEvent.destroy('td');
					JPEvent.destroy('a');
					JPEvent.destroy('input');
					JPEvent.destroy('tags');
					JPEvent.destroy('label');
					JPEvent.destroy('ClearChanges');
				});

				$injector.invoke(init);
			}
		};
	};

	dir.$injector = ['$injector', '$timeout', 'JPEvent'];

	/**
	* jobProgress Module
	*
	* @capture all changes in proposal page
	*/
	angular
		.module('jobProgress')
		.directive('proposalChangedData', dir)
		.directive('isCustomerInput', ['$injector', '$timeout',  function ($injector, $timeout) {
			return {
				restrict: 'A',
				scope: {
					proposal: '=isCustomerInput',
					customerAttr: '='
				},
				link: function (scope, iElement, iAttrs) {
				
					var init = function() {

						var totalInput = 0
						$timeout(function() {


							if( !JP.haveValue(scope.proposal) || (scope.proposal.is_file != '0') ) {
								return;
							}

							$.map(scope.proposal.pages.data, function(page, i) {

								var wrpr = $('<div />').append(page.content);
								
								totalInput += $(wrpr).find('table.col-dependent-table-public').length;
								totalInput += $(wrpr).find('input.public-input').length;
							});

							console.log( '[Count]',  totalInput );
							if( totalInput > 0 ) {
								$(iElement).removeAttr('style');
							}
						});
					};

					$injector.invoke(init);
				}
			};
		}]);
})();