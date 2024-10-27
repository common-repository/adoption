/* Author: Chris Aprea

*/

jQuery(document).ready(function($){
	
	// run the spinner
	var opts = {
		lines: 13, 				// The number of lines to draw
		length: 17, 			// The length of each line
		width: 9, 				// The line thickness
		radius: 29, 			// The radius of the inner circle
		corners: 1, 			// Corner roundness (0..1)
		rotate: 0, 				// The rotation offset
		color: '#aaa', 			// #rgb or #rrggbb
		speed: 1, 				// Rounds per second
		trail: 45, 				// Afterglow percentage
		shadow: false, 			// Whether to render a shadow
		hwaccel: false, 		// Whether to use hardware acceleration
		className: 'spinner', 	// The CSS class to assign to the spinner
		zIndex: 2e9, 			// The z-index (defaults to 2000000000)
		top: 'auto', 			// Top position relative to parent in px
		left: 'auto' 			// Left position relative to parent in px
	};
	var target = document.getElementById('plot');
	var spinner = new Spinner(opts).spin(target);
	
	$.ajax({
		type 		: 'POST',
		url 		: ajaxurl,
		dataType 	: 'json',
		data : {
			'action'	: 'adoption-get-users',
			'type'		: 'load'
		},
		success : function(data) {
		
			if( data.error == null ){
			
				render_chart( data );
			
			}
			else{
			
				error_handler();
			
			}

		},
		error : function(XMLHttpRequest, textStatus, errorThrown) {
			
			error_handler();
			
		}

	});
	
    $(".select_range select").change(function(){

		var spinner = $('.spinner');
	
		$('#plot').empty();
		
		$('#plot').prepend( spinner );

		$('.spinner').show();
	
		$.ajax({
			type 		: 'POST',
			url 		: ajaxurl,
			dataType 	: 'json',
			data : {
				'action'	: 'adoption-get-users',
				'type'		: $(this).val()
			},
			success : function(data) {
			
				if( data.error == null ){
				
					render_chart( data );
				
				}
				else{
				
					error_handler();
				
				}
			
			},
			error : function(XMLHttpRequest, textStatus, errorThrown) {
				
				error_handler();
				
			}

		});

	});
	
	function render_chart( data ){
	
		$('.spinner').hide();
	
		var s1 = data.registrations;
		// Can specify a custom tick Array.
		// Ticks should match up one for each y value (category) in the series.
		var ticks = data.iterations;
		 
		var plot1 = $.jqplot('plot', [s1], {
			seriesColors: ['#8DB9CC'],
			axesDefaults: {
				tickOptions: {
					showMark: false,
				},
			},
			// The "seriesDefaults" option is an options object that will
			// be applied to all series in the chart.
			seriesDefaults:{
				renderer: $.jqplot.BarRenderer,
				rendererOptions: {
					fillToZero: true,
					barMargin: 3,
					highlightMouseOver: true,
					highlightMouseDown: false,
					highlightColors: '#ffae00',
				},
				shadow: false
			},
			axes: {
				// Use a category axis on the x axis and use our custom ticks.
				xaxis: {
					renderer: $.jqplot.CategoryAxisRenderer,
					ticks: ticks,
					tickOptions: {
						showGridline: false
					},
					autoscale: true,
					numberTicks: 3
				},
				yaxis: {
					autoscale: true,
					numberTicks: 3
				}
			},
			grid: {
				shadow: false,
				borderWidth: 0,
				gridLineColor: '#d9d9d9',
				background: '#ffffff'
			},
			highlighter: {
				show: true,
				showTooltip: true,
				tooltipLocation: 'n',
				tooltipOffset: 0,
				tooltipAxes: 'y',
				showMarker: false
			},
			cursor: {
				show: false
			}

		});

	}
	
	function error_handler(){
	
		$('.spinner').hide();
		
		var error_span = document.createElement('span');
		
		$(error_span).addClass('adoption_error');
		
		$(error_span).html(adoption_error_message);
		
		$('#plot').prepend(error_span);
	
	}

});