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
		</script>
		<p><a id='bookmarkme' href='#'' rel='sidebar' title='bookmark this page'>Bookmark This Page</a></p>";
	return $bookmarkMeOutput;
}

function startUntilBody($cityName)
{
	$startUntilBodyOutput = "<html>
	<head>
		<title>The " . $cityName . " Bike Report</title>
	 	<meta charset='UTF-8'>

		<script src='//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js'></script>
		<!-- Rotation script for arrow representing wind direction -->
		<script type='text/javascript' src='http://jqueryrotate.googlecode.com/svn/trunk/jQueryRotate.js'></script>
		<!-- Google line graph -->
		<script type='text/javascript' src='https://www.google.com/jsapi'></script>

		<style type='text/css'>
			#image{
	  			margin:100px 100px;
			}
		</style>
	</head>
	<body>
	";
	return $startUntilBodyOutput;
}

function rotateArrow($windBearing)
{
	$rotateArrowOutput = "<script>";
	$rotateArrowOutput .= "$(document).ready(function(){";
 	$rotateArrowOutput .= "$('#windArrow').rotate(" . $windBearing . ");";
	$rotateArrowOutput .= "});";
	$rotateArrowOutput .= "</script>";

	$rotateArrowOutput .= "<img src='arrow.gif' id='windArrow'></p>";

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
        titleTextStyle: {color: 'white'},
        series: { 0:{ color: 'white'} },
        lineWidth: 7,
        curveType: 'function',
        backgroundColor:{fill:'#72A0C1', stroke:'#F0F8FF'},
        chartArea:{backgroundColor:'#5D8AA8'},
        vAxis:{ maxValue: 100, minValue: 0, textStyle:{color:'white'}, gridlines:{color:'#7B9DB5'} },
        hAxis:{ textStyle:{color:'white'} },
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

function metascore($day, $units)
{
	// Returns a metscore for a day based upon the input conditions for that day.
	// Assumes $day is an associative array 
	$windSpeed = floatval($day[1]);
	$precipProbab = floatval($day[3][2]);
	$temperature = floatval($day[4][0]);
	$icon = $day[5];

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
	$compass = array("N","NNE","NE","ENE","E","ESE","SE","SSE","S","SSW","SW","WSW","W","WNW","NW","NNW");
	$compcount = round($degrees / 22.5);
	$compdir = $compass[$compcount - 1];
	return $compdir;
}

function reportWeekly($week, $units)
{
	// Reports the contents of an associative array, $week, containing data about the following week's weather forecast.
	// Assumes a properly formatted $week associative array.

	// Table formatting flag: 1 means use a table, 0 means no.
	$table = 1;

	$weekdays = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
	$unixDay = mktime();
	$today = date('N', $unixDay); // Returns 1-7

	print "<p><b>Weekly Summary</b></p>";

	if($table == 1)
	{
		print "<table>";
		print "<tr>";
		// call chart function here.  Iterate over $week and build an array with metascore values
		$metaArray = array();
		array_push($metaArray, $today);	// First element will indicate the starting day for the chart.
		for($i = 0; $i <= 6; $i++)
		{
			array_push($metaArray, metascore($week[$i], $units));
		}
		print makeGraph($metaArray);		
		print "</tr>";
		print "<tr>";
	}

	for($i = 0; $i <= 6; $i++)
	{
		if($today <= 6)
		{
			if($table == 1)
			{
				print "<td>";
			}
			print "<p>";
			print "<i>" . $weekdays[$today] . "</i>:<br/>";
			print $week[$i][0] . "<br/>";
		}
		else
		{
			// We've gone off the end off the array, so compensate.
			print "<td>";
			print "<p>";
			print "<i>" . $weekdays[$today - 7] . "</i>:<br/>";
			print $week[$i][0] . "<br/>";
		}
		print "Wind speed/bearing: " . $week[$i][1] . " / " . $week[$i][2] . "<br/>";
		print "Precipitation: " . $week[$i][3][0] . " / " . $week[$i][3][1] . " / " . $week[$i][3][2] . "<br/>";
		print "Temperature: " . $week[$i][4][0] . " / " . $week[$i][4][1] . " / " . $week[$i][4][2] . " / " . $week[$i][4][3] . "<br/>";
		print "Icon: " . $week[$i][5] . "<br/>";

		print "Metascore: " . metascore($week[$i], $units) . "%<br/>";

		print "</p>";
		print "</td>";
		$today++;
	}
	if($table ==1)
	{
		print "</tr>";
	}
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

// Today's weather
$currently = $json->currently->summary;
$temperature = $json->currently->temperature;
$windSpeed = $json->currently->windSpeed;
$windBearing = $json->currently->windBearing;

// Tomorrow's forecast
$weeklyForecast = $json->daily->summary;
$nextDayForecast = $json->daily->data[0]->summary;
$nextDayTempMax = $json->daily->data[0]->temperatureMax;
$nextDayTempMin = $json->daily->data[0]->temperatureMin;

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

// Populate $weeklyWeather from the JSON data
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

/*
	Publish Weather Data
*/

$output = "";

$output .= startUntilBody($cityName);

$output .= "<p><b>Data:</b> " . $cityName . ", " . $state . ", " . $country . ", " . $lat . ", " . $lng .  ", " . $units . "</p>";

$output .= bookmarkMe();

$output .= "<p><b>Time: </b>" . time() . "</p>";
$output .= "<p><b>Present Conditions:</b> " . $currently . "</p>";
$output .= "<p><b>Temperature: </b>" . round($temperature) . " " . $tempSuffix . "</p>";
$output .= "<p><b>Wind Speed / Bearing:</b> " . round($windSpeed) . " " . $speedSuffix . " / " . $windBearing . " degrees (" . compass($windBearing) . ") / ";

$output .= rotateArrow($windBearing);

$output .= "<p><b>Metascore: </b>" . metascore($dailyWeather, $units) . "%</p>"; 

$output .= "<p><b>Time Tomorrow: </b>" . (time() + 86400) . "</p>";
$output .= "<p><b>24hr Forecast: </b>" . $nextDayForecast . "</p>";
$output .= "<p><b>High / Low tomorrow: </b>" . round($nextDayTempMax) . " " . $tempSuffix . " / " . round($nextDayTempMin) . $tempSuffix . "</b>";

$output .= "<p><b>Weekly Forecast: </b>" . $weeklyForecast . "</p>";
$output .= "<div id='chart_div' style='width: 900px; height: 200px;''></div>";
print $output;
$output = "";

// Report the week's weather
reportWeekly($weeklyWeather, $units);

$output .= "</body></html>";
print $output;
?>