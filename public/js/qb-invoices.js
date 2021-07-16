(function($) {

	// var QuickbookPayments = {
	// 	/**
	// 	 * Will Open the Quickbook Payments Window
	// 	 * @param {array} invoices - Array of invoices.
	// 	 * @param {string} invoices - or Could be invoices id(s) with a delimiter (default: comma-separated).
	// 	 * @param {string} delimiter - Only If first argument invoices is a string separted by a delimiter (default: comma-separated)
	// 	 */
	// 	pageUrl : paymentPageRoute,
	// 	openWindow : function (invoices, delimiter = ",") {

	// 		if(!Array.isArray(invoices)) {
	// 			invoices = invoices.split(delimiter);
	// 		}
			
	// 		invoicesAsQueryString = "";

	// 		invoices.forEach(function(invoice) {
	// 			invoicesAsQueryString += "invoices[]=" + invoice + "&";
	// 		});

	// 		pageUrlWithInvoices = this.pageUrl + "?" + invoicesAsQueryString;

	// 		if(invoices.length > 0) {
	// 			window.open(pageUrlWithInvoices, 'QuickBooks Payment', 'width=600,height=400');
	// 		}
	// 	},
	// 	button : {
	// 		hide : function() {
	// 			QuickbookPayments.button.elem.hide();
	// 		},
	// 		show : function() {
	// 			QuickbookPayments.button.elem.show();
	// 		},
	// 		disable : function() {
	// 			QuickbookPayments.button.elem.attr('disabled', 'disabled');
	// 		},
	// 		enable : function() {
	// 			QuickbookPayments.button.elem.removeAttr('disabled');
	// 		},
	// 		initEvents : function(ele) {

	// 			QuickbookPayments.button.elem = $(ele).find(".quickbook-payment-button");

	// 			this.hide();

	// 			setTimeout(function() {
	// 				$(QuickbookPayments.button.elem).on('click', function() {
	// 					QuickbookPayments.openWindow(invoices.getSelected())
	// 				});
	// 			});
	// 		}
	// 	}
	// };

	// // START: Invoice Selection in Modal related code
	// var invoices = {
	// 	getSelector: function(){
	// 		return $(invoices.modal).find('input.invoice-checkbox');
	// 	},
	// 	setModal: function(ele) {
	// 		invoices.modal = ele;
	// 	},
	// 	getModal: function() {
	// 		return invoices.modal;
	// 	},
	// 	selectAll : function () {
	// 		$(invoices.getSelector()).each(function() {
	// 			this.checked = true;
	// 		});

	// 		QuickbookPayments.button.show();
	// 	},
	// 	unselectAll : function () {
	// 		$(invoices.getSelector()).each(function() {
	// 			this.checked = false;
	// 		});

	// 		QuickbookPayments.button.hide();
	// 	},
	// 	getSelected : function () {
	// 		selected = []
	// 		$(invoices.getSelector()).each(function() {
	// 			if(this.checked) { 
	// 				selected.push(this.value); 
	// 			}
	// 		});
	// 		return selected;
	// 	},
	// 	ifAnySelected : function () {
	// 		flag = false
	// 		$(invoices.getSelector()).each(function() {
	// 			flag = flag || this.checked;
	// 		});
	// 		return flag;
	// 	},
	// 	changeCheckedStateOnInvoice : function (checkbox, value) {
	// 		checkbox.checked = value;
	// 		if(this.ifAnySelected()) {
	// 			QuickbookPayments.button.show();
	// 		} else {
	// 			QuickbookPayments.button.hide();
	// 		}
	// 	},
	// 	initEvents: function(ele) {
	// 		invoices.setModal(ele);



	// 		setTimeout(function() {

	// 			invoices.unselectAll();
	// 			$(invoices.getModal()).find('.invoices-select-all').attr('checked', false);

	// 			$(invoices.getModal()).on('change', 'input.invoice-checkbox', function(e) {
	// 				invoices.changeCheckedStateOnInvoice(this, this.checked);

	// 				if( !this.checked ) {
	// 					$(invoices.getModal()).find('.invoices-select-all').attr('checked', false)
	// 				}
	// 			});

	// 			console.log(invoices, ele);

	// 			$(invoices.getModal()).on('change', '.invoices-select-all', function() {
	// 				if(this.checked) {
	// 					invoices.selectAll();
	// 				} else {
	// 					invoices.unselectAll();
	// 				}
	// 			});
	// 		}, 300);

	// 	}
	// };

	// $('body').on('click', '.view-invoices', function(e) {

	// 	var self = this;

	// 	setTimeout(function() {
	// 		console.log(e, $('body').find('.modal.in'));

	// 		var modal = $('body').find('.modal.in');

	// 		if($(self).hasClass('multi_job')) {
	// 			modal = $(self).closest('.tab-content').find('.modal.in');
	// 		}

	// 		console.log( modal );

	// 		QuickbookPayments.button.initEvents(modal);
	// 		invoices.initEvents(modal);
	// 	}, 500);
	// });
})(jQuery)

// QuickbookPayments.button.initEvents();
// invoices.initEvents();

// END: Invoice Selection in Modal related code