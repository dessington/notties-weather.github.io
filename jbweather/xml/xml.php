<?php
header('Content-Type: text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';

function file_get_contents_curl($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

function _round_coord($figure) {
    return sprintf("%.5f", $figure);
}
  

function location_info($location, $username, $curl) {

    $is_postal_code = is_postal_code($location);
    $is_coords = is_coords($location);
    $is_ip_address = is_ip_address($location);

    if ($is_ip_address) {

        $url = "http://ipinfo.io/{$location}/json";

    } elseif ($is_coords) {
        $location = trim($location);
        $ll = explode(',', $location);

        $ll[0] = _round_coord($ll[0]);
        $ll[1] = _round_coord($ll[1]);

        $url = "http://api.geonames.org/findNearbyPlaceNameJSON?lat=$ll[0]&lng=$ll[1]&username={$username}";
    } elseif ($is_postal_code) {
        $url = "http://api.geonames.org/postalCodeSearchJSON?postalcode=" . urlencode($location) . "&username={$username}";
    } else {
        $url = "http://api.geonames.org/searchJSON?q=" . urlencode($location) . "&username={$username}";
    }

    if ($curl == 1) {
        $content = file_get_contents_curl($url);
    } else {
        $content = file_get_contents($url);
    }

    $content = json_decode($content);

    if (!$content || isset($content->status)) {
        return $content;
    }

    $data = new stdClass();
    
    if ($is_ip_address) {
            
            if ($content->loc) {
                
                if ($content->city != "" && $content->country != '') {
                    
                    $data->coords = $content->loc;
                    $data->name   = $content->city . ', ' . $content->country;
                    
                } else {
                    
                    // ipinfo did not provide city or country name
                    // call location_name using coordinates
                    return location_info($content->loc, $username, $curl);
                    
                }
                
            } else {
                return false;
            }
            
        } elseif ($is_coords) {

        if ($content->geonames && !empty($content->geonames)) {
            $data->coords = _round_coord($content->geonames[0]->lat) . "," . _round_coord($content->geonames[0]->lng);
            $data->name = $content->geonames[0]->toponymName . ", " . $content->geonames[0]->countryName;
        } else {
            return false;
        }
    }
    
    elseif ($is_postal_code) {

        if ($content->postalCodes && !empty($content->postalCodes)) {
            $data->coords = _round_coord($content->postalCodes[0]->lat) . "," . _round_coord($content->postalCodes[0]->lng);
            $data->name = $content->postalCodes[0]->placeName . ", " . $content->postalCodes[0]->countryCode;
        } else {
            return false;
        }
    }  else {

        if ($content->geonames && !empty($content->geonames)) {
            $data->coords = _round_coord($content->geonames[0]->lat) . "," . _round_coord($content->geonames[0]->lng);
            $data->name = $content->geonames[0]->toponymName . ", " . $content->geonames[0]->countryName;
        } else {
            return false;
        }
    }

    return $data;
}

function is_postal_code($location) {

    $ZIPREG = array(
        "US" => "^\d{5}([\-]?\d{4})?$",
        "UK" => "^(GIR|[A-Z]\d[A-Z\d]??|[A-Z]{2}\d[A-Z\d]??)[ ]??(\d[A-Z]{2})$",
        "DE" => "\b((?:0[1-46-9]\d{3})|(?:[1-357-9]\d{4})|(?:[4][0-24-9]\d{3})|(?:[6][013-9]\d{3}))\b",
        "CA" => "^([ABCEGHJKLMNPRSTVXY]\d[ABCEGHJKLMNPRSTVWXYZ])\ {0,1}(\d[ABCEGHJKLMNPRSTVWXYZ]\d)$",
        "FR" => "^(F-)?((2[A|B])|[0-9]{2})[0-9]{3}$",
        "IT" => "^(V-|I-)?[0-9]{5}$",
        "AU" => "^(0[289][0-9]{2})|([1345689][0-9]{3})|(2[0-8][0-9]{2})|(290[0-9])|(291[0-4])|(7[0-4][0-9]{2})|(7[8-9][0-9]{2})$",
        "NL" => "^[1-9][0-9]{3}\s?([a-zA-Z]{2})?$",
        "ES" => "^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$",
        "DK" => "^([D|d][K|k]( |-))?[1-9]{1}[0-9]{3}$",
        "SE" => "^(s-|S-){0,1}[0-9]{3}\s?[0-9]{2}$",
        "BE" => "^[1-9]{1}[0-9]{3}$",
        "IN" => "^\d{6}$"
    );

    foreach ($ZIPREG as $reg) {

        if (preg_match("/$reg/", $location)) {
            return true;
        }
    }

    return false;
}

function is_coords($location) {

    if (preg_match("/^(\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?)$/", $location)) {
        return true;
    }

    return false;
}

function is_ip_address($location) {
   return filter_var($location, FILTER_VALIDATE_IP);
}

function translate($string) {

    $lang = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "language.txt");
    if ($lang) {
        $lang = explode("\n", $lang);
        
        $string = preg_replace("/\bMonday\b/",    $lang[0], $string);
        $string = preg_replace("/\bTuesday\b/",   $lang[1], $string);
        $string = preg_replace("/\bWednesday\b/", $lang[2], $string);
        $string = preg_replace("/\bThursday\b/",  $lang[3], $string);
        $string = preg_replace("/\bFriday\b/",    $lang[4], $string);
        $string = preg_replace("/\bSaturday\b/",  $lang[5], $string);
        $string = preg_replace("/\bSunday\b/",    $lang[6], $string);
        
        $string = preg_replace("/\bN\b/",      $lang[7], $string);
        $string = preg_replace("/\bNNE\b/",    $lang[8], $string);
        $string = preg_replace("/\bNE\b/",     $lang[11], $string);
        $string = preg_replace("/\bE\b/",      $lang[12], $string);
        $string = preg_replace("/\bESE\b/",    $lang[13], $string);
        $string = preg_replace("/\bSE\b/",     $lang[14], $string);
        $string = preg_replace("/\bS\b/",      $lang[15], $string);
        $string = preg_replace("/\bSSW\b/",    $lang[16], $string);
        $string = preg_replace("/\bSW\b/",     $lang[17], $string);
        $string = preg_replace("/\bWSW\b/",    $lang[18], $string);
        $string = preg_replace("/\bW\b/",      $lang[19], $string);
        $string = preg_replace("/\bWNW\b/",    $lang[20], $string);
        $string = preg_replace("/\bNW\b/",     $lang[21], $string);
        $string = preg_replace("/\bNNW\b/",    $lang[22], $string);
        
        $string = preg_replace("/\bJan\b/",    $lang[23], $string);
        $string = preg_replace("/\bFeb\b/",    $lang[24], $string);
        $string = preg_replace("/\bMar\b/",    $lang[25], $string);
        $string = preg_replace("/\bApr\b/",    $lang[26], $string);
        $string = preg_replace("/\bMay\b/",    $lang[27], $string);
        $string = preg_replace("/\bJun\b/",    $lang[28], $string);
        $string = preg_replace("/\bJul\b/",    $lang[29], $string);
        $string = preg_replace("/\bAug\b/",    $lang[30], $string);
        $string = preg_replace("/\bSep\b/",    $lang[31], $string);
        $string = preg_replace("/\bOct\b/",    $lang[32], $string);
        $string = preg_replace("/\bNov\b/",    $lang[33], $string);
        $string = preg_replace("/\bDec\b/",    $lang[34], $string);
        
    } 
    
    return $string;
}

function wind_dir($bearing) {

    while ($bearing < 0)
        $bearing += 360;
    while ($bearing >= 360)
        $bearing -= 360;
    $val = round(($bearing - 11.25) / 22.5);
    $arr = ["N", "NNE", "NE", "ENE", "E", "ESE", "SE",
        "SSE", "S", "SSW", "SW", "WSW", "W", "WNW", "NW", "NNW"];
    return $arr[abs($val)];
}

$apikey     = $_POST["apiKey"];
$geonames   = $_POST["geonames"];
$location   = $_POST["location"];
$curl       = $_POST["curl"];

if (!is_dir("cache")) {
    if (!mkdir("cache", 0700)) {
        die("Error creating cache folder!");
    }
}

$cacheName = str_replace(",",  "", $location);
$cacheName = str_replace(" ",  "", $cacheName);
$cacheName = str_replace(".",  "", $cacheName);
$cacheName = str_replace("/",  "", $cacheName);
$cacheName = str_replace("\\", "", $cacheName);
$cacheName = strtolower($cacheName);
$cacheName = "cache" . DIRECTORY_SEPARATOR . $cacheName;
$cacheTime = 3600;


if (!file_exists($cacheName) || filemtime($cacheName) <= time() - $cacheTime) {
    
    $location = location_info($location, $geonames, $curl);
    
    $url = "https://api.forecast.io/forecast/$apikey/$location->coords";
    
    if ($curl == 1) {
        $contents = file_get_contents_curl($url);
    } else {
        $contents = file_get_contents($url);
    }
    
    $contents = json_decode($contents);
    
    $contents->locationName = $location->name;
    
    file_put_contents($cacheName, json_encode($contents));
}

$weatherdata = json_decode(file_get_contents($cacheName));

ob_start();
?>

<?php 
     date_default_timezone_set($weatherdata->timezone);
?>

<response>
    <location><![CDATA[<?php echo $weatherdata->locationName ?>]]></location>

    <current>
        <date><![CDATA[<?php echo translate(date('l d M Y', $weatherdata->currently->time)); ?>]]></date>
        <time><![CDATA[<?php echo $weatherdata->currently->time; ?>]]></time>
        <timezone><![CDATA[<?php echo $weatherdata->timezone; ?>]]></timezone>
        
        <temperature>
            
            <?php
            $tempF = round($weatherdata->currently->temperature);
            $tempC = round(($tempF - 32) * 5 / 9);
            ?>

            <f><![CDATA[<?php echo $tempF ?>]]></f>
            <c><![CDATA[<?php echo $tempC ?>]]></c>
        </temperature>

        <code></code>
        <icon><?php echo $weatherdata->currently->icon; ?></icon>

        <description><?php echo $weatherdata->currently->summary; ?></description>

        <wind>
            <windSpeed>
                
                <?php
                    $windM = $weatherdata->currently->windSpeed;
                    $windK = round($windM * 1.609344);
                ?>
                
                <m><![CDATA[<?php echo $windM; ?>]]></m>
                <k><![CDATA[<?php echo $windK; ?>]]></k>
            </windSpeed>

            <direction><?php echo translate(wind_dir($weatherdata->currently->windBearing)); ?></direction>
        </wind>
    </current>

<?php foreach ($weatherdata->daily->data as $day) : ?>
        <day>
            
            <date><![CDATA[<?php echo date('l d',$day->time); ?>]]></date>
            <temperature>
                
                <?php
                    $maxTempF = round($day->temperatureMax);
                    $maxTempC = round(($maxTempF - 32) * 5 / 9);
                    $minTempF = round($day->temperatureMin);
                    $minTempC = round(($minTempF - 32) * 5 / 9);
                ?>

                <max>
                <f><![CDATA[<?php echo $maxTempF; ?>]]></f>
                <c><![CDATA[<?php echo $maxTempC; ?>]]></c>
                </max>

                <min>
                <f><![CDATA[<?php echo $minTempF; ?>]]></f>
                <c><![CDATA[<?php echo $minTempC; ?>]]></c>
                </min>
            </temperature>

            <code></code>
            <icon><![CDATA[<?php echo $day->icon; ?>]]></icon>

            <description><![CDATA[<?php echo $day->summary; ?>]]></description>

            <wind>
                <windSpeed>

                    <?php
                    $windM = $day->windSpeed;
                    $windK = round($windM * 1.609344);
                    ?>

                    <m><![CDATA[<?php echo $windM; ?>]]></m>
                    <k><![CDATA[<?php echo $windK; ?>]]></k>
                    
                </windSpeed>

                <direction><![CDATA[<?php echo $day->windBearing; ?>]]></direction>
            </wind>
        </day>
<?php endforeach; ?>

</response>

<?php ob_get_flush();