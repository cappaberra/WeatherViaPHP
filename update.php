<?php 
	// do not forget to setup the cron-job in order to make this process automated.
	// Required data from Weather Underground
	
	// you can define variables here or via URL, make sure to comment out or delete hardcoded variables to get them from URL
//	start of hardcoded variables
	// API key https://www.wunderground.com/weather/api/
	$wuAPI = "ebEXAMPLEab"; // change contents, keep quotes
	$wuID = "Kexample37";

	// Data needed from PWS weather
	$pwsID = "FexampleER"; // change contents, keep quotes
	// $pwsID = filter_var("FexampleER", FILTER_SANITIZE_STRING); // Example of sanitized variable, worth trying if you run into errors
	$psw = "1ExAmPlE."; // seems to dislike commas, try simplier password in case you get ID/pass error (periods "." are ok)
// 	End of hardcoded variables

	// get missing data from URL (if available)
	if(!isset($wuAPI))
		$wuAPI = filter_input(INPUT_GET,"wuAPI",FILTER_SANITIZE_STRING);
	if(!isset($wuID))
		$wuID = filter_input(INPUT_GET,"wuID",FILTER_SANITIZE_STRING);
	if(!isset($pwsID))
		$pwsID = filter_input(INPUT_GET,"pwsID",FILTER_SANITIZE_STRING);
	if(!isset($psw))
		$psw = filter_input(INPUT_GET,"psw",FILTER_SANITIZE_STRING);

	// start of code

	if(isset($wuAPI) && isset($wuID) && isset($pwsID) && isset($psw)){
		$wuData = file_get_contents('https://api.weather.com/v2/pws/observations/current?stationId='.$wuID.'&format=json&units=e&apiKey='.$wuAPI);
		$data = json_decode($wuData,true);


        if(isset($data['observations'])){
			$date = new DateTime("@" . $data['observations'][0]['epoch']);

			$delta = time() - $data['observations'][0]['epoch'];

			if($delta > 2000){ // to get rid of old data spikes

				echo("The data from ".$delta." seconds ago was too old for trasfer, will retry on next attempt");

			} else {
				$url = "http://www.pwsweather.com/pwsupdate/pwsupdate.php?ID=". $pwsID ."&PASSWORD=". urlencode($psw) ."&dateutc=" . $date->format('Y-m-d+H:i:s') .
					($data['observations'][0]['winddir'] >= 0 ? "&winddir=" . $data['observations'][0]['wind_degrees'] : '' ) .
					($data['observations'][0]['imperial']['windSpeed'] >= 0 ? "&windspeedmph=" . $data['observations'][0]['imperial']['windSpeed'] : '' ) .
					($data['observations'][0]['imperial']['windGust'] >= 0 ? "&windgustmph=". $data['observations'][0]['imperial']['windGust'] : "" ) .
					// I would be impressed if anyone recorded temperatures close to absolute zero.
					($data['observations'][0]['imperial']['temp'] > -459 ? "&tempf=" . $data['observations'][0]['imperial']['temp'] : "" ) .
					($data['observations'][0]['imperial']['precipRate'] >= 0 ? "&rainin=" . $data['observations'][0]['imperial']['precipRate']  : "" ) .
					($data['observations'][0]['imperial']['precipTotal'] >= 0 ? "&dailyrainin=" . $data['observations'][0]['imperial']['precipTotal'] : "" ) .
					($data['observations'][0]['imperial']['pressure'] >= 0 ? "&baromin=" . $data['observations'][0]['imperial']['pressure']  : "" ) .
					($data['observations'][0]['imperial']['dewpt'] > -100 ? "&dewptf=" . $data['observations'][0]['imperial']['dewpt']  : "" ) .
					($data['observations'][0]['humidity'] <> '-' ? "&humidity=" . $data['observations'][0]['humidity']  : "" ) .
						"&softwaretype=ebviaphpV0.3&action=updateraw";
				
				
				$pwsdata =  file_get_contents($url);
				
				$results = explode("\n", $pwsdata);

				switch ($results[6]){ // 6 represents the 7th line (count starts at 0) which carries useful information
					case "ERROR: Not a vailid Station ID":
						echo (
'<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>PWSweather Error</title>
</head>
<body>
<h1>We got an error from PWS weather:</h1>
<p>Your PWS weather ID (pwsID) appears to be invalid</p>
</body> </html>');
						break;
					case "ERROR: Not a vailid Station ID/Password":
						echo (
'<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>PWSweather Error</title>
</head>
<body>
<h1>We got an error from PWS weather:</h1>
<p>Your PWS account password (psw) appears to be invalid</p>
</body> </html>');
						break;
					case "Data Logged and posted in METAR mirror.":
						echo("The latest data from ".$delta." seconds ago was transfered to PWS weather station " . $pwsID);
						break;
					default:
						echo $pwsdata;
						break;
				}
			}

			
		} else {
				//http_response_code(400); // bad request 
				// we got an error
				if(isset($data['response']['error'])){
				echo (
				'<!doctype html>
				<html>
				<head>
				<meta charset="utf-8">
				<title>Weather Underground Error</title>
				</head>
				<body>
				<h1>We got an error from Weather Underground:</h1><p>');

				switch($data['response']['error']['type']){
					case "keynotfound":
						echo('Your Weather Underground API key (wuAPI) appears to be invalid');
						break;
					case "Station:OFFLINE":
						echo('Your Weather Underground Station ID (wuID) appears to be invalid');
						break;
					default:
						echo('This appears to be a temporary error, please try again later</p>');
						echo("<p>Exact Error type: " . $data['response']['error']['type'] . "</p>");
						echo("<p>Which means: " . $data['response']['error']['description']);
						break;
				}
				echo ('</p></body> </html>');

			}
		}
		
	} else {
		echo (
		'<!doctype html>
		<html>
		<head>
		<meta charset="utf-8">
		<title>Insuficient Data</title>
		</head>
		<body>
		<p>Not enough URL or Hardcoded parameters</p>
		</body>
		</html>');
	}
	
	?>
