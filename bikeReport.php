<?php

$cityName = $_GET["cityName"];
$state = $_GET["locality"];
$country = $_GET["country"];
$lat = $_GET["lat"];
$lng = $_GET["lng"];
$units = $_GET["units"];

$cityName = clean($cityName);
$state = clean($state);
$country = clean($country);
$lat = clean($lat);
$lng = clean($lng);
$units = clean($units);

function clean($str)
{
	$str = mb_convert_encoding($str, "UTF-8", "UTF-8");
	$str = htmlentities($str, ENT_QUOTES, "UTF-8");
	return $str;
}

function compass($degrees)
{
	$compass = array("N","NNE","NE","ENE","E","ESE","SE","SSE","S","SSW","SW","WSW","W","WNW","NW","NNW");
	$compcount = round($degrees / 22.5);
	$compdir = $compass[$compcount];
	return $compdir;
}

function reportWeekly($week)
{
	$weekdays = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
	$unixDay = mktime();
	$today = date('N', $unixDay); // Returns 1-7

	print "<p><b>Weekly Summary</b></p>";

	for($i = 0; $i <= 6; $i++)
	{
		if($today <= 6)
		{
			print "<p>";
			print "<i>" . $weekdays[$today] . "</i>: " . $week[$i][0] . "<br/>";
			print "Wind speed/bearing: " . $week[$i][1] . " / " . $week[$i][2] . "<br/>";
			print "Precipitation: " . $week[$i][3][0] . " / " . $week[$i][3][1] . " / " . $week[$i][3][2] . "<br/>";
			print "</p>";
		}
		else
		{
			// We've gone off the end off the array
			print "<p>";
			print "<i>" . $weekdays[$today - 7] . "</i>: " . $week[$i][0] . "<br/>";
			print "Wind speed/bearing: " . $week[$i][1] . " / " . $week[$i][2] . "<br/>";
			print "Precipitation: " . $week[$i][3][0] . " / " . $week[$i][3][1] . " / " . $week[$i][3][2] . "<br/>";
			print "</p>";
		}
		$today++;
	}
}

// Acquire API credentials from an ini file.
$ini_array = parse_ini_file("bikereport.ini", true);

$weatherAPIKey = explode(', ', $ini_array['api_keys']['weather']);
$weatherAPIKey = $weatherAPIKey[0];
//print "Weather key: " . $weatherAPIKey;

// construct the query with our apikey and the query we want to make
$endpoint = "https://api.forecast.io/forecast/" . $weatherAPIKey . "/" . $lat . "," . $lng;

// Modify units based on CA (canada), UK, or US (default if not modified).  SI available, but not used here.
if($units == "CA")
{
	$endpoint .= "?units=ca";
	$tempSuffix = "C";
	$speedSuffix = "km/hr";
}
if($units == "US")
{
	$endpoint .="?units=us";
	$tempSuffix = "F";
	$speedSuffix = "mph";
}
if($units == "UK")
{
	$endpoint .= "?units=uk";
	$tempSuffix = "C";
	$speedSuffix = "mph";
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

$currently = $json->currently->summary;
$temperature = $json->currently->temperature;
$windSpeed = $json->currently->windSpeed;
$windBearing = $json->currently->windBearing;

// Daily has 7 members in data (one for each day, presumably)
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

*/
$weeklyWeather = array();

// Array for temp use in building $weeklyWeather
$dailyWeather = array();

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

	// Push the temp on, then clear it
	array_push($weeklyWeather, $dailyWeather);
	$dailyWeather = array();
}

$output = "";
$output .= "<html>
 <head>
  <title>The "
  . $cityName .

  " Bike Report</title>
 <meta charset='UTF-8'>
 </head>
 <body>
";

$output .= "<p><b>Data:</b> " . $cityName . ", " . $state . ", " . $country . ", " . $lat . ", " . $lng .  ", " . $units . "</p>";
$output .= "<p><b>Time: </b>" . time() . "</p>";
$output .= "<p><b>Present Conditions:</b> " . $currently . "</p>";
$output .= "<p><b>Temperature: </b>" . round($temperature) . " " . $tempSuffix . "</p>";
$output .= "<p><b>Wind Speed / Bearing:</b> " . round($windSpeed) . " " . $speedSuffix . " / " . $windBearing . " degrees (" . compass($windBearing) . ")</p>";

$output .= "<p><b>Time Tomorrow: </b>" . (time() + 86400) . "</p>";
$output .= "<p><b>24hr Forecast: </b>" . $nextDayForecast . "</p>";
$output .= "<p><b>High / Low tomorrow: </b>" . round($nextDayTempMax) . " " . $tempSuffix . " / " . round($nextDayTempMin) . $tempSuffix . "</b>";

$output .= "<p><b>Weekly Forecast: </b>" . $weeklyForecast . "</p>";
print $output;
$output = "";

// Report the week's weather
reportWeekly($weeklyWeather);

$output .= "</body></html>";
print $output;
?>