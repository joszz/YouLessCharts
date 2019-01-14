#!/usr/bin/php
<?php

	include "inc/settings.inc.php";
	include "classes/curl.class.php";
	include "classes/request.class.php";
	include "classes/database.class.php";
	include "classes/generic.class.php";
	
	$request = new Request();
	$db = new Database();
	$gen = new Generic();
	$settings = $db->getSettings();
	
	
	// Update data table
	$data = $request->getLastHour();		

	$row = explode(",", $data['val']);
	$total = count($row);
	$time = strtotime($data['tm']);
	for($t=1;$t<$total;$t++)
	{
          $mtime = $time + ( $t * $data['dt'] );
		  $low=$gen->IsLowKwh(date('Y-m-d H:i:00',$mtime));
		  if ( $low == 0 ) {
			$tariff=(float)$settings['cpkwh'];
		  } else {
			$tariff=(float)$settings['cpkwh_low'];
		  }


		  $db->addMinuteData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], str_replace("\"", "",$row[$t]), $low, $tariff );
		  
	}
	
	$liveData = json_decode($request->getLiveData(), true);

	$time = time()-(86400*2);
	$nu = time();
	for ($i = $time; $i < $nu ;$i = $i + 60 ) {
		$db->addMissingMinuteData( date('Y-m-d H:i:00',$i));
	}
	
	
?>
