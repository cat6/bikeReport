<?php

// Utilities for the Bike Report engine

function getSunUpDownTimes($json)
{
	// Returns an array with user-friendly data about today's sunrise and sunset, or -1 if a timestamp input was invalid
	// Assumes a valid $json weather data object
	$sunRise = $json->daily->data[0]->sunriseTime + (3600 * $json->offset);
	$sunDown = $json->daily->data[0]->sunsetTime + (3600 * $json->offset);

	if($sunRise <= 100000)
	{
		//print "Sunrise: " . $sunRise . " / " . gmdate('g:ia', $sunRise) . " / Sunset: " . $sunDown . " / " . gmdate('g:ia', $sunDown);	// Return a nice string
		return array(-1, gmdate('g:ia', $sunDown));
	}
	else
	{
		//print "Sunrise: " . $sunRise . " / " . gmdate('g:ia', $sunRise) . " / Sunset: " . $sunDown . " / " . gmdate('g:ia', $sunDown);	// Return a nice string
		return array(gmdate('g:ia', $sunRise), gmdate('g:ia', $sunDown));
	}
	// Should not get here
	return -1;
}

function inchesToCM($val)
{
	return $val * 2.54;
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

$radarURL = "http://api.wunderground.com/api/" . $radarKey . "/radar/image.gif?centerlat=" . $lat . "&amp;centerlon=" . $lng . "&amp;radius=20&amp;width=280&amp;height=280&amp;newmaps=1";

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
	// foreach
	TESTING ($camArray as $cam)
	{
		print "test: " . $cam . "\n";
	}
*/
	return $camArray;
}

function timeStamp($json)
{
	$currentTime = $json->currently->time;	// Unix time stamp for local time
	$localTime = $currentTime + ($json->offset * 3600);	// Adjust time using hours offset
	return gmdate('D M\ jS, g:ia', $localTime);	// Return a nice string
}

function periodOfDay($json, $localTime)
{
	// returns 0 for daytime, 1 for dusk, 2 for night, and 3 for dawn
	$sunriseToday = $json->daily->data[0]->sunriseTime + (3600 * $json->offset);
	$sunsetToday = $json->daily->data[0]->sunsetTime + (3600 * $json->offset);

	$ret = -2;	// Default; daytime.

	// Two circumstances it could be night.  Before midnight, both sunrise and sunset are behind us
	// After midnight, both sunrise and sunset are ahead of us
	if(($localTime > ($sunriseToday + 1800)) && ($localTime < ($sunsetToday - 1800)))
	{
		// Day.  Sunrise is a large negative, like -18000, 0, or a small positive, like 3600.
		$ret = 0;
	}
	elseif( ($localTime  > $sunriseToday) && ($localTime < $sunriseToday + 1800))
	{
		// Dawn
		$ret = 3;
	}
	elseif(($localTime  < $sunsetToday) && ($localTime  > ($sunsetToday - 1800)))
	{
		// Dusk
		$ret = 1;
	}
 	elseif((($localTime > $sunriseToday) && ($localTime > $sunsetToday) ) || (($localTime < $sunsetToday) && ($localTime < $sunsetToday))  )
	{
		// Night
		$ret = 2;
	}
	else
	{
		// Should not get here.
		$ret = -1;
	}

/*
	// Simple version:
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
*/
// TESTING
	//print "periodOfDay, localTime, sunriseToday, sunSetToday: " . $ret . " / " . $localTime . " / " . $sunriseToday . " / " . $sunsetToday . "<br/>";

	return $ret;
}

function psaImage($imageName, $alt)
{
	// Returns the <img> tag for a PSA image for the page, whose name is $imageName
	// Functionalizing the process allows for the later possibility of rotating images (e.g. from a hard-coded array
	// or external source) on a regular basis.
	// Assumes that $imageName is the name of a valid image in /graphics/psa/, and that $alt is a string of descriptive text.
	return "<img src='graphics/psa/" . $imageName . "' alt='" . $alt . "' id='psaImage' width='100%' height='100%' />";
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

	$thermometerOutput = "";

	$thermometerOutput .= "   <script>
        //Based originally on a positive-only \"fundraising\" type thermometer by \"Geeky John\" http://jsfiddle.net/GeekyJohn/vQ4Xn/


        function percentage(goalAmount, minAmount, progressAmount) {
            var range, compensated, percentageAmount;

            range = (goalAmount + 274) - (minAmount + 274);
            compensated = (progressAmount + 274) - (minAmount + 274);
            percentageAmount = (progressAmount + 274) - (minAmount + 274) / range;

            percentageAmount = compensated / range;
            percentageAmount = percentageAmount * 100;

            return percentageAmount;
        }
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
        function thermometer(unitChoice, goalAmount, progressAmount, minAmount, animate) {
            \"use strict\";

            var \$thermo = \$(\"#thermometer\"),
                \$progress = \$(\".progress\", \$thermo),
                \$goal = \$(\".max\", \$thermo),
                \$min = \$(\".min\", \$thermo),
                \$mid = \$(\".mid\", \$thermo),
                percentageAmount, minHeight,
                compensated, midAmount, range, rangePercent;

            // Metric units by default
            unitChoice = unitChoice || 'C';

            // Adjustment value for the midline marker line (typically the zero on a thermometer)
            // Change this to move your 'zero' value up or down the thermometer to compensate for size changes
            var minAdjust = -25;

            // Animate by default
            var animate = typeof Boolean !== 'undefined' ? animate : true;

            goalAmount = goalAmount || parseFloat(\$goal.text()),
            minAmount = minAmount || parseFloat(\$min.text()),
            midAmount = midAmount || parseFloat(\$mid.text()),
            progressAmount = progressAmount || parseFloat(\$progress.text()),
            percentageAmount = Math.min(Math.round(progressAmount / goalAmount * 1000) / 10, 100); //make sure we have 1 decimal point

            //let's format the numbers and put them back in the DOM
            \$goal.find(\".amount\").text(goalAmount + unitChoice);
            \$progress.find(\".amount\").text(progressAmount + unitChoice);
            \$mid.find(\".amount\").text(midAmount + unitChoice);
            \$min.find(\".amount\").text(minAmount + unitChoice);

            percentageAmount = percentage(goalAmount, minAmount, progressAmount);

            minHeight = percentage(goalAmount, minAmount, 0);

            $(\"#thermometer .mid\").css(\"bottom\", ((minHeight + minAdjust) + \"%\")); 

            //let's set the progress indicator
            \$progress.find(\".amount\").hide();
            
            if (animate !== false) {
                \$progress.animate({
                    \"height\": percentageAmount + \"%\"
                }, 1200, function () {
                    \$(this).find(\".amount\").fadeIn(500);
                });
            } else {
                \$progress.css({
                    \"height\": percentageAmount + \"%\"
                });
                \$progress.find(\".amount\").fadeIn(500);
            }
        }

        \$(document).ready(function () 
        {
            // Call thermometer() without arguments to have it read from the DOM
            thermometer('" . $unitChoices[1] . "');
            // ...or with parameters if you want to update it using JavaScript.
            // You can update live, and choose whether to show the animation
            // (you might not if the updates are relatively small).
            //thermometer(50, -21, -30, true);
        });
    </script>";

	return $thermometerOutput;
}


function bookmarkMe()
{
	$bookmarkMeOutput = "
		<script>
		// Credit: http://stackoverflow.com/questions/10033215/add-to-favorites-button
    	\$(function() {
	        \$('#bookmarkme').click(function() {
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

?>