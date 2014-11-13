<?php

/*
	Initialization
*/

// Basic Variables
$cityName = $_GET["cityName"];
$state = $_GET["locality"];
$country = $_GET["country"];
$lat = $_GET["lat"];
$lng = $_GET["lng"];
$units = $_GET["units"];
$oneway = $_GET["oneway"];
$onewayCompass = $_GET["compass"]; 

// Scrub variables 
$cityName = clean($cityName);
$state = clean($state);
$country = clean($country);
$lat = clean($lat);
$lng = clean($lng);
$units = clean($units);

// Import metascore functionality
require_once("meta.php");

// Acquire API credentials from an ini file.
$ini_array = parse_ini_file("bikereport.ini", true);

$weatherAPIKey = explode(', ', $ini_array['api_keys']['weather']);
$weatherAPIKey = $weatherAPIKey[0];
$camAPIKey = explode(', ', $ini_array['api_keys']['cam']); 
$camAPIKey = $camAPIKey[0];
$radarKey = explode(', ', $ini_array['api_keys']['radar']);
$radarKey = $radarKey[0];

/*
	Functions
*/

function makeHourlyReport($json, $units, $hoursToReport)
{
	$unitChoices = unitChoice($units);	// temp == [1], speed == [2]
	$output = "";

	// Summary for the next few hours
	$output .= "<div class='summaryTable' id='dailyTitle'>\n";

	$output .= "<div class='summaryRow' style='width: 80%;'>\n";
	$output .= "<div class='summaryTitleCell' id='dailySummary'>\n";
	$output .= "<h3><b>In the next few hours:  " . $json->hourly->summary . "</b></h3>\n";
	$output .= "</div><!--hourly summary dayCell-->\n";
	$output .= "</div><!--hourly summary summaryRow-->\n";
	$output .= "</div><!--hourly summary summaryTable-->\n";

	$output .= "<div class='summaryTable' id='daily'>\n";
	$output .= "<div class='summaryRow' style='width: 80%'>\n";

	foreach($hoursToReport as $hour)
	{
		$output .= "<div class='hourCell' style='padding: 10px;'>\n";
		$output .= "<h2>" . $hour . "</h2>" . " hours from now: ";

		//metascore
		$output .= "<h2>" . meta($json, $units, "h{$hour}") . "%</h2><br/><br/>\n";

		$output .= "<img src='graphics/icons/" . $json->hourly->data[$hour]->icon . ".png' alt='" . $hour . " hours from now: " . $json->hourly->data[$hour]->icon . "' id='weatherIcon' height='50' width='50'/><br/>";

		$output .= "<i>" . $json->hourly->data[$hour]->summary . "</i><br/>\n";

		$output .= "<h4>Temp: " . round($json->hourly->data[$hour]->temperature) . $unitChoices[1] . "&deg;</h4>\n";
		$output .= "<h4>Feels like: " . round($json->hourly->data[$hour]->apparentTemperature) . $unitChoices[1] . "&deg;</h4>\n";
		$output .= "<h4>Wind: " . round($json->hourly->data[$hour]->windSpeed) . 	$unitChoices[2] . " / " . compass($json->hourly->data[$hour]->windBearing) . "</h4>";

		$output .= "</div><!--day dayCell-->\n";
	}

	$output .= "</div><!--day summaryRow-->\n";
	$output .= "</div><!--day summaryTable-->\n";
	$output .= "<br/><br/>\n";

	return $output;
}

function getRadarMap($radarKey, $lat, $lng)
{
	// Provides a radar
	$radarHeight = 400;
	$radarWidth = 600;

	$radius = 100;

	$radarURL = "http://api.wunderground.com/api/";
	$radarURL .= $radarKey;
	$radarURL .= "/radar/image.gif?centerlat=" . $lat;
	$radarURL .= "&#38;centerlong=" . $lng;
	$radarURL .= "&radius=" . $radius;
	$radarURL .= "&width=" . $radarWidth;
	$radarURL .= "&height=" . $radarHeight;
	$radarURL .= "&newmaps=1";

// Provides a map 
	$radarURL = "http://api.wunderground.com/api/" . $radarKey . "/radar/image.gif?centerlat=" . $lat . ";centerlon=" . $lng . "&radius=100&width=280&height=280&newmaps=1";

$radarURL = "http://api.wunderground.com/api/" . $radarKey . "/radar/image.gif?centerlat=" . $lat . "&centerlon=" . $lng . "&radius=20&width=280&height=280&newmaps=1";



	return $radarURL;
}

function makeCamArray($data, $camAPIKey)
{
	// MAKE ENDPOINT URL
	$lat = $data->latitude;
	$lng = $data->longitude;

	$endpointCam = "http://api.webcams.travel/rest?method=wct.webcams.list_nearby&lat=";
	$endpointCam .= $lat;
	$endpointCam .= "&lng=";
	$endpointCam .= $lng;
	$endpointCam .= "&devid=";
	$endpointCam .= $camAPIKey;
	$endpointCam .= "&format=json";

	// menu: lat/lng: 28.331 / 80.6131
	// search: lat/lng: 28.3200067 / -80.6075513

	// setup curl to make a call to the endpoint
	$session = curl_init($endpointCam);
	// indicates that we want the response back
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	// exec curl and get the data back
	$camData = curl_exec($session);

	// remember to close the curl session once we are finished retrieveing the data
	curl_close($session);

	// decode the json data to make it easier to parse the php
	$jsonCam = json_decode($camData);
	if($search_results === NUL) die('Error parsing json');

	//var_dump($jsonCam);	// TEST

	$camArray = array();	// An array of webcam URLs for the given area
	for($i = 0; $i < 10; $i++)
	{
		if($jsonCam->webcams->webcam[$i]->toenail_url != '')
		{	
			array_push($camArray, $jsonCam->webcams->webcam[$i]->toenail_url);
		}
	}
/*
	// TESTING
	foreach ($camArray as $cam)
	{
		print "test: " . $cam . "\n";
	}
*/
	return $camArray;
}

function timeStamp($data)
{
	$currentTime = $data->currently->time;	// Unix time stamp for local time
	$localTime = $currentTime + ($data->offset * 3600);	// Adjust time using hours offset
	return gmdate('D M\ jS, g:ia', $localTime);	// Return a nice string
}

function periodOfDay($data)
{
	// returns 0 for daytime, 1 for dusk, 2 for night, and 3 for dawn
	$localTime = $data->currently->time + (3600 * $data->offset);
	$sunriseToday = $data->daily->data[0]->sunrisetime + (3600 * $data->offset);
	$sunsetToday = $data->daily->data[0]->sunsetTime + (3600 * $data->offset);

	$ret = 0;	// Default; daytime.

	// daytime: local/rise/set: 1415 799469 / -18000 / 1415 811422
	// rise is negative, set is ahead

	// unit test data
	// local / sunrise / sunset / ret

	// Day: sunrise is negative (or zero?) and sunset > local
	// day (1:54pm - toronto)
	// 1415800456 / -18000 / 1415811422 / 0
	// day (8:58am - Honolulu)
 	// 1415782712 / -36000 / 1415814720 / 0

	// night: either both are positive, OR sunrise is 0??
	// night (6:52pm - london)
	// 1415818378 / 0 / 1415809043 / -1 
	// night (3:48am-tokyo)
	// 1415850538 / 32400 / 1415896665 / 2 

	// dawn: 

	// Dusk: (local < sunset) && ((local + 30 mins) > sunset)

	// 9:16am in apia island
	// 1415 870184 / 50400 / 1415 907259 / 2

	// dawn? - (palikir island - 6:14am)
	//1415859283 / 39600 / 1415902051 / 2

	// Two circumstances it could be night.  Before midnight, both sunrise and sunset are behind us
	// After midnight, both sunrise and sunset are ahead of us
	if($sunriseToday < 0 && $sunsetToday > 0)
	{
		// Day.  Sunrise is a large negative, like -18000.
		$ret = 0;
	}
 	elseif((($localtime < $sunriseToday) && ($localtime < $sunsetToday)) || (($localTime > $sunsetToday) && ($localtime > $sunsetToday))  )
	{
		// Night
		$ret = 2;
	}
	elseif($localTime  < ($sunriseToday + 1800))
	{
		// Dawn
		$ret = 3;
	}
	elseif(($localTime  < $sunsetToday) && ($localTime  > ($sunsetToday - 1800) ) )
	{
		// Dusk
		$ret = 1;
	}
	else
	{
		// Should not get here.
		$ret = -1;
	}

	// Simple fix for now:
	if($localTime > $sunriseToday && $localTime < $sunsetToday)
	{
		// Day
		$ret = 0;
	}
	else
	{
		// night.
		$ret = 2;
	}


	//print "local/rise/set/ret: " . $localTime . " / " . $sunriseToday . " / " . $sunsetToday . " / " . $ret . "\n\n";
	return $ret;
}

function psaImage($imageName, $alt)
{
	// Returns the <img> tag for a PSA image for the page, whose name is $imageName
	// Functionalizing the process allows for the later possibility of rotating images (e.g. from a hard-coded array
	// or external source) on a regular basis.
	// Assumes that $imageName is the name of a valid image in /graphics/psa/, and that $alt is a string of descriptive text.
	return "<img src='graphics/psa/" . $imageName . "' alt='" . $alt . "' id='psaImage' width='100%' height='100%' />";
//return "<span class='psaSpan' style='background: graphics/" . . "'></span>\n";
}

function checkAlerts($data)
{
	// Returns an array containing [0]: lights-on alerts, [1]: weather alerts

	// Check if it's between 30 mins before dusk and 30 mins after dawn--the legal definition of 'night' for driving/road purposes in Ontario
	//$currentTime = $data->currently->time;
	$icon = $data->currently->icon;
	$lights;
	$weather;
	$alerts = array();

	$period = periodOfDay($data);	// 0 for daytime, 1 for dusk, 2 for night, and 3 for dawn

	if($period == 2)    
	{
		// Then it's nighttime, before dawn.  
		$lights = "use lights (night)";
	}
	if($period == 3)
	{
		// Then it's nighttime, after dusk.
		$lights = "use lights (dawn)";
	}
	if($period == 1)
	{
		$lights = "use lights (dusk)";
	}

	// Check for fog--append to any existing 
	if($icon == "fog")
	{
		if($lights != "")
		{
			$lights .= ", fog.";	
		}
		else
		{
			$lights = "use Lights (fog)";
		}
	}

	$weather = "";	// Placeholder until weather alerts are implemented

	array_push($alerts, $lights, $weather);

	return $alerts;
}

function thermometer($units)
{
	$unitChoices = unitChoice($units);	// temp == [1], speed == [2]
	$thermometerOutput = "
	<script>
/**
 * Thermometer Progress meter.
 * This function will update the progress element in the \"thermometer\"
 * to the updated percentage.
 * If no parameters are passed in it will read them from the DOM
 *
 * @param {Number} goalAmount The Goal amount, this represents the 100% mark
 * @param {Number} progressAmount The progress amount is the current amount
 * @param {Boolean} animate Whether to animate the height or not
 *
 */
function thermometer(id, goalAmount, progressAmount, animate) {
    \"use strict\";

    var \$thermo = \$(\"#\"+id),
        \$progress = \$(\".progress\", \$thermo),
        \$goal = \$(\".goal\", \$thermo),
        percentageAmount,
        isHorizontal = \$thermo.hasClass(\"horizontal\"),
        newCSS = {};

    goalAmount = goalAmount || parseFloat( \$goal.text() ),
    progressAmount = progressAmount || parseFloat( \$progress.text() ),
    percentageAmount =  Math.min( Math.round(progressAmount / goalAmount * 1000) / 10, 100); //make sure we have 1 decimal point

    //let\"s format the numbers and put them back in the DOM
    \$goal.find(\".amount\").text( goalAmount +";

    $thermometerOutput .= "\"" . $unitChoices[1] . "\" );";
    $thermometerOutput .= "\$progress.find(\".amount\").text( progressAmount +";
    $thermometerOutput .= "\"" . $unitChoices[1] . "\" );";

	$thermometerOutput .= "
    //let\"s set the progress indicator
    \$progress.find(\".amount\").hide();

    newCSS[ isHorizontal ? \"width\" : \"height\" ] = percentageAmount + \"%\";

    if (animate !== false) {
        \$progress.animate( newCSS, 1200, function(){
            \$(this).find(\".amount\").fadeIn(500);
        });
    }
    else {
        \$progress.css( newCSS );
        \$progress.find(\".amount\").fadeIn(500);
    }
}

\$(document).ready(function(){
    thermometer(\"thermo1\");
});
</script>
	";
	return $thermometerOutput;
}

function bookmarkMe()
{
	$bookmarkMeOutput = "
		<script>
		// Credit: http://stackoverflow.com/questions/10033215/add-to-favorites-button
    	$(function() {
	        $('#bookmarkme').click(function() {
	            if (window.sidebar && window.sidebar.addPanel) { // Mozilla Firefox Bookmark
	                window.sidebar.addPanel(document.title,window.location.href,'');
	            } else if(window.external && ('AddFavorite' in window.external)) { // IE Favorite
	                window.external.AddFavorite(location.href,document.title); 
	            } else if(window.opera && window.print) { // Opera Hotlist
	                this.title=document.title;
	                return true;
	            } else { // webkit - safari/chrome
	                alert('Press ' + (navigator.userAgent.toLowerCase().indexOf('mac') != - 1 ? 'Command/Cmd' : 'CTRL') + ' + D to bookmark this page.');
	            }
	        });
    	});
		</script>";
	return $bookmarkMeOutput;
}

function startUntilBody($cityName, $lat, $lng)
{
	$startUntilBodyOutput = "<!DOCTYPE html>\n<html>
	<head>
		<title>The " . $cityName . " Bike Report</title>\n
	 	<meta charset='UTF-8'>

		<script src='//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js'></script>
		<!-- Tiny Carousel for webcams-->
		<script type='text/javascript' src='jquery/jquery.tinycarousel.min.js'></script>
		<!-- Rotation script for arrow representing wind direction -->
		<script type='text/javascript' src='http://jqueryrotate.googlecode.com/svn/trunk/jQueryRotate.js'></script>
		<!-- Google line graph -->
		<script type='text/javascript' src='https://www.google.com/jsapi'></script>

		<link href='http://fonts.googleapis.com/css?family=Droid+Serif%7CCrimson+Text' rel='stylesheet' type='text/css'>
		<link rel='stylesheet' href='styles/tinycarousel.css' type='text/css' media='screen'/>

		<!--Main CSS-->
		<link rel='stylesheet' href='styles/bikeStyles.css' type='text/css' media='screen' /> 

		<script>
			$(window).load(function()
				{
					$('#slider1').tinycarousel({ 
						interval: true
					, 	bullets: true 
					});
						var slider1 = $('#slider1').data('plugin_tinycarousel');

						    // The start method starts the interval.
						    $('#startslider').click(function()
						    {
						        slider1.start();
						        return false;
						    });

						    // The stop method stops the interval.
						    $('#stopslider').click(function()
						    {
						        slider1.stop();
						        return false;
					});
				});
		</script>
";
	return $startUntilBodyOutput;
}

function speedUnits($units)
{
	// Returns appropriate speed display string, given $units.
	if($units == "CA")
	{
		return "km/hr";
	}
	if($units == "US")
	{
		return "mph";
	}
	if($units == "UK")
	{
		return "mph";
	}
	// Should not get here.  Error.
	return -1;
}

function tempUnits($units)
{
	// Returns appropriate temperature display string, given $units.
	if($units == "CA")
	{
		return "C";
	}
	if($units == "US")
	{
		return "F";
	}
	if($units == "UK")
	{
		return "C";
	}
	// Should not get here.  Error.
	return -1;
}

function rotateArrow($windBearing)
{
	$rotateArrowOutput = "<script>";
	$rotateArrowOutput .= "$(document).ready(function(){";
 	$rotateArrowOutput .= "$('#windArrow').rotate(" . ($windBearing + 180). ");";
	$rotateArrowOutput .= "});";
	$rotateArrowOutput .= "</script>";

	$rotateArrowOutput .= "<img src='graphics/redArrow.png' alt='Wind Direction Arrow' id='windArrow'/><br/>";

	return $rotateArrowOutput;
}

function makeGraph($points)
{
	$graphOutput = "<script>
      google.load('visualization', '1', {packages:['corechart']});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Day', 'Score'],
          ";
    // Pop off first element of $ponits--it's the starting weekday of the chart
    $startDay = array_shift($points);

    // Build an array of weekday names by reordering an extant array starting at sunday
	$weekdays = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");

	// Reorder the array so today is first 
	for($i = 0; $i < $startDay; $i++)
	{
		$replace = array_shift($weekdays);
		$weekdays[] = $replace;
	}

    // Foreach over remaining $points and add to the graph array
    $i = 0;
    foreach($points as $point)
    {
    	$graphOutput .= "['" . $weekdays[$i] . "', " . $point . "],";
    	$i++;
    }
    // Pop the last char off of $graphOutput so there's no trailing comma
    $graphOutput = substr($graphOutput, 0, -1);

    	/* Example
          ['2004',  1000],
          ['2005',  1170],
          ['2006',  660],
          ['2007',  103]
		*/
    $graphOutput .= "]);

        var options = {
        title: 'Cycling Conditions This Week',
        fontName: 'Crimson+Text',
        titleTextStyle: {color: '#FAEBD7'},
        series: { 0:{ color: '#FAEBD7'} },
        lineWidth: 7,
        curveType: 'function',
        backgroundColor:{fill:'#72A0C1', stroke:'#F0F8FF'},
        chartArea:{backgroundColor:'#5D8AA8', width:'100%'},
        vAxis:{ maxValue: 100, minValue: 0, textStyle:{color:'#FAEBD7'}, gridlines:{color:'#7B9DB5'} },
        hAxis:{ maxValue: 100, minValue: 0, textStyle:{color:'#FAEBD7'} },
       	legend:{position: 'none'},
        };

        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
       
        chart.draw(data, options);
      }
    </script>";
	return $graphOutput;
}

function unitChoice($units)
{
	// Returns variables for $endpoint and temp/speed terminology; assumes a valid unit choice.
	// Return format: $ret($endpoint, $tempSuffix, $speedSuffix)
	$ret = array();

	if($units == "CA")
	{
		array_push($ret, "?units=ca", "C", "km/hr");
	}
	if($units == "US")
	{
		array_push($ret, "?units=us", "F", "mph");
	}
	if($units == "UK")
	{
		array_push($ret, "?units=uk", "C", "mph");
	}
	if($units != "CA" && $units != "US" && $units != "UK")
	{
		// Should not get here.  Return an error if input is incorrect.
		return -1;
	}
	return $ret;
}

function convertSpeed($speed)
{
	// Converts speed (and distance) from mph to km/hr
	return $speed * 1.6;
}

function convertTemp($temperature)
{
	// Converts temperature from F to C
	return ($temperature - 32) * (5/9);
}

function clean($str)
{
	// Returns a tidied-up string to prevent script injection, CX attacks, etc.; takes in a raw-input string, $str.
	$str = mb_convert_encoding($str, "UTF-8", "UTF-8");
	$str = htmlentities($str, ENT_QUOTES, "UTF-8");
	return $str;
}

function compass($degrees)
{
	// Returns a string representing a compass direction, as based upon an input degree value, $degrees.  Assumes $degrees does not exceed 360.
	$compass = array("N","NNE","NE","ENE","E","ESE","SE","SSE","S","SSW","SW","WSW","W","WNW","NW","NNW", "N");
	$compCount = round($degrees / 22.5);
	$compdir = $compass[$compCount];

	return $compdir;
}

function reportWeekly($week, $units, $weeklySummary, $json, $camAPIKey, $oneway, $onewayCompass)
{
	// Reports the contents of an associative array, $week, containing data about the following week's weather forecast.
	// Assumes a properly formatted $week associative array.
	$reportOutput;

	$weekdays = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
	$weekdaysShort = array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");
	$unixDay = mktime();
	$today = date('N', $unixDay); // Returns 1-7
	$cityName = $_GET["cityName"];
	$unitChoices = unitChoice($units);	// temp == [1], speed == [2]

	// Look for webcams.  If any are in the area, then present a carousel.  Otherwise, present a psa message.
	$camArray = makeCamArray($json, $camAPIKey);

	$reportOutput .= "<div class='summaryTable'>\n";

	if($camArray[0] != '')
	{
		$reportOutput .= "<div class='summaryRow'>\n";
		$reportOutput .= "	<div class='titleCell'>
								<div id='slider1'> ";
								if(sizeof($camArray) < 5)
								{
									$reportOutput .= 		"   <a class='buttons prev' href='#''>&lt;</a>";
								}
		$reportOutput .=        "	<div class='viewport'  style='";

									if(sizeof($camArray) < 7)
									{
										$carouselWidth = (184 + 20) * sizeof($camArray); // width of a pic is 184, + 20px padding
										$carouselWidth = "$carouselWidth";
									}
									else
									{
										$carouselWidth = 5 * (184 + 20);// "80%";
									}

									$reportOutput .= "width:" . $carouselWidth . "px;'>";

									$reportOutput .= "<ul class='overview'>";
										for($i = 0; $i < 10; $i++)
										{
											if($camArray[$i] != '')
											{
												$reportOutput .= "<li><img src='" . $camArray[$i] . "' /></li>";
											}
										}
										$reportOutput .= "
										</ul>
									</div>";
								if(sizeof($camArray) < 5)
								{
									$reportOutput .= "<a class='buttons next' href='#'>&gt;</a>";
								}

				$reportOutput .= "</div>
							</div>";
		$reportOutput .= "</div>\n";
	}

		$reportOutput .= "<div class='summaryRow'>\n";
			$reportOutput .= "<div class='summaryTitleCell' id='weeklySummary'>\n";
			$reportOutput .= "<h3><b>Weekly Summary: </b>" . $weeklySummary. "</h3>";
			$reportOutput .= "</div>\n";
		$reportOutput .= "</div>\n";
	$reportOutput .= "</div>\n";

	$reportOutput .= "<div class='summaryTable' id='weekly'>\n";

	// Make the metascore graph
	$metaArray = array();
	array_push($metaArray, $today);	// First element will indicate the starting day for the chart.

	for($i = 0; $i <= 6; $i++)
	{
		if (function_exists('meta'))
		{
			if($oneway === "true")
			{
				array_push($metaArray, meta($json, $units, "d{$i}", $onewayCompass));
			}
			else
			{
				array_push($metaArray, meta($json, $units, "d{$i}"));
			}
		}
		else
		{
			array_push($metaArray, "N/A");
		}
	}		

	$graphData = makeGraph($metaArray);

	// Weekly weather report output
	$today = date('N', $unixDay); // Returns 1-7

	$reportOutput .= "<div class='summaryTable'>\n";
	$reportOutput .= "<div class='summaryRow' style='text-align: center;'>\n";
	for($i = 0; $i <= 6; $i++)
	{
		$reportOutput .= "<div class='dayCell'>";
			if($today <= 6)
			{
				if (function_exists('meta'))
				{
					$metaFlag = "d" . $i;
					//print "metaflag, oneway, compass: " . $metaFlag . " / " . $oneway . " / " . $onewayCompass . "\n\n";
					if($oneway === "true")
					{
						$reportOutput .= "<b>" . $weekdaysShort[$today] . ": </b>" .  meta($json, $units, $metaFlag, $onewayCompass) . "%<br/>\n";	// weekday
					}
					if($oneway === "false")
					{
						$reportOutput .= "<b>" . $weekdaysShort[$today] . ": </b>" .  meta($json, $units, $metaFlag) . "%<br/>\n";	// weekday
					}
				}
				else
				{
					$reportOutput .= "<b>N/A</b><br/>\n"; 
				}

			}
			else
			{
				if (function_exists('meta'))
				{
					$metaFlag = "d" . $i;
					if($oneway === "true")
					{
					$reportOutput .= "<b>" . $weekdaysShort[$today - 7] . ": </b>" .  meta($json, $units, $metaFlag, $onewayCompass) . "%<br/>\n";	// weekday
					}
					if($oneway === "false")
					{
						$reportOutput .= "<b>" . $weekdaysShort[$today - 7] . ": </b>" .  meta($json, $units, $metaFlag) . "%<br/>\n";	// weekday
					}
				}
				else
				{
					$reportOutput .= "<b>N/A</b><br/>\n"; 
				}
			}
			$reportOutput .= "<img src='graphics/icons/" . $week[$i][5] . ".png' alt='Current Weather: " . $week[$i][5] . "' height='50' width='50'/><br/>";
			$reportOutput .= round($week[$i][4][1]) . "&deg;" . $unitChoices[1] . " Max<br/>\n";
			$reportOutput .= round($week[$i][4][0]) . "&deg;" . $unitChoices[1] . " Min<br/><br/>\n";

			$reportOutput .= round($week[$i][1]) . " " . $unitChoices[2] . " / " . compass($week[$i][2]) . "<br/><br/>\n";

			$reportOutput .= "Feels like:<br/>" . $json->daily->data[$i]->apparentTemperatureMin . "&deg;" . $unitChoices[1] . " to " . $json->daily->data[$i]->apparentTemperatureMax . "&deg;" . $unitChoices[1] . "<br/><br/>\n";

			$reportOutput .=  "<b>" . $week[$i][0] . "</b><br/>\n";
		$reportOutput .= "</div><!--dayCell-->\n";
		$today++;
	}
	$reportOutput .= "</div><!--summaryRow-->\n";
	$reportOutput .= "</div><!--summaryTable-->\n";

	$ret = array();

	array_push($ret, $graphData, $reportOutput);

	return $ret;
}

/*
	Collect Weather Data
*/

// Construct the query with our apikey and the query we want to make.
$endpoint = "https://api.forecast.io/forecast/" . $weatherAPIKey . "/" . $lat . "," . $lng;

// Modify units based on CA (canada), UK, or US (default if not modified).  SI available, but not used here.
$unitSettings = unitChoice($units);

if($unitSettings != -1)
{
	$endpoint .= $unitSettings[0];
	$tempSuffix = $unitSettings[1];
	$speedSuffix = $unitSettings[2];
}
else
{
	die('Error processing units');
}

// setup curl to make a call to the endpoint
$session = curl_init($endpoint);

// indicates that we want the response back
curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

// exec curl and get the data back
$data = curl_exec($session);

// remember to close the curl session once we are finished retrieveing the data
curl_close($session);

// decode the json data to make it easier to parse the php
$json = json_decode($data);
if ($search_results === NUL) die('Error parsing json');

/*
	Parse Weather Data
*/

// We create variables like "$temperature" rather than "$json->currently->temperature" to make things easier to read and with less complexity.  
// If this comes at the expense of slight extra resource usage, then so be it: the code will be more intuitive and have fewer typos.

// Today's weather
$currently = $json->currently->summary;
$temperature = $json->currently->temperature;
$windSpeed = $json->currently->windSpeed;
$windBearing = $json->currently->windBearing;
$todayIcon = $json->currently->icon;

// Tomorrow's forecast
$weeklyForecast = $json->daily->summary;
$nextDayForecast = $json->daily->data[1]->summary;
$nextDayTempMax = $json->daily->data[1]->temperatureMax;
$nextDayTempMin = $json->daily->data[1]->temperatureMin;

/*
	Associative Array for storing the next week's weather.  Each member represents a day.
	[i]: The daily record array (whole)
	[i][0]: Summary for weather on day i
	[i][1]: Wind Speed
	[i][2]: Wind Bearing
	[i][3]: Precipitation: 
		[i][3][0]: intensity
		[i][3][1]: max
		[i][3][2]: probability
	[i][4]: Temperature(whole): 
		[i][4][0]: tempMin
		[i][4][1]: tempMax
		[i][4][2]: apparentTempMin
		[i][4][3]: apparentTempMax
	[i][5]: Icon text
*/
$weeklyWeather = array();

// Array for temp use in building $weeklyWeather
$dailyWeather = array();

// Populate $weeklyWeather from the JSON data. 
for($i = 0; $i < 7; $i++)
{
	$dailyWeather[0] = $json->daily->data[$i]->summary;
	$dailyWeather[1] = $json->daily->data[$i]->windSpeed;
	$dailyWeather[2] = $json->daily->data[$i]->windBearing;

	$dailyWeather[3][0] = $json->daily->data[$i]->precipIntensity;
	$dailyWeather[3][1] = $json->daily->data[$i]->precipIntensityMax;
	$dailyWeather[3][2] = $json->daily->data[$i]->precipProbability;

	$dailyWeather[4][0] = $json->daily->data[$i]->temperatureMin;
	$dailyWeather[4][1] = $json->daily->data[$i]->temperatureMax;
	$dailyWeather[4][2] = $json->daily->data[$i]->apparentTemperatureMin;
	$dailyWeather[4][3] = $json->daily->data[$i]->apparentTemperatureMax;

	$dailyWeather[5] = $json->daily->data[$i]->icon;

	// Push the temp on, then clear it
	array_push($weeklyWeather, $dailyWeather);
	$dailyWeather = array();
}


// Prep an array for instantaneous metascore analysis
$instantMeta = array($temperature, $windSpeed, $todayIcon);

/*
	Publish Weather Data
*/

$output = "";

$output .= startUntilBody($cityName, $lat, $lng);

$output .= "<div id='container'>\n";
	$output .= bookmarkMe();

	// Header
	$output .= "<div id='header' style='background-image: url(https://maps.googleapis.com/maps/api/staticmap?center=" . ($lat - 0.12) . "," . $lng . "&zoom=11&size=600x600);'>\n";
	$output .= "<h1>Bike Report: " . $cityName . "</h1>\n";
	$output .= "<!--header--></div>\n";
	$output .= "<div id='navigation'>\n";
	$output .= "<ul>\n";
	$output .= "<li id='navDate'>" . timeStamp($json) . "</li>";

	// Alerts
	// [0]: light-related alert message, [1]: weather-related alert message
	$todayAlerts = checkAlerts($json);
	if($todayAlerts[0] != "")
	{		
		$output .= "<li id='navAlert'>Warning: " . $todayAlerts[0] . "</li>";
	}

	$output .= "<li><a href='http://www.brianneary.net/EXPERIMENTS/newBikeReport/bikeReport.html' title='Try Another City'>Try Another City</a></li>";

//	$output .= "<li><a target='_blank' href='" . getRadarMap($radarKey, $json->latitude, $json->longitude) . "' title='Radar'>Radar</a></li>"; // Weather radar map.

	$output .= "<li><a target='_blank' href='https://www.google.ca/maps/@" . $json->latitude . "," . $json->longitude . ",12z/data=!5m1!1e3' title='Click to see the Google bike map for this area'>Bike-friendly routes: " . $cityName . "</a></li>\n";
	$output .= "<li><a id='bookmarkme' href='#' title='bookmark this page'>Bookmark</a></li>";
	$output .= "<li><a href='mailto:webmaster@bikereport.net?Subject=BikeReport:" . $cityName . "'>Contact</a></li>\n";
	$output .= "</ul>";
	$output .= "<!--navigation--></div>";

	// Content
	$output .= "<div id='content'>\n";

	$output .= "<div id='topContent'>\n";

		// Report the week's weather
		$weekAndGraph = reportWeekly($weeklyWeather, $units, $weeklyForecast, $json, $camAPIKey, $oneway, $onewayCompass);

		$output .= "<div id='left'>\n";
		$output .= $weekAndGraph[0]; // Print out the graph code		
		$output .= "<div id='chart_div' style='width: 90%;'></div>\n";
		$output .= "<!--left--></div>\n";

		$output .= "<div id='right'>\n";

			$output .= "<div class='topTable'>\n";

			// Deprecated/testing
			//$output .= "<p><b>Data:</b> " . $cityName . ", " . $state . ", " . $country . ", " . $lat . ", " . $lng .  ", " . $units . "<br/>\n";
			//$output .= "<b>Time: </b>" . time() . "<br/>\n";
			//$output .= "<b>Time Tomorrow: </b>" . (time() + 86400) . "<br/>\n";
				$output .= "<div class='topRow'>\n";

						$output .= "<div class='topCell'  id='bigMeta'>\n";
						if (function_exists('meta'))
						{
							if($oneway === "true")
							{
								$output .= "<h1>" . meta($json, $units, 0, $onewayCompass) . "&#37; </h1>";
							}
							if($oneway === "false")
							{
								$output .= "<h1>" . meta($json, $units, 0) . "&#37; </h1>";
							}
						}
						else
						{
							$output .= "<h1>N/A</h1>";
						}

						$output .= "<b>Overall</b>\n";
						$output .= "<!--topCell(meta)--></div>\n";


					if($units == "UK" or $units == "CA")
					{
						// Celcius thermometer--set max value. 
						$thermoScale = 50;
					}
					else
					{
						// Fahrenheit thermometer--set max value.
						$thermoScale = 100;
					}

					$output .= "<div class='topCell'  id='bigTemperature'>\n";
					$output .= "
								<div id='thermo1' class='thermometer'>
								    <div class='track'>
								        <div class='goal'>
								            <div class='amount'>";

								        $output .= $thermoScale;

								        $output .= "<!--amount--></div>
								        <!--goal--></div>
								        <div class='progress'>
								            <div class='amount'>";

								         $output .= round($temperature);

								        $output .= "<!--amount--></div>
								        <!--progress--></div>
								    <!--track--></div>
								<!--thermo1--></div>
					\n";
					$output .= thermometer($units);
					$output .= "<b>Temperature</b>\n";
					$output .= "<!--topCell(thermo)--></div>\n";

//					$output .= "</div>\n";	// Close conditions right now cell

					$output .= "<div class='topCell' id='bigCompass'>\n";

						$output .= rotateArrow($windBearing);
						$output .= "<b>Wind: " . round($windSpeed) . " " . speedUnits($units) . "  (" . compass($windBearing) . ")</b><br/>\n";

					$output .= "<!--compass cell--></div>\n";

					$output .= "<div class='topCell' id='bigConditions'>\n";

						if( ($todayIcon == "cloudy") && (periodOfDay($json) != 0) ) 
						{
							$todayIcon = "cloudy-night";
						}

						$output .= "<!--Novacons Weather Icons, by Chet Design: digitalchet.deviantart.com/art/Novacons-Weather-Icons-13133337-->\n";
						$output .= "<img src='graphics/icons/" . $todayIcon . ".png' alt='Current Weather: " . $todayIcon . "' id='weatherIcon' height='100' width='100'/><br/>";
						$output .= "<p><b>Today's Conditions:</b> " . $currently . ".  " . $weeklyWeather[0][0] . "<br/>\n";

					$output .= "<!--metascore cell--></div>\n";	

				$output .= "<!--topRow--></div>\n";	

		$output .= "<!--topTable--></div>";	

		$output .= "<!--right--></div>\n";	

		$output .= "<div style='clear: both;'></div>\n";

		$output .= "<!--topContent--></div>\n";

		$output .= "<div id='below'>";

		$hoursToReport = array(2, 4, 8, 12);
		$output .= makeHourlyReport($json, $units, $hoursToReport);

		$output .= $weekAndGraph[1]; // Print out the Weekly Summary
		$output .= "<!--below--></div>";

		print $output;



		$output = "";

	$output .= "<!--content--></div>\n";

	$output .= "<div id='footer'>\n";
	//$output .= "<a id='bookmarkme' href='#' title='bookmark this page'>Bookmark This Page</a>";
	$output .= "Copyright 2014";
	$output .= "<!--footer--></div>\n";

$output .= "<!--container--></div>\n";

$output .= "</body>\n";
$output .= "</html>";
print $output;
?>