<?php
/*
 * @version     2.7
 * @package     J.B.Weather Widget
 * @copyright   Copyright (C) 2016. All rights reserved.
 * @author      Stachethemes
*/
?>



<!-- STEP 1: INCLUDE STYLESHEET && JAVASCRIPT IN YOUR <HEAD> -->
<link rel="stylesheet" type="text/css" href="jbweather/css/style.css" />
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
<script type="text/javascript" src="jbweather/js/jbweather.js"></script>






<!-- STEP 2: INCLUDE JBWEATHER PHP CLASS -->
<?php require_once ("jbweather/jbww.php"); ?>






<!-- STEP 3: ADJUST PARAMETERS -->
<?php 
// URL path to jbweather folder
$parameters['url'] = 'jbweather';

// Api Key
// Obtain API key from https://developer.forecast.io/register
$parameters['apikey'] = '2973c7f565a8fc0a02e8dd3f4814c07f';

// Geonames username
// Obtain username by registering at http://www.geonames.org/login
$parameters['geonames'] = 'customweb';

// Default Location
// Display this location if autoDetect is OFF, or autoDetect fails to locate user location
$parameters['location'] = 'Nottingham Road, KwaZulu-Natal';

// Autodetect user location (HTML5 GEOLOCATION)
// 0 - OFF
// 1 - ON through HTML5
// 2 - ON through IP address
$parameters['autodetect'] = '0';

// Temperature units
// C - Celsius
// F - Fahrenheit
$parameters['degreesunits'] = 'C';

// Wind Units
// M - Miles
// K - Kilometers 
$parameters['windunits'] = 'K';

// cURL
// 0 - OFF
// 1 - ON
$parameters['curl'] = '1';

?>

<!-- STEP 4: DISPLAY THE WIDGET -->
<?php
$JBW = new JBWeather();
$JBW->setParams($parameters);
$JBW->display();
?>
