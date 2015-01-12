<?php

function reportLights($arr)
{
	// Returns a string about light-related warnings for a trip
	// arr: [0]: lightsNeeded, [1]: lightsAtStart

	//print "reportLights--needed, atStart: " . $arr[0] . " / " . $arr[1] . "<br/>";

	if($arr[0] == 1 && $arr[1] == 1)
	{
		$lightRet = "It will be dark when you start riding--your bike needs lights for this trip";

	}
	elseif($arr[0] == 1 && $arr[1] == 0)
	{
		$lightRet = "It will be dark later on during your ride--equip your bike with lights";
	}
	elseif($arr[0] == 0 && $arr[1] == 0)
	{
		$lightRet = "It won't be dark during any time that your ride is planned, so lights aren't needed";
	}
	else
	{
		$lightsRet = "";
	}
	return $lightRet;
}

function reportTemp($recommendArr, $tempSuffix)
{
	// Returns a string with temperature analysis
	$recOutput = "";

	// Get temperature recommendations
	$evalTemperature = $recommendArr;

	// IF MAX TEMP IS FROM A TIMESTAMP MORE THAN 24 HOURS OUT, USE THE DATE, BUT NOT THE TIME
	// ELSE, USE JUST THE TIME
	// [3] maxtime
	// [4] mintime
	//print "ReportTemp: max temp, time: " . $evalTemperature[0] . " / " . gmdate("D F j, Y, g:i a", $evalTemperature[3]) . "<br/>";
	//print "ReportTemp: min temp, time: " . $evalTemperature[1] . " / " . gmdate("D F j, Y, g:i a", $evalTemperature[4]) . "<br/>";
	$tempReportFlag = 0; // 0 means use same-day time formatting, 1 means use a larger more formal format for more distant reports
	if(round($evalTemperature[0]) == round($evalTemperature[1]))
	{
		// Max and min are the same, so give a generic statement.
		$recOutput .= "<li>Temperatures will remain steady at " . round($evalTemperature[0]) . $tempSuffix . "</li>";
	}
	else
	{
		// If we have distinct max and min temperatures
		if($evalTemperature[3] > ($startTime + (3600*12)))
		{
			// Than max temp will occur beyond 12 hours of start
			$recOutput .= "<li>Temperatures will peak at " . round($evalTemperature[0], 0) . $tempSuffix . " today " . gmdate("\a\\t g:i a", $evalTemperature[3]); 
			$tempReportFlag = 1;
		}
		else
		{
			// Than max temp will occur within 24 hours of start
			$recOutput .= "<li>Temperatures will peak at " . round($evalTemperature[0], 0) . $tempSuffix . " at " . gmdate("g:i a l", $evalTemperature[3]) . " today"; 
		}
		if($evalTemperature[4] > ($startTime + (3600*12)))
		{
			// Than min temp will occur beyond 12 hrs
			$recOutput .= ", and go as low as " . round($evalTemperature[1]) . $tempSuffix;
			if($tempReportFlag = 0)
			{
				$recOutput .=  gmdate("g:i a", $evalTemperature[4]) . " today</li>";
			}
			else
			{
				$recOutput .=  gmdate(" \a\\t g:i a", $evalTemperature[4]) . "</li>";	
			}
		}
		else
		{
			// min temp happens within 12 hrs
			$recOutput .= ", and go as low as " . round($evalTemperature[1]) . $tempSuffix . " at "; 
			if($tempReportFlag = 0)
			{
				$recOutput .= gmdate("g:i a", $evalTemperature[4]) . " today</li>";
			}
			else
			{
				$recOutput .= gmdate("g:i a \o\\n l ", $evalTemperature[4]) . "</li>";
			}
		}
	}
	return $recOutput;
}

function reportPrecip($recommendArr, $currentTime, $realPrecipThreshold, $heavyPrecipThreshold)
{
	// Returns a string with precipitation analysis
	$recOutput = "";
	$fineFlag = 0; // If this stays unaffected, a 'things are fine' message will result.

	// Precipitation 
	$evalPrecip = $recommendArr;

	// evalPrecip array contains: $maxIntensity, $maxTime, $maxType, $maxAccumulation, $tempAtMaxTime
	$maxPrecipIntensity = $evalPrecip[0];
	$maxPrecipTime = $evalPrecip[1]  + (3600 * $json->offset);
	$maxPrecipType = $evalPrecip[2];
	$maxPrecipAccumulation = $evalPrecip[3];
	$maxPrecipTemp = $evalPrecip[4];
	$maxPercent = $evalPrecip[5];

	$percentWord = getPercentWord($maxPercent);
	$dateToUse = soonOrLater($currentTime, $startTime, $maxPrecipTime);

	if($dateToUse == 2)
	{
		// 2 means $maxPrecipTime is within a short while of starting, and the trip is starting soon
		// Use a short form (e.g. "3pm"), since they're leaving soon, e.g. within a few hours.
		$dateToUse = gmdate("\a\\t ga", $maxPrecipTime);
	}
	elseif($dateToUse == 1)
	{
		// 1 means $maxPrecipTime is within a short while of starting, but the trip isn't scheduld to start for a while
		// Use the full date, because somebody's likely planning a trip some time in the future and we don't want confusion.
		$dateToUse = gmdate("D \a\\t ga", $maxPrecipTime);
	}
	else
	{
		// 0 means $maxPrecipTime isn't going to happen within a short while of starting, period.
		// Use the full date.
		$dateToUse = gmdate("M j \a\\t ga", $maxPrecipTime);	
	}


	if($maxPrecipIntensity > 0 && $maxPrecipIntensity < $realPrecipThreshold)
	{
		// Then it's just drizzling or whatever.  
		if($maxPrecipType == 'snow')
		{
			$recOutput .= "<li>" . $percentWord . " snow on " . $dateToUse . " nothing much to be worried about.</li>";
		}
		if($maxPrecipType == 'rain')
		{
			$recOutput .= "<li>" . $percentWord . " rain just a little on " . $dateToUse . "Carry on--nothing to see here.</li>";
		}
		$fineFlag = 1;
	}

	if($maxPrecipIntensity > $realPrecipThreshold)
	{
		if($maxPrecipIntensity > $heavyPrecipThreshold)
		{
			// if rain
			// give SERIOUS rain warning and 'batton down the hatches' type gear recommendation, hope you've got fenders etc.
			if($maxPrecipType == 'rain')
			{
				$recOutput .= "<li>" . $percentWord . " rain considerably, especially " . $dateToUse . "--bring proper rain gear with you.</li>";			
			}
			// if snow
			if($maxPrecipType == 'snow')
			{
				// give snowstorm type warning and gear recomendations for bike + person
				$recOutput .= "<li>" . $percentWord . " snow considerably, especially at " . $dateToUse . "--be ready for it!</li>";
			}
		}
		else
		{
			// if rain
			// Then there's real rain--give warning and recommend gear
			if($maxPrecipType == 'rain')
			{
				$recOutput .= "<li>" . $percentWord . " rain on " . $dateToUse . " --bring some gear for it.</li>";
			}
			if($maxPrecipType == 'snow')
			{
				$recOutput .= "<li>" . $percentWord . " snow on " . $dateToUse . "--bring some gear for it.</li>";
				// Then there's real snow--give warning and accumulation for that one hour
			}
		}
		$fineFlag = 1;
	}
	if($fineFlag == 0)
	{
		$recOutput .= "<li>There won't be any precipitation of note, so no need for wet-weather gear.</li>";
	}

	return $recOutput;
}

function iterate($json, $startTime, $endTime)
{
	// Returns an associative array with recommendations about cycling conditions from $starTime to $endTime.
	// This is the master function for iterating $json data for recommendation purposes.

	// Initialize  vars
	$maxIntensity = 0; $maxTime = 0; $maxType = 'rain'; $maxAccumulation = null; $tempAtMaxTime = 0; $maxPercent = 0;
	$maxTemp = -100000; $minTemp = 100000; $maxHumidity = 0; $maxTempTime = 0; $minTempTime = 0; $maxHumidTime = 0;
	$lightsNeeded = 0; $lightsAtStart = 0; $lightsEvalFlag = 0;
	$dayStartFlag = 0;
	// $lightsForDusk = 0;

	$tripLength = $endTime - $startTime;

	//print "start, end, currently, abs: " . gmdate("F j, Y, G:i", $startTime) . " / " . gmdate("F j, Y, G:i", $endTime) . " / " . gmdate("F j, Y, G:i", $json->currently->time + (3600 * $json->offset))  . " / " . abs($startTime - ($json->currently->time + (3600 * $json->offset))) . "<br/>";

	// Check Currently data if $startTime is within 10 mins of now
	if(abs($startTime - ($json->currently->time + (3600 * $json->offset))) < 600)
	{
		// Temperature
		if($json->currently->temperature > $maxTemp)
		{
			$maxTemp = $json->currently->temperature;
			$maxTempTime = $json->currently->time  + (3600 * $json->offset);
		}
		if($json->currently->temperature < $minTemp)
		{
			$minTemp = $json->currently->temperature;
			$minTempTime = $json->currently->time  + (3600 * $json->offset);
		}
		if($json->currently->humidity > $maxHumidity)
		{
			$maxHumidity = $json->currently->humidity;
			$maxHumidTime = $json->currently->time + (3600 * $json->offset);
		}

		// Lights
		$periodCurrently = periodOfDay($json, ($json->currently->time + (3600 * $json->offset)));
		//print "period currently: " . $periodCurrently . "<br/>";
		if(($periodCurrently > 0) && ($periodCurrently  < 3))
		{
			$lightsNeeded = 1;
			$lightsAtStart = 1;
		}
	}
	// Iterate Hourly
	//foreach($json->hourly->data as $datum)
	for($i = 0; $i < 48; $i++)
	{
		$datum = $json->hourly->data[$i];
		//print "i: " . $i . "<br/>";
		//print "hour: " . gmdate("F j, Y, G:i", $datum->time) . "<br/>";
		//print "start: " . gmdate("F j, Y, G:i", $startTime) . "<br/>";
		//print "corrt: " . gmdate("F j, Y, G:i", ($datum->time + (3600 * $json->offset))) . "<br/><br/>";
		// STARTS 1 BEHIND (E.G. 5AM FOR A 6AM START) AND IS 1 SHORT (E.G. 11 HOURS FOR 12 HOUR TRIP)
		if(($datum->time + (3600 * $json->offset) >= $startTime) && ($datum->time + (3600 * $json->offset) <= $endTime))
		{
			//print "hour, maxtime, cur intens, max intens: " . date("F j, Y, G:i", $datum->time) . " / " . date("F j, Y, G:i", $maxTime) . " / " . $datum->precipIntensity . " / " . $maxIntensity . "<br/>"; 

			// Precipitation
			if($datum->precipIntensity >= $maxIntensity)
			{
				// This is the most intense precipitation period so far
				$maxIntensity = $datum->precipIntensity;
				$maxTime = $datum->time + (3600 * $json->offset);
				$maxType = $datum->precipType;
				$tempAtMaxTime = $datum->temperature;

				if($maxType == "snow")
				{
					$maxAccumulation = $datum->precipAccumulation;
				}
				else
				{
					$maxAccumulation = null;
				}	
			}
			if($datum->precipProbability > $maxPercent)
			{
				$maxPercent = $datum->precipProbability;
			}

			//print "hour, temp: " . gmdate("F j, Y, G:i", $datum->time + (3600 * $json->offset)) . " / " . $datum->temperature . "<br/>";
			// Temperature
			if($datum->temperature > $maxTemp)
			{
				$maxTemp = $datum->temperature;
				$maxTempTime = $datum->time  + (3600 * $json->offset);
			}
			if($datum->temperature < $minTemp)
			{
				$minTemp = $datum->temperature;
				$minTempTime = $datum->time  + (3600 * $json->offset);
			}
			if($datum->humidity > $maxHumidity)
			{
				$maxHumidity = $datum->humidity;
				$maxHumidTime = $datum->time + (3600 * $json->offset);
			}
		}

		if($lightsEvalFlag === 0)
		{
			$periodCurrently = periodOfDay($json, ($datum->time + (3600 * $json->offset)));
			$periodAtStart = periodOfDay($json, $startTime);
			$periodAtEnd = periodOfDay($json, $endTime);
			$darkAtStart = ($periodAtStart > 0 && $periodAtStart < 3);
			$darkAtEnd = ($periodAtEnd > 0 && $periodAtEnd < 3);
			//print "time, period: " .   gmdate("D M j \a\\t g:ia", $datum->time + (3600 * $json->offset)) . " / " . $periodCurrently . "<br/>\n";
			//print "darkAtStart, darkAtEnd: " . $darkAtStart . " / " . $darkAtEnd . "<br/>\n";
			//if(($periodCurrently > 0) && ($periodCurrently  < 3) && ($datum->time + (3600 * $json->offset) >= $startTime) && ($datum->time + (3600 * $json->offset) <= $endTime))
			if($darkAtStart || $darkAtEnd)
			{
				if($darkAtStart == True && $darkAtEnd == False)
				{
					$lightsAtStart = 1;
					$lightsNeeded = 1;
				}
				if($darkAtStart == False && $darkAtEnd == True)
				{
					$lightsNeeded = 1;
				}
			}
			$lightsEvalFlag = 1;
		}
	}
	//$diff = $endTime - $json->currently->time;
	//print "end, current, diff: " . $endTime . " / " . $json->currently->time . " / " . $diff . "<br/>";
	// Iterate daily
	if($endTime - $json->currently->time > 172800) // If the end of the trip is at least 48 hours in the future
	{
		foreach($json->daily->data as $datum)
		{
			$todayTime = gmdate("j", $datum->time + (3600 * $json->offset));
			$startDay = gmdate("j", $startTime);
			$endDay = gmdate("j", $endTime);

			//print "DAY: " . gmdate("F j, Y, G:i", $datum->time) . "<br/>";

			if(strcmp($todayTime, $startDay) === 0)
			{
				$dayStartFlag = 1;
			}
			if($dayStartFlag == 1)
			{
				//print "DAY, maxtime, cur intens, max intens: " . gmdate("F j, Y, G:i", $datum->time) . " / " . gmdate("F j, Y, G:i", $maxTime) . " / " . $datum->precipIntensity . " / " . $maxIntensity . "<br/>"; 

				// Precipitation
				if($datum->precipIntensity >= $maxIntensity)
				{
					// This is the most intense precipitation period so far
					$maxIntensity = $datum->precipIntensity;
					$maxTime = $datum->time;
					$maxType = $datum->precipType;
					$tempAtMaxTime = $datum->temperature;

					if($maxType == "snow")
					{
						$maxAccumulation = $datum->precipAccumulation;
					}
					else
					{
						$maxAccumulation = null;
					}	
				}
				if($datum->precipProbability > $maxPercent)
				{
					$maxPercent = $datum->precipProbability;
				}
			}
			// Temperature
			if($dayStartFlag == 1)
			{
				if($datum->temperatureMax > $maxTemp)
				{
					$maxTemp = $datum->temperatureMax;
					$maxTempTime = $datum->temperatureMaxTime  + (3600 * $json->offset);
				}
				if($datum->temperatureMin < $minTemp)
				{
					$minTemp = $datum->temperatureMin;
					$minTempTime = $datum->temperatureMinTime  + (3600 * $json->offset);
				}
				if($datum->humidity > $maxHumidity)
				{
					$maxHumidity = $datum->humidity;
					$maxHumidTime = $datum->time + (3600 * $json->offset);
				}
				//print "DAY curr time, max temp time, max temp: " . gmdate("F j, Y, G:i", $datum->time + (3600 * $json->offset)) . " / " . gmdate("F j, Y, G:i", $maxTempTime + (3600 * $json->offset)) . " / " . $maxTemp . "<br/><br/>"; 
				//print "min, minTempTime: " . gmdate("F j, Y, G:i", $minTempTime) . " / " . $minTemp . "<br/><br/>";
			}
			if(strcmp($todayTime, $endDay) == 0)
			{
				$dayStartFlag = 0;
			}
		}
	}
	//print "FINAL: min, minTempTime: " . gmdate("F j, Y, G:i", $minTempTime) . " / " . $minTemp . "<br/><br/>";
			
	$precipRet = array($maxIntensity, $maxTime, $maxType, $maxAccumulation, $tempAtMaxTime, $maxPercent);
	$tempRet = array($maxTemp, $minTemp, $maxHumidity, $maxTempTime, $minTempTime, $maxHumidTime);
	$lightsRet = array($lightsNeeded, $lightsAtStart);

	return array($precipRet, $tempRet, $lightsRet);
}

function recommendPeriod($hours)
{
	// Returns a string describing a time period, initially input as $hours
	if($hours <= 24)
	{
		if($hours === 1)
		{
			return $hours . " hour";
		}
		else
		{
			return $hours . " hours";
		}
	}
	if($hours % 24 == 0)
	{
		return ($hours/24) . " days";
	}
	if($hours % 24 !== 0)
	{
		return floor($hours/24) . " days, " . ((($hours/24) - floor($hours/24)) * 24) . " hours";
	}
	// Should not get here
	return -1;
}

function bigintval($value) 
{
	// Courtesy of "defines": http://stackoverflow.com/questions/990406/php-intval-equivalent-for-numbers-2147483647
  $value = trim($value);
  if (ctype_digit($value)) {
    return $value;
  }
  $value = preg_replace("/[^0-9](.*)$/", '', $value);
  if (ctype_digit($value)) {
    return $value;
  }
  return 0;
}

function checkAlerts($json)
{
	// Returns an array containing [0]: lights-on alerts, [1]: weather alerts

	/*
		Redesign: checkAlerts($time, $icon)
		-take in $time--make sure it's converted to local time.  
		- take in $json->icon.  Default will be 0--this will be necessary for minutely processing

		- workings: determine if time is day/night/etc.

		Return an array ($alertFlag, $warnings), where $alertFlag == 1 means to use lights, and $warnings is a string
		with reasons why you use your lights, (e.g. fog, night, dawn, etc.)
	*/


	// Check if it's between 30 mins before dusk and 30 mins after dawn--the legal definition of 'night' for driving/road purposes in Ontario
	$icon = $json->currently->icon;
	$lights = "";
	$weather;
	$alerts = array();

	$period = periodOfDay($json, $json->currently->time);	// 0 for daytime, 1 for dusk, 2 for night, and 3 for dawn

	if($period == 2)    
	{
		// Then it's nighttime, before dawn.  
		$lights = "use lights: night";
	}
	if($period == 3)
	{
		// Then it's nighttime, after dusk.
		$lights = "use lights: dawn";
	}
	if($period == 1)
	{
		$lights = "use lights: dusk";
	}

	// Check for fog--append to any existing 
	if($icon == "fog")
	{
		if($lights != "")
		{
			$lights .= ", fog";	
		}
		else
		{
			$lights = "use Lights: fog";
		}
	}

	$weather = "";	// Placeholder until weather alerts are implemented

	array_push($alerts, $lights, $weather);

	return $alerts;
}

function soonOrLater($currentTime, $startTime, $time)
{
	// If $time occurs within $threshold of $startTime AND $startTime is less than $startThreshold from $currentTime, plus or minus
	$threshold = (3600 * 12);
	$startThreshold = (3600 * 1);

	// current: 76022

	// start: 58022	
	// time: 54800
	// start - time = 3222 (within an hour)

	// start - current = 
	//print "soonOrLater:: threshold, startThreshold, currentTime, startTime, time: " . $threshold . "/" . $startThreshold . "/" . $currentTime . "/" . $startTime . "/" . $time . "<br/>";

	if( (abs($time - $startTime) <= $threshold) && (abs($startTime - $currentTime) <= $startThreshold) )
	{
		// Then $time is happening either now or within an our of starting off
		return 2;
	}
	elseif((abs($time - $startTime) <= $threshold) && (abs($startTime - $currentTime) >= $startThreshold))
	{
		// If $time is happening within $startThreshold of $start, but the trip isn't schedule to begin for a while (e.g. a tour)
		return 1;
	}
	else
	{
		// Then $time isn't happening within $threshold of $startTime
		return 0;
	}
	// Should not get here
	return -1;
}

function getPercentWord($percent)
{
	$percent *= 100;	// Makes it easier to think of an as actual percentage.
	if($percent >= 100)
	{
		$str = "It will";
	}
	elseif($percent < 100 && $percent >= 90)
	{
		$str = "It almost certainly will";
	}
	elseif($percent < 90 && $percent >= 75)
	{
		$str = "It will likely";
	}
	elseif($percent < 75 && $percent >= 50)
	{
		$str = "It may";
	}
	elseif($percent < 50 && $percent >= 25)
	{
		$str = "It might";
	}
	elseif($percent < 25 && $percent > 0)
	{
		$str = "There's a small chance it might";
	}
	else // Should not get here
	{
		return -1;
	}

	return $str;
}

function remainingHours($startTime, $endTime)
{
	// Returns the number of WHOLE hours left in the day that $startTime transpires in.  Partial hours don't count.
	// Assumes $startTime is a time of day on the present day.  Otherwise there's no reason to use this data since we're
	// comparing between days.
	if($endTime - $startTime < (3600 * 24) ) 
	{
		return (($endTime - $startTime) / 3600);
	}
	else
	{
		return 24 - intval(gmdate(G, $startTime));
	}
}

function remainingDays($startTime, $endTime)
{
	// Returns the number of whole days left in the trip.  Assumes $startTime and $endTime are valid unix timestamps representing
	// the start and finish of a trip.
	$difference = $endTime - $startTime; // unix value of the two stamps
	$difference = floor($difference / 86400);	// number of whole days in the trip, (e.g. a 3.5 day trip will be 3 days here)

	return $difference;
}

function evalTemperature($json, $startTime, $endTime, $remainingHours, $remainingDays)
{
	// Returns max temperature, min temperature, max humidity for the specified time period
	$maxTemp = -100000; $minTemp = 100000; $maxHumidity = 0; $maxTempTime = 0; $minTempTime = 0; $maxHumidTime = 0;

	//print "<br/>TEMP start, end: " . gmdate("F j, Y, G:i", $startTime) . " / " . gmdate("F j, Y, G:i", $endTime) . "<br/>";

	if($json->currently->temperature > $maxTemp)
	{
		$maxTemp = $json->currently->temperature;
		$maxTempTime = $json->currently->time  + (3600 * $json->offset);
	}
	if($json->currently->temperature < $minTemp)
	{
		$minTemp = $json->currently->temperature;
		$minTempTime = $json->currently->time  + (3600 * $json->offset);
	}
	if($json->currently->humidity > $maxHumidity)
	{
		$maxHumidity = $json->currently->humidity;
		$maxHumidTime = $json->currently->time + (3600 * $json->offset);
	}


	foreach($json->hourly->data as $datum)
	{
		if(($datum->time + (3600 * $json->offset) >= $startTime) && ($datum->time + (3600 * $json->offset) <= $endTime))
		{
			//print "HOUR curr time, max temp time, temp, max temp: " . gmdate("F j, Y, G:i", $datum->time + (3600 * $json->offset)) . " / " . gmdate("F j, Y, G:i", $maxTempTime + (3600 * $json->offset)) . " / " . $datum->temperature . " / " . $maxTemp . "<br/>"; 
			//print "Datum->time: " . $datum->time . "<br/>";	
			if($datum->temperature > $maxTemp)
			{
				$maxTemp = $datum->temperature;
				$maxTempTime = $datum->time  + (3600 * $json->offset);
			}
			if($datum->temperature < $minTemp)
			{
				$minTemp = $datum->temperature;
				$minTempTime = $datum->time  + (3600 * $json->offset);
			}
			if($datum->humidity > $maxHumidity)
			{
				$maxHumidity = $datum->humidity;
				$maxHumidTime = $datum->time + (3600 * $json->offset);
			}
		}
	}
	foreach($json->daily->data as $datum)
	{
		if(($datum->time + (3600 * $json->offset) >= $startTime) && ($datum->time + (3600 * $json->offset) < $endTime))
		{
			if($datum->temperatureMax > $maxTemp)
			{
				$maxTemp = $datum->temperatureMax;
				$maxTempTime = $datum->temperatureMaxTime  + (3600 * $json->offset);
			}
			if($datum->temperatureMin < $minTemp)
			{
				$minTemp = $datum->temperatureMin;
				$minTempTime = $datum->temperatureMinTime  + (3600 * $json->offset);
			}
			if($datum->humidity > $maxHumidity)
			{
				$maxHumidity = $datum->humidity;
				$maxHumidTime = $datum->time + (3600 * $json->offset);
			}
			//print "DAY curr time, max temp time, max temp: " . gmdate("F j, Y, G:i", $datum->time + (3600 * $json->offset)) . " / " . gmdate("F j, Y, G:i", $maxTempTime + (3600 * $json->offset)) . " / " . $maxTemp . "<br/>"; 
		}
	}

	//print "TEMP DONE: maxtemp, mintemp, maxtemptime, mintemptime: " . $maxTemp . "/" . $minTemp . "/" .gmdate("F j, Y, G:i", $maxTempTime) . "/" . gmdate("F j, Y, G:i", $minTempTime) . "<br/>";
	return array($maxTemp, $minTemp, $maxHumidity, $maxTempTime, $minTempTime, $maxHumidTime);
}

function evalPrecipitation($json, $startTime, $endTime)
{
	// Returns max precipitation intensity, type, accumulation (if snow), temp at max precip time, and time of max
	// NOTE: precipIntensity represents liquid inches per hour at the exact time of maximum precipitation.

	// Initialize values
	$maxIntensity = 0; $maxTime = 0; $maxType = 'rain'; $maxAccumulation = null; $tempAtMaxTime = 0; $maxPercent = 0;

	//print "INSIDE start, end: " . gmdate("F j, Y, G:i", $startTime) . " / " . gmdate("F j, Y, G:i", $endTime) . "<br/>";

	foreach($json->hourly->data as $datum)
	{
		if(($datum->time + (3600 * $json->offset) >= $startTime) && ($datum->time + (3600 * $json->offset) <= $endTime))
		{
			//print "hour, maxtime, cur intens, max intens: " . gmdate("F j, Y, G:i", $datum->time) . " / " . gmdate("F j, Y, G:i", $maxTime) . " / " . $datum->precipIntensity . " / " . $maxIntensity . "<br/>"; 
			if($datum->precipIntensity >= $maxIntensity)
			{
				// This is the most intense precipitation period so far
				$maxIntensity = $datum->precipIntensity;
				$maxTime = $datum->time;
				$maxType = $datum->precipType;
				$tempAtMaxTime = $datum->temperature;

				if($maxType == "snow")
				{
					$maxAccumulation = $datum->precipAccumulation;
				}
				else
				{
					$maxAccumulation = null;
				}	
			}
			if($datum->precipProbability > $maxPercent)
			{
				$maxPercent = $datum->precipProbability;
			}
		}
	}
	foreach($json->daily->data as $datum)
	{
		if(($datum->time + (3600 * $json->offset) >= $startTime) && ($datum->time + (3600 * $json->offset) < $endTime))
		{
			//print "DAY, maxtime, cur intens, max intens: " . gmdate("F j, Y, G:i", $datum->time) . " / " . gmdate("F j, Y, G:i", $maxTime) . " / " . $datum->precipIntensity . " / " . $maxIntensity . "<br/>"; 
			if($datum->precipIntensity >= $maxIntensity)
			{
				// This is the most intense precipitation period so far
				$maxIntensity = $datum->precipIntensity;
				$maxTime = $datum->time;
				$maxType = $datum->precipType;
				$tempAtMaxTime = $datum->temperature;

				if($maxType == "snow")
				{
					$maxAccumulation = $datum->precipAccumulation;
				}
				else
				{
					$maxAccumulation = null;
				}	
			}
			if($datum->precipProbability > $maxPercent)
			{
				$maxPercent = $datum->precipProbability;
			}
		}
	}

	//print "precip data: " . $maxIntensity . " / " . $maxTime . " / " .  $maxType . " / " . $maxAccumulation . " / " . $tempAtMaxTime . " / " . $maxPercent;

	return array($maxIntensity, $maxTime, $maxType, $maxAccumulation, $tempAtMaxTime, $maxPercent);
}

function makeRecommendations($json, $startTime, $tripTime, $unitSettings)
{
	// Returns a string with recommendations for use in bikereport.net.  

	// From Darksky: A very rough guide is that a value of 0 in./hr. corresponds to no precipitation, 
	// 0.002 in./hr. corresponds to very light precipitation, 
	// 0.017 in./hr. corresponds to light precipitation, 
	// 0.1 in./hr. corresponds to moderate precipitation, 
	// and 0.4 in./hr. corresponds to heavy precipitation.
	$realPrecipThreshold = 0.017;
	$heavyPrecipThreshold = 0.04;

	$startTime = intval($startTime) + (3600 * $json->offset);
	$endTime = intval($tripTime) + $startTime;
	$currentTime = $json->currently->time + (3600 * $json->offset);
	$lengthOfTrip = recommendPeriod($tripTime/3600);
	//print "current, start, end: " . $currentTime . " / " . $startTime . " / " . $endTime . "<br/>";

	$tempSuffix = $unitSettings[1];
	$speedSuffix = $unitSettings[2];

	if($endTime < $currentTime)
	{
		// Diagnostic url for testing this:
		// http://www.brianneary.net/EXPERIMENTS/newBikeReport/bikeReport.php?cityName=Toronto&lat=43.653226&lng=-79.3831843&locality=Ontario&country=Canada&units=CA&oneway=false&compass=0&tripStart=1417664184&tripLength=3600&tripSpeed=20
		return "<b>The requested trip period is passed, and no data are available to analyze it.</b><br/><br/>";
	}

	// Build the array of recommendations
	// [0]: precipitation, [1]: temperature, [2]: lights
	$recommendArr = iterate($json, $startTime, $endTime);

	// Get precipitation recommendations
	//$evalPrecip = $recommendArr[0];

	// Get temperature recommendations
	//$evalTemperature = $recommendArr[1];

	//$evalLights = $recommendArr[2];

	/*

	Start Recomendations Output

	*/

	$recOutput = "";
	$recOutput .= "<br/><b>Here are some recomendations for a trip of " . $lengthOfTrip . ", from " . gmdate("D M j \a\\t g:ia", $startTime) . " to " . gmdate("D M j \a\\t g:ia", $endTime) . ":</b> <br/>";
	// 'g:ia'

	$recOutput .= "<ul>";

    $precipRecommendation = reportPrecip($recommendArr[0], $currentTime, $dateToUse, $realPrecipThreshold, $heavyPrecipThreshold);
    $recOutput .= $precipRecommendation;

    $tempRecommendation = reportTemp($recommendArr[1], $tempSuffix);
    $recOutput .= $tempRecommendation;

    $lightsRecommendation = reportLights($recommendArr[2]);
    if($tripTime > (3600*24))
    {
    	$recOutput .= "<li>Since this trip is at least 24 hours long, it might be best to bring lights.  Use your best judgment.</li>";
    }
    else
    {
    	if($lightsRecommendation !== "")
    	{
    		$recOutput .= "<li>" . $lightsRecommendation . "</li>";
    	}
    }

/*
	$alerts = checkAlerts($json);

	if($alerts[0] != "")
	{
		$recOutput .= "<li>Warning: " . $alerts[0] . "</li>";
	}
*/
	$recOutput .= "</ul>";

	return $recOutput;
}

?>