var reloadBxSliderForVideo = function(element) {

	var container  = $('.tab-content .active');

	if(!JP.haveValue(element)) {
		element  = $(container).find('.comp-videos-slider');
	}


	$(element)
		.bxSlider({
			minSlides: 1,
			maxSlides: 1,
			moveSlides: 1,
			slideMargin: 0,
			captions: true,
			pager: false,
			responsive: true,
			infiniteLoop: false,
			hideControlOnEnd:true,
		});
};

$(document).ready(function(){
	$('.resource-slider').bxSlider({
		slideWidth: 140,
		minSlides: 1,
		maxSlides: 3,
		moveSlides: 1,
		slideMargin: 20,
		captions: true,
		pager: false,
		responsive: true,
		infiniteLoop: false,
		hideControlOnEnd:true,
		onSliderLoad: function(currentIndex){
			$(".resource-slider a").click(function(){
				var img = $(this).children("img").attr("src");
				$("#main-resource").attr("src", img);
			});
		}
	});

	var sliderCommonOptions = {
		slideWidth: 123,
		minSlides: 3,
  		maxSlides: 3,
  		moveSlides: 1,
		slideMargin: 10,
		captions: true,
		pager: false,
		responsive: true,
		infiniteLoop: false,
		hideControlOnEnd:true
	};

	$('.proposals-slider1').bxSlider(sliderCommonOptions);

	$('a[href="#parent-accepted-proposal"]').one('shown.bs.tab', function (e) {
	    $('.proposals-slider2').bxSlider(sliderCommonOptions);
	});

	$('a[href="#parent-rejected-proposal"]').one('shown.bs.tab', function (e) {
	    $('.proposals-slider3').bxSlider(sliderCommonOptions);
	});

	setTimeout(function() {

		reloadBxSliderForVideo();

	}, 500)

    //tooltip
  	$('[data-toggle="tooltip"]').tooltip();


  	/**
  	* @retun Calendar Focus Date
  	**/
  	var getDefaultDate = function(element) {

  		var dates = eval(scheduleDates);

  		if( JP.haveValue(element) && $(element).hasClass('multi_job') ) {
			dates = eval($(element).attr('dates-ref')) || [];
		}

  		var dd = moment().format('YYYY-MM-DD');

  		if( angular.isArray(dates) && dates.length > 0) {
  			dd = moment(dates[0].start_date_time).format('YYYY-MM-DD');
  		}

  		return dd;
  	};


  	/**
  	* @check is jon Scheduled or Not
  	**/
  	var isJobScheduleOnDate = function(today, element) {
		var dates = eval(scheduleDates);

		if( JP.haveValue(element) && $(element).hasClass('multi_job') ) {
			dates = eval($(element).attr('dates-ref')) || [];
		}

		var p = $.map(dates, function(item) {

			var start = moment(item.start_date_time).format('YYYY-MM-DD');
	        var end = moment(item.end_date_time).format('YYYY-MM-DD');

			// if( end != start ) {
			// 	// end = moment(end).subtract(1, 'days').format('YYYY-MM-DD');
			// }
  			if( today >= start && today <= end ) {
  				return item;
  			}
  		});

  		return (p.length > 0);
  	};


  	initCalendar = function (calendar) {

  		$.each($(calendar) , function(i, element) {
  			console.log( i, element );

  			/**
		  	* @show Fullcalendar
		  	**/
		  	$(element).fullCalendar({
		        header: {
		            left: 'prev,next',
		            center: 'title ',
		            right: ''
		        },

		        //fix weeks
		        fixedWeekCount: false,

		        //events
		        events: [],

		       	//default date set
		        defaultDate: getDefaultDate(element),

		       	//set background color of cell
		       	dayRender: function (date, cell) {
			        var today = moment(date).format('YYYY-MM-DD');



			        if( isJobScheduleOnDate(today, element) ) {
			        console.log('element', element);

			        	cell.addClass("day-change")
			        	var parnt = cell.parents('.fc-row');
			        	var c = parnt.find('thead .fc-day-top');

				        $.each(c, function(i, item) {
				        	if( $(item).attr('data-date') == today ) {
				        		$(item).find('span').css('color', '#fff');
				        	}
				        });

			            cell.css("background-color", "#428bca");
			        }
			    },
		    });
  		});




  	}

  	initCalendar(".tab-pane.active .calendar");

  	/*file resources slider*/
  	$(document).ready(function(){
		$('.resource_slider').bxSlider({
			slideWidth: 300,
			minSlides: 2,
			maxSlides: 2,
			moveSlides: 2,
			// infiniteLoop: false,
  	// 		hideControlOnEnd: true,
			slideMargin: 10
		});
	});
});