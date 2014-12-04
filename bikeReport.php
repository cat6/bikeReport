<?php

/*
	Initialization
*/

// Import metascore functionality
require_once("meta.php");

// Import recommendation functionality
require_once("recommend.php");

// Import utility functions
require_once("utilities.php");

// Import weather data collection
require_once("getData.php");

// Basic Variables
$cityName = $_GET["cityName"];
$state = $_GET["locality"];
$country = $_GET["country"];
$lat = $_GET["lat"];
$lng = $_GET["lng"];
$units = $_GET["units"];
$oneway = $_GET["oneway"];
$onewayCompass = $_GET["compass"]; 
// These are the GM unix time input, regardless of where the user is.  Modify on the backend with the offset value.
$startTime = $_GET["tripStart"];
$endTime = $_GET["tripLength"];

$tripSpeed = $_GET["tripSpeed"];
$tripUnits = $units;// $_GET["tripUnits"];

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
$camAPIKey = explode(', ', $ini_array['api_keys']['cam']); 
$camAPIKey = $camAPIKey[0];
$radarKey = explode(', ', $ini_array['api_keys']['radar']);
$radarKey = $radarKey[0];

/*
	Functions
*/

function getPrecipInfo($precipType, $precipIntensity, $precipAccumulation, $units)
{
	// Returns HTML pertaining to precipitation, intended for the weekly report.
	$precipOutput = "<b>";

	if($precipType == "rain")
	{
		if($units == "US")
		{
			// Express rain in inches to one-tenth
			if(round($precipIntensity, 1) < 0.1)
			{
				$precipOutput .= "</b>-"; 
			}
			else
			{
				$precipOutput .= "Rain:</b> " . round($precipIntensity, 1) . " in";				
			}
		}
		else
		{
			// Express in mm's
			if(round(inchesToCM($precipIntensity), 0) < 1)
			{
				$precipOutput .= "Rain:</b> Less than 1mm";
			}
			else
			{
				$precipOutput .= "Rain:</b> " . round(inchesToCM($precipIntensity), 0) . "mm";
			}
		}
	}
	elseif($precipType == "snow") 
	{ 
		if($units == "US")
		{	
			if(round($precipAccumulation, 1) < 0.1)
			{
				$precipOutput .= "Snow:</b> Less than 0.1 in";
			}
			else
			{
				// Express snow to one-tenth of an inch
				$precipOutput .= "Snow:</b> " . round($precipAccumulation, 1) . " in";
			}
		}
		else
		{
			// Express snow to the nearest cm.  
			if($precipAccumulation > 0)
			{
				$precipOutput .= "Snow:</b> " . round(inchesToCM($precipAccumulation, 0)) . "cm";
			}
			else
			{
				$precipOutput .= "Snow:</b> Less than 1cm";
			} 
		}
	}
	elseif($precipType == "sleet")
	{
		$precipOutput .= "Sleet</b>";
	}
	elseif($precipType == "hail")
	{
		$precipOutput .= "Hail</b>";
	}
	else
	{
		$precipOutput .= "-</b>";
	}

	return $precipOutput;
}

function makeHourlyReport($json, $units, $hoursToReport, $startTime, $endTime, $unitSettings, $sunTimes)
{
	$unitChoices = unitChoice($units);	// temp == [1], speed == [2]

	$output = "<br/>";

	// Summary for the next few hours
	$output .= "<div class='summaryTable' id='recommendTitle'>\n";
		$output .= "<div class='summaryRow'>\n";
			$output .= "<div class='summaryRecommendCell' id='recommendSummary'>\n";

			$output .= makeRecommendations($json, $startTime, $endTime, $unitSettings);

			if($sunTimes[0] != -1)
			{
				$output .= "<b>Sunrise:</b> " . $sunTimes[0] . " <b>Sunset:</b> " . $sunTimes[1] . "<br/><br/>";
			}
			else
			{
				$output .= "<b>Sunset:</b> " . $sunTimes[1] . "<br/><br/>";			
			}

			$output .= "</div><!--dailySummary-->\n";
		$output .= "</div><!--summaryRow-->\n";
	$output .= "</div><!--summaryTable-->\n";

	// Summary for the next few hours
	$output .= "<div class='summaryTable' id='dailyTitle'>\n";
		$output .= "<div class='summaryRow'>\n";
			$output .= "<div class='summaryTitleCell' id='dailySummary'>\n";
				$output .= "<br/><h3><b>In the next few hours:  " . $json->hourly->summary . "</b></h3><br/><br/>\n";
			$output .= "</div><!--dailySummary-->\n";
		$output .= "</div><!--summaryRow-->\n";
	$output .= "</div><!--summaryTable-->\n";

	$output .= "<div class='summaryTable' id='daily'>\n";
	$output .= "<div class='summaryRow'>\n";



	foreach($hoursToReport as $hour)
	{
		$output .= "<div class='hourCell' style='padding: 10px;'>\n";
		$output .= "<h2>" . $hour . "</h2>" . " hours from now: ";

		//metascore
		$output .= "<h2>" . meta($json, $units, "h{$hour}", $json->hourly->data[$hour]->time) . "%</h2><br/><br/>\n";

		$output .= "<img src='graphics/icons/" . $json->hourly->data[$hour]->icon . ".png' alt='" . $hour . " hours from now: " . $json->hourly->data[$hour]->icon . "' height='50' width='50'/><br/>";

		$output .= "<i>" . $json->hourly->data[$hour]->summary . "</i><br/>\n";

		$output .= "<h4>Temp: " . round($json->hourly->data[$hour]->temperature) . $unitChoices[1] . "&deg;</h4>\n";
		$output .= "<h4>Feels like: " . round($json->hourly->data[$hour]->apparentTemperature) . $unitChoices[1] . "&deg;</h4>\n";
		$output .= "<h4>Wind: " . round($json->hourly->data[$hour]->windSpeed) . 	$unitChoices[2] . " / " . compass($json->hourly->data[$hour]->windBearing) . "</h4>";

		if($json->hourly->data[$hour]->precipType != 'undefined')
		{
			if($json->daily->data[$i]->precipAccumulation != 'undefined')
			{
				$precipAccumulation = $json->daily->data[$i]->precipAccumulation;
			}
			else
			{
				$precipAccumulation = "N/A";
			}

			$output .= getPrecipInfo($json->hourly->data[$hour]->precipType, $json->hourly->data[$hour]->precipIntensity, $precipAccumulation, $units);
		}

		$output .= "</div><!--day dayCell-->\n";
	}

	$output .= "</div><!--day summaryRow-->\n";
	$output .= "</div><!--day summaryTable-->\n";
	$output .= "<br/>\n";

	return $output;
}

function startUntilBody($cityName, $lat, $lng)
{
	$startUntilBodyOutput = "<!DOCTYPE html>\n<html>
	<head>
		<title>The " . $cityName . " Bike Report</title>\n
	 	<meta charset='UTF-8'>
    	<meta name=\"Description\" content=\"Bike Report provides weather, forecast and analysis of biking conditions wherever you are.\"/>
	    <link rel=\"icon\" type=\"image/png\" href=\"/favicon-160x160.png\" sizes=\"160x160\">
	    <link rel=\"icon\" type=\"image/png\" href=\"/favicon-96x96.png\" sizes=\"96x96\">
	    <link rel=\"icon\" type=\"image/png\" href=\"/favicon-16x16.png\" sizes=\"16x16\">
	    <link rel=\"icon\" type=\"image/png\" href=\"/favicon-32x32.png\" sizes=\"32x32\">

		<script src='//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js'></script>
		<!-- Tiny Carousel for webcams-->
		<script type='text/javascript' src='jquery/jquery.tinycarousel.min.js'></script>
		<!-- Rotation script for arrow representing wind direction -->
		<script type='text/javascript' src='http://jqueryrotate.googlecode.com/svn/trunk/jQueryRotate.js'></script>
		<!-- Google line graph -->
		<script type='text/javascript' src='https://www.google.com/jsapi'></script>

		<link href='http://fonts.googleapis.com/css?family=Playfair+Display%7CDroid+Serif' rel='stylesheet' type='text/css'>

		<link rel='stylesheet' href='styles/tinycarousel.css' type='text/css' media='screen'/>

		<!--Main CSS-->
		<link rel='stylesheet' href='styles/bikeStyles.css' type='text/css' media='screen' /> 

		<script>
			$(window).load(function()
				{
					$('#slider1').tinycarousel({ interval: true });
				});
		</script>
";
	return $startUntilBodyOutput;
}

function reportWeekly($week, $units, $weeklySummary, $json, $camAPIKey, $oneway, $onewayCompass)
{
	// Reports the contents of an associative array, $week, containing data about the following week's weather forecast.
	// Assumes a properly formatted $week associative array.
	$reportOutput;

	$weekdays = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
	$weekdaysShort = array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");
	$unixDay = time();
	$today = date('N', $unixDay); // Returns 1-7
	$cityName = $_GET["cityName"];
	$unitChoices = unitChoice($units);	// temp == [1], speed == [2]

	// Look for webcams.  If any are in the area, then present a carousel.  Otherwise, present a psa message.
	$camArray = makeCamArray($json, $camAPIKey);

	if($camArray[0] != '')
	{
		$reportOutput .= "<div class='summaryTable'>\n";
		$reportOutput .= "<div class='summaryRow'>\n";
		$reportOutput .= "	<div class='summaryTitleCell'>
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
												$reportOutput .= "<li><img src='" . $camArray[$i] . "' alt='webcam image' /></li>";
											}
										}
										$reportOutput .= "
										</ul>
									</div><!--viewport-->";
								if(sizeof($camArray) < 5)
								{
									$reportOutput .= "<a class='buttons next' href='#'>&gt;</a>";
								}

				$reportOutput .= "</div><!--slider1-->
							</div><!--summaryTitleCell-->";
		$reportOutput .= "</div><!--summaryRow-->\n";
		$reportOutput .= "</div><!--summaryTable-->\n";
	}

	$reportOutput .= "<div class='summaryTable'>\n";
		$reportOutput .= "<div class='summaryRow'>\n";
			$reportOutput .= "<div class='summaryTitleCell' id='weeklySummary'>\n";
			$reportOutput .= "<h3><b>Weekly Summary: </b>" . $weeklySummary. "</h3><br/>";
			$reportOutput .= "</div><!--summaryTitleCell-->\n";
		$reportOutput .= "</div><!--summaryRow-->\n";

		$reportOutput .= "<div class='summaryRow'>\n";
			$reportOutput .= "<div class='summaryTitleCell' style='width: 80%; margin-left:auto; margin-right:auto;'>\n";
				$reportOutput .= "<div id='chart_div' style='width: 100%;'></div>\n";
			$reportOutput .= "</div><!--summaryTitleCell-->\n";
		$reportOutput .= "</div><!--summaryRow-->\n";

	$reportOutput .= "</div><!--summaryTable-->\n";


	$reportOutput .= "</div><!--summaryTable-->\n";

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
				array_push($metaArray, meta($json, $units, "d{$i}", $json->daily->data[$i]->time, $onewayCompass));
			}
			else
			{
				array_push($metaArray, meta($json, $units, "d{$i}", $json->daily->data[$i]->time));
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
	$reportOutput .= "<div class='summaryRow'>\n";
	for($i = 0; $i <= 6; $i++)
	{
		$reportOutput .= "<div class='dayCell'>";
			if($today <= 6)
			{
				if (function_exists('meta'))
				{
					$metaFlag = "d" . $i;
					if($oneway === "true")
					{
						$reportOutput .= "<b>" . $weekdaysShort[$today] . ": </b>" .  meta($json, $units, $metaFlag, $json->daily->data[$i]->time, $onewayCompass) . "%<br/>\n";	// weekday
					}
					if($oneway === "false")
					{
						$reportOutput .= "<b>" . $weekdaysShort[$today] . ": </b>" .  meta($json, $units, $metaFlag, $json->daily->data[$i]->time) . "%<br/>\n";	// weekday
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
					$reportOutput .= "<b>" . $weekdaysShort[$today - 7] . ": </b>" .  meta($json, $units, $metaFlag, $json->daily->data[$i]->time, $onewayCompass) . "%<br/>\n";	// weekday
					}
					if($oneway === "false")
					{
						$reportOutput .= "<b>" . $weekdaysShort[$today - 7] . ": </b>" .  meta($json, $units, $metaFlag, $json->daily->data[$i]->time) . "%<br/>\n";	// weekday
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

			if($json->daily->data[$i]->precipAccumulation != 'undefined')
			{
				$precipAccumulation = $json->daily->data[$i]->precipAccumulation;
			}
			else
			{
				$precipAccumulation = "N/A";
			}

			$reportOutput .= getPrecipInfo($week[$i][3][3], $json->daily->data[$i]->precipIntensity, $precipAccumulation, $units) . "<br/><br/>";

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

// Modify units based on CA (canada), UK, or US (default if not modified).  SI available, but not used here.
// This must be done before weather data collection in order to request data in the proper temperature units (F or C)
$unitSettings = unitChoice($units);

if($unitSettings != -1)
{
	$endpointUnits = $unitSettings[0];
	$tempSuffix = $unitSettings[1];
	$speedSuffix = $unitSettings[2];
}
else
{
	die('Error processing units');
}

if($tempSuffix == "F")
{
	$thermoMaxValue = 120;
	$thermoMinValue = -20;
}
else
{
	// Metric by default
	$thermoMaxValue = 40;
	$thermoMinValue = -30;
}
$thermoMidValue = 0;

$json = collectWeatherData($weatherAPIKey, $lat, $lng, $endpointUnits);

/*
	Parse Weather Data
*/

$sunTimes = getSunUpDownTimes($json);

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
		[i][3][3]: type
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

	if($json->daily->data[$i]->precipType != "") // $json->daily->data[$i]->precipType != 'undefined'
	{
		$dailyWeather[3][3] = $json->daily->data[$i]->precipType;
	}
	else
	{
		$dailyWeather[3][3] = "Clear";
	}

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

$output .= "</head>\n";
$output .= "<body>\n";

// Google analytics
$output .= "<?php include_once(\"analyticstracking.php\") ?>";

$output .= "<div id='container'>\n";
	$output .= bookmarkMe();

	// Header
	$output .= "<div id='header' style='background-image: url(https://maps.googleapis.com/maps/api/staticmap?center=" . ($lat - 0.12) . "," . $lng . "&amp;zoom=11&amp;size=600x600);'>\n";
	$output .= "<h1>Bike Report: " . $cityName . "</h1>\n";
	$output .= "<!--header--></div>\n";

	$output .= "<div id='navigation'>\n";
	$output .= "<ul>\n";
	$output .= "<li id='navDate'>" . timeStamp($json) . "</li>";

	$output .= "<li><a href='index.html' title='Try Another City'>Try Another City</a></li>";

	if($country == "Canada" || $country == "United States")
	{
		$output .= "<li><a target='_blank' href='" . getRadarMap($radarKey, $json->latitude, $json->longitude) . "' title='Radar'>Radar</a></li>"; // Weather radar map.
	}

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
		$output .= "<h1>Right now:</h1>\n";

		$output .= "<!--left--></div>\n";

		$output .= "<div id='right'>\n";

			$output .= "<div class='topTable'>\n";

				$output .= "<div class='topRow'>\n";

						$output .= "<div class='topCell'  id='bigMeta'>\n";
						if (function_exists('meta'))
						{
							if($oneway === "true")
							{
								$output .= "<h1>" . meta($json, $units, 0, $json->currently->time, $onewayCompass) . "&#37; </h1>";
							}
							if($oneway === "false")
							{
								$output .= "<h1>" . meta($json, $units, 0, $json->currently->time) . "&#37; </h1>";
							}
						}
						else
						{
							$output .= "<h1>N/A</h1>";
						}

						$output .= "<b>Overall</b>\n";
						$output .= "<!--topCell(bigMeta)--></div>\n";


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
						<div id=\"thermometer\">
                			<div class=\"track\">

                    			<div class=\"max\">
                        			<div class=\"amount\">" . $thermoMaxValue . "</div>
                    			</div>
                    			
                    			<div class=\"mid\">
                        			<div class=\"amount\">" . $thermoMidValue . "</div>
                    			</div>
                    			
                    			<div class=\"progress\">
                        			<div class=\"amount\" style=\"display: block;\">" . round($temperature) 
                        			. "</div>
                    			</div>

                    			<div class=\"min\">
                        			<div class=\"amount\">" . $thermoMinValue . "</div>
                    			</div>

                			</div>
            			</div>";

					$output .= thermometer($units);
					$output .= "<b>Temperature</b><br/><small>Apparent Temp: " . round($json->currently->apparentTemperature) . $tempSuffix  . "</small>\n";
					$output .= "<!--topCell(bigTemperature)--></div>\n";

					$output .= "<div class='topCell' id='bigCompass'>\n";

						$output .= rotateArrow($windBearing);
						$output .= "<b>Wind: " . round($windSpeed) . " " . speedUnits($units) . "  (" . compass($windBearing) . ")</b><br/>\n";

					$output .= "<!--bigCompass--></div>\n";

					$output .= "<div class='topCell' id='bigConditions'>\n";

						if( ($todayIcon == "cloudy") && (periodOfDay($json, $json->currently->time) != 0) ) 
						{
							$todayIcon = "cloudy-night";
						}

						$output .= "<!--Novacons Weather Icons, by Chet Design: digitalchet.deviantart.com/art/Novacons-Weather-Icons-13133337-->\n";
						$output .= "<img src='graphics/icons/" . $todayIcon . ".png' alt='Current Weather: " . $todayIcon . "' id='weatherIcon' height='100' width='100'/><br/>";
						$output .= "<p><b>Today's Conditions:</b> " . $currently . ".  " . $weeklyWeather[0][0] . "<br/>\n";

					$output .= "<!--bigCondition--></div>\n";	

				$output .= "<!--topRow--></div>\n";	

		$output .= "<!--topTable--></div>";	

		$output .= "<!--right--></div>\n";	

		$output .= "<div style='clear: both;'></div>\n";

		$output .= "<!--topContent--></div>\n";

		$output .= "<div id='below'>";

		$hoursToReport = array(2, 4, 8, 12);
		$output .= makeHourlyReport($json, $units, $hoursToReport, $startTime, $endTime, $unitSettings, $sunTimes);

		$output .= $weekAndGraph[1]; // Print out the Weekly Summary
		$output .= "<!--below--></div>";

		print $output;

		$output = "";

	$output .= "<!--content--></div>\n";

	$output .= "<div id='footer'>\n";
	$output .= "Copyright 2014";
	$output .= "<!--footer--></div>\n";

$output .= "<!--container--></div>\n";

$output .= "//$output .= \"<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-57342744-1', 'auto');
  ga('send', 'pageview');

</script>\";";

$output .= "</body>\n";
$output .= "</html>";
print $output;
?>