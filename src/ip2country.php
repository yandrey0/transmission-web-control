<?php

header('Content-type: application/json; charset=utf-8');

header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store");

setlocale(LC_ALL, 'ru_RU.utf8');



use MaxMind\Db\Reader;
$reader = new Reader('/var/www/geoip2/GeoLite2-City.mmdb');
$reader2 = new Reader('/var/www/geoip2/GeoLite2-ASN.mmdb');

$offset_local = date('Z');

$ret = ['country'=>'','name'=>'','city'=>'','asn'=>'', 'timezone'=>''];

if(isset($_SERVER['QUERY_STRING']) && filter_var($_SERVER['QUERY_STRING'], FILTER_VALIDATE_IP)){

$record = $reader->get($_SERVER['QUERY_STRING']);

$ret['country'] = isset($record['country']['iso_code']) ? strtolower($record['country']['iso_code']) : 'un';

$name = isset($record['country']['names']['ru']) ? $record['country']['names']['ru'] : '';
if(empty($name) && isset($record['country']['names']['en'])) $name = $record['country']['names']['en'];
if(isset($record['city']['names']['ru'])) $city[] = $record['city']['names']['ru'];
if(empty($city) && isset($record['city']['names']['en'])) $city[] = $record['city']['names']['en'];
						
if(isset($record['subdivisions'])){
 foreach($record['subdivisions'] as $sub) if(isset($sub['names'])) $city[] = isset($sub['names']['ru']) ? $sub['names']['ru'] : $sub['names']['en'];
}

$ret['name'] = $name ? $name : "неизвестно";

if(!empty($city)){
     $city = array_unique($city);
      $city = implode(', ',$city);
   }else{
	$city = null;
}

$ret['city'] = $city;


$asn = $reader2->get($_SERVER['QUERY_STRING']);

$ret['asn'] = isset($asn['autonomous_system_organization'])?$asn['autonomous_system_organization']:"-";

                    if(isset($record['location']['time_zone'])){
//						if($dt = new DateTime("now", new DateTimeZone($record['location']['time_zone']))) $timezone = $dt->format('P').' '.$record['location']['time_zone']; else $timezone = $record['location']['time_zone'];
						if($dt_peer = new DateTime(null, new DateTimeZone($record['location']['time_zone']))){
							$offset_peer = $dt_peer->format('Z');
							$diff = ($offset_peer - $offset_local) / 3600;
							$diff = ($diff >= 0) ? '+'.$diff : $diff;
							$timezone = $diff.' '.$record['location']['time_zone'];
						} else $timezone = $record['location']['time_zone'];
					}else{
						$timezone = '-';
					}

$ret['timezone'] = $timezone;

}


echo json_encode($ret, JSON_UNESCAPED_UNICODE);