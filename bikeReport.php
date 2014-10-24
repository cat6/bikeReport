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

// Scrub variables 
$cityName = clean($cityName);
$state = clean($state);
$country = clean($country);
$lat = clean($lat);
$lng = clean($lng);
$units = clean($units);

// Acquire API credentials from an ini file.
$ini_array = parse_ini_file("bikereport.ini", true);

$weatherAPIKey = explode(', ', $ini_array['api_keys']['weather']);
$weatherAPIKey = $weatherAPIKey[0];

/*
	Functions
*/

function checkAlerts($data)
{
	// Returns an array containing [0]: lights-on alerts, [1]: weather alerts

	// Check if it's between 30 mins before dusk and 30 mins after dawn--the legal definition of 'night' for driving/road purposes in Ontario
	$currentTime = $data->currently->time;
	$sunriseToday = $data->daily->data[0]->sunrisetime;
	$sunsetToday = $data->daily->data[0]->sunsetTime;
	$icon = $data->currently->icon;
	$lights;
	$weather;
	$alerts = array();

	if( ($currentTime > $sunsetToday) && ($currentTime > ($sunsetToday + 1800) )  )  
	{
		// Then it's nighttime, before dawn.  
		$lights = "Use lights: night";
	}
	if($currentTime < ($sunriseToday + 1800))
	{
		// Then it's nighttime, after dusk.
		$lights = "Use lights: pre-dawn";
	}
	if(($currentTime > $sunsetToday) && ($curentTime < ($sunsetToday + 1800) ) )
	{
		$lights = "Use lights: dusk";
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
			$lights = "Use Lights: fog";
		}
	}
	$lights .= "";

	$weather = "";	// Placeholder until weather alerts are implemented

	array_push($alerts, $lights, $weather);

	return $alerts;
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
		<!-- Rotation script for arrow representing wind direction -->
		<script type='text/javascript' src='http://jqueryrotate.googlecode.com/svn/trunk/jQueryRotate.js'></script>
		<!-- Google line graph -->
		<script type='text/javascript' src='https://www.google.com/jsapi'></script>

		<link href='http://fonts.googleapis.com/css?family=Droid+Serif%7CCrimson+Text' rel='stylesheet' type='text/css'>

		<style type='text/css'>
			#image{
	  			margin:100px 100px;
			}
			
			html, body {
   				padding: 0;
     			margin: 0;
			}

			body {
				background: #FAEBD7;
				font-family: 'Droid Serif';
			}

			#container
			{
				margin-left: auto;
				margin-right: auto;
				height: auto;
				width: auto;
			}

			#header
			{
				color: #FAEBD7;
				background: #5D8AA8;
				padding: 20px;
				font-family: 'Crimson Text', serif;
				/* Stroking and shadow makes the title visible when it overlaps the map in the header */
				webkit-text-stroke: 1px black;
			   	text-shadow:
			    	3px 3px 0 #000,
			        -1px -1px 0 #000,  
			    	1px -1px 0 #000,
			        -1px 1px 0 #000,
			    	1px 1px 0 #000;
				";

	$startUntilBodyOutput .= "background-image: url('https://maps.googleapis.com/maps/api/staticmap?center=" . ($lat - 0.12) . "," . $lng . "&zoom=11&size=600x600');";

	$startUntilBodyOutput .= "
				background-repeat: no-repeat;
				background-attachment: fixed;
				background-position: right top;
			}

			#header h1 { margin: 0; font-size: 250%;}

			#navigation
			{
				float: right;
				width: 100%;
				background: #333;
			}

			#navigation ul
			{
				float: right;
				margin: 0;
				padding: 0;
			}

			#navigation ul li
			{
				list-style-type: none;
				display: inline;
			}

			#navigation li a
			{
				display: block;
				padding: 5px 10px;
				color: #fff;
				text-decoration: none;
			}

			#navigation li a:hover { background: #5D8AA8; }

			#content
			{
				background: #72A0C1;

				overflow: hidden;
				min-height: 700px;
				padding-left: 10px;
				padding-right: 10px;
			}

			#content h2
			{
				color: #000;
				font-size: 160%;
				margin: 0 0 .5em;
			}

			#footer
			{
				background: #333;
				color: #fff;
				text-align: right;
				padding: 20px;
				height: 1%;
			}

			#left{
			    width:40%;
			    float:left;
			}

			#right{
			    width:60%;
			    float:right;
			}

			#below{
			}
			
			table
			{ 
				table-layout:fixed;
				width: 100%;
    			margin-left: auto;
    			margin-right: auto;
			}
			
			td, th {
				vertical-align:top;
			}
			tr {
				align: center;
			}

			#bigCompass {
				text-align: center;
				vertical-align: middle;
				background: rgba(255, 255, 255, .2);				
			}

			#bigConditions {
				text-align: center;
				vertical-align: middle;
				background: rgba(255, 255, 255, .5);
			}

			#bigConditions h1 { font-size: 250%; }

			#bigTemperature {
				text-align: center;
				vertical-align: middle;
				background: rgba(255, 255, 255, .5);
			}

			#bigTemperature h1 { font-size: 250%; }

			#compassTitle {
				text-align: top;
				color: 'red';
			}

			.summaryTable {
				width: 100%;
				display: table;
			}

			.summaryRow {
				display: table-row;
				width: 100%;
			}

			.summaryCell {
				display: table-cell;
				width: 25%;
			}

			.summaryTitleCell {
				width: 100%;
			}

			.topTable {
				width: 100%;
				display: table;
			}

			.topRow {
				display: table-row;
				width: 100%;
			}

			.topCell {
				display: table-cell;
				width: 33%;
			}


		</style>
	</head>
	<body>
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
        title: 'Cycling Conditions',
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

function metascore($day, $units, $metaFlag)
{
	// Returns a metscore for a day based upon the input conditions for that day.
	// Depending upon $metaFlag, analyzes either instantaneous readings ($metaFlag == 0) or
	// analyzes a later day's conditons from an associative array. 

	// $day if instantaneous: [0]: windspeed, [1]: temperature, [2]: $icon
	if($metaFlag == 0)
	{
		$temperature = $day[0];
		$windSpeed = $day[1];
		$icon = $day[2];
	}
	else
	{
		$windSpeed = floatval($day[1]);
		$precipProbab = floatval($day[3][2]);

		$temperatureMin = floatval($day[4][0]);
		$temperatureMax = floatval($day[4][1]);
		$temperature = (($temperatureMax - $temperatureMin) / 2) + $temperatureMin;

		$icon = $day[5];
	}

	// Start off assuming perfect conditions
	$score = 100;

	// If CA, don't convert, if UK, convert mph to km/hr, if US, convert mph to km/hr AND convert F to C
	if($units == "US")
	{
		$windSpeed = convertSpeed($windSpeed);
		$temperature = convertTemp($temperature);
	}
	if($units == "UK")
	{
		$windSpeed = convertSpeed($windSpeed);
	}

	/*
		Analysis
	 	Now things should be (uniformly) in "Canadian" format.  Analyze the data and produce a metascore.
	*/

	// Deduct [3 points] for each km/hr of excessive wind
	if($windSpeed > 10)
	{
		$score = $score - (3 * ($windSpeed - 10));
	}
	// Deduct [3 points] for each degree of excessively hot temperature
	if($temperature > 25)
	{
		$score = $score - (3 * ($temperature - 25));
	}
	// Deduct [3 points] for each degree of excessively cold temperature
	if($temperature < 10)
	{
		if($temperature == 0)
		{
			$score = $score - 30;
		}

		if($temperature > 0)
		{
			$score = $score - (3 * (10 - $temperature));
		}
		if($temperature < 0)
		{
			$score = $score + (3 * (-10 + $temperature)); 
		}
	}

	// If $precipProb is high, or if the conditons ($icon) contain bad words :P, then lower the score
	if($icon == "rain" || $icon == "snow" || $icon == "sleet" || $icon == "hail")
	{
		// Than it's precipitating badly enough for a serious deduction (40% of what it would be otherwise)
		$score = $score * 0.4;
	}

	$score = round($score);

	if($icon == "fog")
	{
		$score = $score - 10;
	}

	// Set a floor and ceiling to keep the overall value constrained.
	if($score > 100)
	{
		$score = 100;
	}
	if($score < 0)
	{
		$score = 0;
	}
	return (string)$score;
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

function reportWeekly($week, $units, $weeklySummary)
{
	// Reports the contents of an associative array, $week, containing data about the following week's weather forecast.
	// Assumes a properly formatted $week associative array.
	$reportOutput;

	$weekdays = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
	$unixDay = mktime();
	$today = date('N', $unixDay); // Returns 1-7

	$reportOutput .= "<div class='summaryTable'>\n";

		$reportOutput .= "<div class='summaryRow'>\n";
			$reportOutput .= "<div class='summaryTitleCell'>\n";
			$reportOutput .= "<p><b><h3>Weekly Summary:</b>" . $weeklySummary ."</p>\n";
			$reportOutput .= "</div>\n";
		$reportOutput .= "</div>\n";
	$reportOutput .= "</div>\n";

	$reportOutput .= "<div class='summaryTable'>\n";


	$metaArray = array();
	array_push($metaArray, $today);	// First element will indicate the starting day for the chart.

	for($i = 0; $i <= 6; $i++)
	{
		array_push($metaArray, metascore($week[$i], $units, 1));
	}		

	$graphData = makeGraph($metaArray);

	$reportOutput .= "<div class='summaryRow'>\n";
	$firstDayFlag = 1;

	for($i = 0; $i <= 6; $i++)
	{

		$reportOutput .= "<div class='summaryCell'>\n";
		$reportOutput .= "<p>";

		if($firstDayFlag == 1)
		{
			$reportOutput .= "<b><i>Today (" . $weekdays[$today] . "):</i></b><br/>\n";	// weekday
			$firstDayFlag = 0;
		}
		elseif($today <= 6)
		{
			$reportOutput .= "<b><i>" . $weekdays[$today] . ":</i></b><br/>\n";	// weekday
		}
		else
		{
			// We've gone off the end off the array, so compensate.
			$reportOutput .= "<b><i>" . $weekdays[$today - 7] . "</i></b>:<br/>";	// Weekday
		}

		$reportOutput .= $week[$i][0] . "<br/>";	// This day's summary
		$reportOutput .= "Wind speed/bearing: " . $week[$i][1] . " / " . $week[$i][2] . "<br/>\n";
		$reportOutput .= "Precipitation: " . $week[$i][3][0] . " / " . $week[$i][3][1] . " / " . $week[$i][3][2] . "<br/>\n";
		$reportOutput .= "Temperature: " . $week[$i][4][0] . " / " . $week[$i][4][1] . " / " . $week[$i][4][2] . " / " . $week[$i][4][3] . "<br/>\n";
		$reportOutput .= "Icon: " . $week[$i][5] . "<br/>\n";

		$reportOutput .= "Metascore: " . metascore($week[$i], $units, 1) . "&#37;<br/>\n";

		$reportOutput .= "</p>\n";
		if($i == 3)
		{
			$reportOutput .= "</div>\n";	// Close off this cell
			$reportOutput .= "</div>\n";	// Close off the row
			$reportOutput .= "<div class='summaryRow'>\n";	// Start new row
		}
		else
		{
			$reportOutput .= "</div>\n";	// Close off this cell
		}
		$today++;
	}

	$reportOutput .= "</div>\n";	// Close off the last row
	$reportOutput .= "</div>\n";	// Close off the "table"

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

// Alerts
// [0]: light-related alert message, [1]: weather-related alert message
$todayAlerts = checkAlerts($json);

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
	$output .= "<div id='header'>\n";
	$output .= "<h1>Bike Report: " . $cityName . "</h1>";
	$output .= "<!--header--></div>\n";
	$output .= "<div id='navigation'>\n";
	$output .= "<ul>\n";
//	$output .= "<li><a href='#' title='Try Another City'>Try Another City</a></li>";
	$output .= "<li><a id='bookmarkme' href='#' title='bookmark this page'>Bookmark This Page</a></li>";
	$output .= "</ul>";
	$output .= "<!--navigation--></div>";

	// Content
	$output .= "<div id='content'>\n";

		// Report the week's weather
		$weekAndGraph = reportWeekly($weeklyWeather, $units, $weeklyForecast);

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

					$output .= "<div class='topCell' id='bigTemperature'>\n";

						$output .= "<h1>" . metascore($instantMeta, $units, 0) . "&#37;</h1>";
						$output .= "<b>" . round($temperature) . " " . $tempSuffix . "</b><br/>\n";

//						if($todayAlerts[0] != "")
//						{			
//							$output .= "<b>" . $todayAlerts[0] . "</b>";
//						}

					$output .= "</p>\n";
					$output .= "</div>\n";	// Close conditions right now cell

					$output .= "<div class='topCell' id='bigCompass'>\n";

						$output .= "<b>Wind:</b><br/>\n"; 
						$output .= rotateArrow($windBearing);
						$output .= "<b>" . round($windSpeed) . " " . speedUnits($units) . "  (" . compass($windBearing) . ")</b><br/>\n";

					$output .= "<!--compass cell--></div>\n";	// Close compass cell

					$output .= "<div class='topCell' id='bigConditions'>\n";
						$output .= "<!--Novacons Weather Icons, by Chet Design: digitalchet.deviantart.com/art/Novacons-Weather-Icons-13133337-->\n";
						$output .= "<img src='graphics/icons/" . $todayIcon . ".png' alt='Current Weather: " . $todayIcon . "' id='weatherIcon' height='100' width='100'/><br/>";
						$output .= "<p><b>Today's Conditions:</b> " . $currently . ".  " . $weeklyWeather[0][0] . "<br/>\n";


					$output .= "<!--metascore cell--></div>\n";	

				$output .= "<!--topRow--></div>\n";	

		$output .= "<!--topTable--></div>";	

		$output .= "<!--right--></div>\n";	

		$output .= "<div id='below'>";
		$output .= $weekAndGraph[1]; // Print out the Weekly Summary
		$output .= "<!--below--></div>";

		print $output;

		$output = "";

		print $output;
		$output = "";

	$output .= "<!--content--></div>\n";

	$output .= "<div id='footer'>\n";
	$output .= "Copyright 2014";
	$output .= "<!--footer--></div>\n";

$output .= "<!--container--></div>\n";

$output .= "</body>\n";
$output .= "</html>";
print $output;
?>