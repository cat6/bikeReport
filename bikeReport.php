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
$output .= "<p><b>Temperature: </b>" . $temperature . " " . $tempSuffix . "</p>";
$output .= "<p><b>Wind Speed / Bearing:</b> " . $windSpeed . " " . $speedSuffix . " / " . $windBearing . " degrees</p>";

$output .= "<p><b>Time Tomorrow: </b>" . (time() + 86400) . "</p>";
$output .= "<p><b>24hr Forecast: </b>" . $nextDayForecast . "</p>";
$output .= "<p><b>High / Low tomorrow: </b>" . $nextDayTempMax . " " . $tempSuffix . " / " . $nextDayTempMin . $tempSuffix . "</b>";

$output .= "<p><b>Weekly Forecast: </b>" . $weeklyForecast . "</p>";

$output .= "</body></html>";
print $output;
?>