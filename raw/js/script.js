var loadingEnabled = false;

// Live chart function
var tmpWatt = 0;
var tmpTime = 0;
var tmpAverage = 100;
var tmpMin = 999;
var tmpMin2 = 999;
var tmpMaxLWValue = 0;
var tmpLWValue = 0;
var counter = 1;
var updown = "countUp";

function requestLiveData() {
    $.ajax({
        url: 'ajax.php?a=live',
        dataType: 'json',
        success: function(json) {
			//var interval = $('#settingsOverlay').data('liveinterval');
			var interval = 50;
			var shiftMax = 10*60000 / 1000;								//interval to 50
            var series = chart.series[0],
                shift = series.data.length > shiftMax; // shift if the series is longer than shiftMax

            // add the point
            var x = (new Date()).getTime();
            var y = json["raw"];
            //console.log(point);
            chart.series[0].addPoint([x, y], true, shift);
			
            // up/down indicator
            
			/*
			if(tmpWatt < parseInt(json["pwr"])){
            	updown = "countUp";
            }
            else if(tmpWatt == parseInt(json["pwr"])){
            	updown = "";
            }
            else
            {
            	updown = "countDown";
            }
            */
			
			
			tmpWatt = parseInt(json["pwr"]);
			
			tmpTime = 6000/tmpWatt;

			
			if (counter < 100){		//Eerste 100 loops een gemiddelde bepalen voor tmpAverage
				tmpAverage = Number(((tmpAverage*(counter-1)+y)/counter).toFixed(0));
			}
			else{					//Loop 101 t/m 600 (oneindig vaak dus) het gemiddelde bepalen door een Raw waarde als 1/200 deel (0,5%) mee te laten tellen.
				tmpAverage = Number(((tmpAverage*199 + y)/200).toFixed(1));
			}
			
			
			
			if (counter<600){		//Loop om eens per x maal min temp te resetten
				if(y < tmpMin2){
					tmpMin2 = y;
				}
				counter=counter+1;
			}
			else{					//reset na x maal de tmpMin naar laagste value van afgelopen x maal.
				tmpMin=tmpMin2;
				tmpMin2= 999;
				counter = 100;
				updown = "";
				}
			
				
			if(y < tmpMin){
            	tmpMin = y;
            }
		
			
			tmpMaxLWValue = Number(100+100*(((tmpAverage/tmpMin)-1)).toFixed(2));
			tmpLWValue = Number(100+100*(((tmpAverage/tmpMin)-1)*0.7)).toFixed(0);
			
			
			
			
            // update counter
            $('#wattCounter').html("<span class='"+updown+"'>Avg Raw: "+tmpAverage+"<br>Min Raw: "+tmpMin+"<br>Max LW: "+tmpMaxLWValue+"  |  safe LW (70%): "+tmpLWValue+"</span>");            
			
			
			// getMeter();
            
            // call it again after one second
            setTimeout(requestLiveData, interval);    
        },
        cache: false
    });
}
// Calculate costs/kwh function
function calculate(target, date){
		$('#kwhCounter').html("<span style='line-height:30px;font-style:italic;'>Loading…</span>");
		$('#cpkwhCounter').html("<span style='line-height:30px;font-style:italic;'>Loading…</span>");	
		return;
			
		$.ajax({
			url: 'ajax.php?a=calculate_'+target+'&date='+date,
			dataType: 'json',
			success: function( jsonData ) {
			
				// KWH and costs counter
					if($('input[name=dualcount]:checked').val() == 1)
					{
						$('#kwhCounter').html("<span>H: "+jsonData["kwh"]+" kWh<br>L: "+jsonData["kwhLow"]+" kWh<br>T: "+jsonData["kwhTotal"]+" kWh</span>");
						$('#cpkwhCounter').html("<span>H: € "+jsonData["price"]+" <br>L: € "+jsonData["priceLow"]+" <br>T: € "+jsonData["priceTotal"]+"</span>");
					}
					else
					{
						$('#kwhCounter').html("<span style='line-height:30px;'>"+jsonData["kwh"]+" kWh</span>");
						$('#cpkwhCounter').html("<span style='line-height:30px;'>€ "+jsonData["price"]+"</span>");
					}				
			},
			cache: false
		});	
}				

function calculateRange(min,max){

			$('#range').html("<span>S: " + Highcharts.dateFormat('%d-%m-%Y %H:%M:%S', min) +"<br>E: " + Highcharts.dateFormat('%d-%m-%Y %H:%M:%S', max) + "</span>");	
									
			$.ajax({
				url: 'ajax.php?a=calculate_range&stime='+Math.floor(min/1000)+'&etime='+Math.floor(max/1000),
				dataType: 'json',
				success: function( jsonData ) {
					if($('input[name=dualcount]:checked').val() == 1)
					{
						$('#kwhCounter').html("<span>H: "+jsonData["kwh"]+" kWh<br>L: "+jsonData["kwhLow"]+" kWh<br>T: "+jsonData["kwhTotal"]+" kWh</span>");
						$('#cpkwhCounter').html("<span>H: € "+jsonData["price"]+" <br>L: € "+jsonData["priceLow"]+" <br>T: € "+jsonData["priceTotal"]+"</span>");
					}
					else
					{
						$('#kwhCounter').html("<span style='line-height:30px;'>"+jsonData["kwh"]+" kWh</span>");
						$('#cpkwhCounter').html("<span style='line-height:30px;'>€ "+jsonData["price"]+"</span>");
					}				
										
				}
										
			});
}

function getMeter() {
			$.ajax({
				url: 'ajax.php?a=get_meter',
				dataType: 'json',
				success: function( jsonData ) {
					if($('input[name=dualcount]:checked').val() == 1)
					{
						if ( jsonData["islow"]  == '0' ) { 
							$('#meter').html("<span class='isLow'>H: "+jsonData["meter"]+" kWh<br></span><span>L: "+jsonData["meterl"]+" kWh</span>");
						} else {
							$('#meter').html("<span>H: "+jsonData["meter"]+" kWh<br></span><span class='isLow'>L: "+jsonData["meterl"]+" kWh</span>");
						}
					}
					else
					{
						$('#meter').html("<span style='line-height:30px;'>"+jsonData["meter"]+" kWh</span>");
					}				
										
					setTimeout(getMeter, 60 * 1000);    
				}
			});
}
		
// Create chart function
function createChart(target, date){

			// Generate loading screen
			if(loadingEnabled)
			{
				historychart.showLoading();
			}
			else
			{
				loadingEnabled = true;
			}				

			$.ajax({
				url: 'SmiGjob.php',
				success: function(result) { dummy = 0;}
			});
			
							
			$.ajax({
				url: 'ajax.php?a='+target+'&date='+date,
				dataType: 'json',
				success: function( jsonData ) {

					// If invalid data give feedback
					if(jsonData["ok"] == 0)
					{
						$('#message').text(jsonData["msg"]);
						$('#overlay').fadeIn();
					}
					
						// Format data
						jsDate = jsonData["start"].split("-");
						year = jsDate[0];
						month = jsDate[1]-1;
						day = jsDate[2]-0;
						hour = jsDate[3]-0;
						minute = jsDate[4]-0;
						
						var start = (new Date(year, month, day, hour, minute)).getTime();
						var approximation = "average";

						if(target == 'day')
						{
							var title = 'Dagverbruik';
							var type = 'areaspline';
							var serieName = 'Watt';
							var yTitle = {
				                text: 'Watt',
				                margin: 40
				            };			
							var rangeSelector = false;
							var navScroll = true;
							var pointInterval = 60 * 1000;
							var tickInterval = null;
							var plotLines = null;											
							var buttons = [{
											type: 'hour',
											count: 1,
											text: '1u'
										}, {
											type: 'hour',
											count: 12,
											text: '12u'
										}, {
											type: 'day',
											count: 1,
											text: 'dag'
										}];
						}
						else if(target == 'week')
						{
							var title = 'Weekverbruik';
							var type = 'areaspline';
							var serieName = 'Watt';
							var yTitle = {
				                text: 'Watt',
				                margin: 40
				            };					
							var rangeSelector = true;
							var navScroll = true;
							var pointInterval = 60 * 1000;
							var tickInterval = null;
							var plotLines = [{
								value: start + (24 * 60 * 60 * 1000),
								width: 1, 
								color: '#c0c0c0'
							},{
								value: start + (2 * 24 * 60 * 60 * 1000),
								width: 1, 
								color: '#c0c0c0'
							},{
								value: start + (3 * 24 * 60 * 60 * 1000),
								width: 1, 
								color: '#c0c0c0'
							},{
								value: start + (4 *24 * 60 * 60 * 1000),
								width: 1, 
								color: '#c0c0c0'
							},{
								value: start + (5 * 24 * 60 * 60 * 1000),
								width: 1, 
								color: '#c0c0c0'
							},{
								value: start + (6 * 24 * 60 * 60 * 1000),
								width: 1, 
								color: '#c0c0c0'
							}];										
							var buttons = [{
											type: 'hour',
											count: 1,
											text: '1u'
										}, {
											type: 'hour',
											count: 12,
											text: '12u'
										}, {
											type: 'day',
											count: 1,
											text: 'dag'
										}, {
											type: 'week',
											count: 1,
											text: 'week'
										}];
						}
						else if(target == 'month')
						{
							var title = 'Maandverbruik';
							var type = 'column';
							var serieName = 'Watt';
							var yTitle = {
				                text: 'Watt',
				                margin: 40
				            };					
							var rangeSelector = false;
							var navScroll = false;
							var pointInterval = 60 * 1000;
							var tickInterval = null;
							var plotLines = [{
								value: start + (7 * 24 * 60 * 60 * 1000),
								width: 1, 
								color: '#c0c0c0'
							},{
								value: start + (14 * 24 * 60 * 60 * 1000),
								width: 1, 
								color: '#c0c0c0'
							},{
								value: start + (21 * 24 * 60 * 60 * 1000),
								width: 1, 
								color: '#c0c0c0'
							},{
								value: start + (28 *24 * 60 * 60 * 1000),
								width: 1, 
								color: '#c0c0c0'
							}];										
						}
						else if(target == 'year')
						{
							var title = 'Jaarverbruik';
							var type = 'column';
							var serieName = 'Watt';
							var yTitle = {
				                text: 'Watt',
				                margin: 40
				            };					
							var rangeSelector = true;
							var navScroll = true;
							var pointInterval = 60 * 1000;
							var tickInterval = null;
							var plotLines = null;
							var buttons = [];
						}
												
						
						// Parse values to integers
						data = jsonData["val"].split(",");
						for(var i=0; i<data.length; i++) { data[i] = parseFloat(data[i], 10); } 

						// Create the chart
						historychart = new Highcharts.StockChart({
							chart: {
								renderTo : 'history',
								type: type,			
								events: {
									load: function () {
                       
											var min = this.xAxis[0].getExtremes().min,
												max = this.xAxis[0].getExtremes().max;
											
											calculateRange(min,max);
											//requestLiveData();
											//getMeter();
									}
								}
							},			
							credits: {
								enabled: false
							},
							title: {
								text : title
							},	
							yAxis: {
								showFirstLabel: false,
								title: yTitle
							},
							xAxis: {
								type: 'datetime',
								tickInterval: tickInterval,
								plotLines: plotLines,
								events: {
									afterSetExtremes: function () {
										var min = this.min,
											max = this.max;
										
										calculateRange(min,max);
										//getMeter();
									}
								}
							},	
							rangeSelector: {
								selected: 3,
								enabled: rangeSelector,
								buttons: buttons
							},							
							navigator: {
								enabled: navScroll
							},									
							scrollbar: {
								enabled: navScroll
							},

							plotOptions: {
									series: {
										cursor: 'pointer',
										//pointWidth: 15,
										point: {
											events: {
												click: function() {
													var dt = this.category;
													var chart_type = $('#history').data('chart');
													if (chart_type == 'month') {
														var target = "day";
													} else if (chart_type == 'week') {
														var target = "day";
													} else if (chart_type == 'Live') {
														var target = "day";
													} else if (chart_type == 'day') {
														var target = "day";
													}
													date = $.datepicker.formatDate("yy-mm-dd", new Date(dt));
													$('#datepicker').datepicker('setDate', date);
													$('#history').data('chart', target);
													createChart(target, date);
												}
											}
										}
									}},

							series : [{
								name : serieName,
								turboThreshold: 5000,
								data : data ,
								pointStart: start,
				            	pointInterval: pointInterval,
								dataGrouping: {
									approximation: approximation 
								},
								tooltip: {
									valueDecimals: 2
								}
							}],
							dataGrouping: {
								enabled: false
							}
						});	
						
						calculate(target, date);											
						
				},
    			cache: false
			});
			
						
}		

			
$(document).ready(function() {

	// Dialogs (alerts)
	$('#closeDialog').click(function(){
		$('#overlay').hide();
	});
		
	// Settings
	$('#showSettings').click(function(){
		$('#settingsOverlay').slideDown();
	});
	$('#hideSettings').click(function(){
		$('#settingsOverlay').slideUp(function(){
			var dualcnt = $('input[name=dualcount]:checked').val();
			if(dualcnt != $('#settingsOverlay').data('dualcount'))
			{
				$('input[name=dualcount]').not(':checked').attr('checked', true);
				if($('#settingsOverlay').data('dualcount') == 1)
				{
					$('.cpkwhlow').show();
				}
				else
				{
					$('.cpkwhlow').hide();
				}
			}		
		});		
	});
	
	$('input[name=dualcount]').change(function(){
		var dualcnt = $('input[name=dualcount]:checked').val();
		if(dualcnt == 1)
		{
			$('.cpkwhlow').show();
		}
		else
		{
			$('.cpkwhlow').hide();
		}
	});
		
	$('#saveSettings').click(function(){
		$.ajax({
			url: 'ajax.php?a=saveSettings',
			type: 'POST',
			dataType: 'json',
			data: $('#settingsOverlay form').serialize(),
			success: function( data ) {

				$('#settingsOverlay').slideUp('fast', function(){
					$('#settingsOverlay input[type=password]').val('');
				});
				
				if($('#settingsOverlay').data('dualcount') != $('input[name=dualcount]:checked').val())
				{
					$('#settingsOverlay').data('dualcount', $('input[name=dualcount]:checked').val());	
					var chart = $('#history').data('chart');
					//createChart(chart, $('#datepicker').val());					
				}
				$('#settingsOverlay').data('liveinterval', $('select[name=liveinterval]').val());								

				$('#message').text(data["msg"]);
				$('#overlay').fadeIn();			
			}
		});			
		return false;
	});	
	
	// Show chart
	$('.showChart').click(function(){
		var chart = $(this).data('chart');
		$('.chart').hide();
		$('.'+chart).show();
		
		$('.btn li').each(function(){
			$(this).removeClass('selected');
		});
		$(this).parent().addClass('selected');
		$('#history').data('chart', chart);
		
		if(chart != 'live')
		{
			createChart(chart, $('#datepicker').val());		
		}
		//console.log(chart);
	});
	
	
	//Highcharts options
	Highcharts.setOptions({
		global: {
			useUTC: false
		},	
		lang: {
			decimalPoint: ',',
			months: ['Januari', 'Februari', 'Maart', 'April', 'Mei', 'Juni', 'Juli', 'Augustus', 'September', 'Oktober', 'November', 'December'],
			shortMonths: ['Jan', 'Feb', 'Mrt', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'],
			weekdays: ['Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag']
		}			
	});
	
	// Live chart
    chart = new Highcharts.Chart({
        chart: {
            renderTo: 'live',
            defaultSeriesType: 'spline',
            events: {
                load: function () {
					requestLiveData();
					getMeter();
				}
            }
        },         

		colors: ['#2f7ed8', ' ,#666666'],

		credits: {
			enabled: false
		},
		legend: {
			enabled: false
		},		      
        title: {
            text: 'RAW values'
        },
        xAxis: {
            type: 'datetime',
            tickPixelInterval: 150,
            minRange: 60 * 1000
        },
        yAxis: {
			showFirstLabel: false,
            minPadding: 0.2,
            maxPadding: 0.2,
            title: {
                text: 'Light intensity',
                margin: 40
            }
        },
        series: [{
            name: 'Light intensity',
            data: [],
		    marker: {
				enabled: false,
				symbol: 'circle',
				radius: 1
			}
        }



		
		],
		exporting: {
			enabled: false
		}		
    });  
	
		
	// Datepicker
	$('#datepicker').datepicker({
		inline: true,
		dateFormat: 'yy-mm-dd',
		maxDate: new Date(),
		showOn: 'focus',
		//changeMonth: true,
		//changeYear: true,	
		firstDay: 1,	
		monthNames: ['Januari', 'Februari', 'Maart', 'April', 'Mei', 'Juni', 'Juli', 'Augustus', 'September', 'Oktober', 'November', 'December'],
        monthNamesShort: ['jan', 'feb', 'maa', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'],
        dayNames: ['zondag', 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag'],
        dayNamesShort: ['zon', 'maa', 'din', 'woe', 'don', 'vri', 'zat'],
        dayNamesMin: ['zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'],
		onSelect: function(date, inst){
		
			
			var target = $('#history').data('chart');			
			createChart(target, date);


		}		
	});
			
	      
$('a#next').click(function () {
   prev_next(1);
return false;
});

$('a#previous').click(function () {
   prev_next(-1);
return false;
});

});

function prev_next(type) {
  var chart_type = $('li.selected .showChart').data('chart');
  
  var $picker = $("#datepicker");                                                                              
  var date=new Date($picker.datepicker('getDate'));

  if (type!==1) type=-1;

  switch (chart_type) {
  
    case 'week': 
       date.setDate(date.getDate()+(7*type));
       break;
    case 'month':
       date.setMonth(date.getMonth()+(1*type));
       break;
    case 'year':
       date.setFullYear(date.getFullYear()+(1*type));
       break;
    default:
       date.setDate(date.getDate()+(1*type));
       break;
   } 
   $picker.datepicker('setDate', date);
   var target = $('#history').data('chart');                                                                
   
   date = $picker.datepicker({ dateFormat: 'yy-mm-dd' }).val();
   
   createChart(target, date);                                           
}
