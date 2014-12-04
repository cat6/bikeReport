<?php
/*
	Collect and construct weather data
*/

function collectWeatherData($weatherAPIKey, $lat, $lng, $endpointUnits)
{
	// Construct the query with our apikey and the query we want to make.
	$endpoint = "https://api.forecast.io/forecast/" . $weatherAPIKey . "/" . $lat . "," . $lng;

	// Modify units based on CA (canada), UK, or US (default if not modified).  SI available, but not used here.
	$unitSettings = unitChoice($units);

	$endpoint .= $endpointUnits;

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

	//print "Currently, timezone: " . $json->currently->summary . " / " . $json->timezone .  "<br/>";
	return $json;
}
?>