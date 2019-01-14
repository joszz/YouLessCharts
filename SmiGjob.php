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

	
	
	function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	
	$time_start = microtime_float();

	
	//Update eens per 24h de laatste 6 dagen
	//Op exact 00 uur en < 20 minuten
	$HourNow = date('H');
	$MinuteNow = date('i');
	if ($HourNow == 0 && $MinuteNow <= 20 ) {
		$settings['LastUpdate_UnixTime'] -= 3600*24*6;
	}
	
	
	//Update database with latest update timestamp
	$db->updateSettings('LastUpdate_UnixTime', time());

	$liveData = json_decode($request->getLiveData(), true);

	

	$time_end = microtime_float();
	$totaltime = round($time_end - $time_start,4);
		
	print "<br>LiveData: $totaltime sec<br><br>";
	$time_start = microtime_float();	




	
	
	
	// Update data table with 1 min data
	$data = $request->getLastHour();		

	$row = explode(",", $data['val']);
	$total = count($row);
	$time = strtotime($data['tm']);
	for($t=0;$t<$total;$t++)
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

	
	
	
	$time_end = microtime_float();
	$totaltime = round($time_end - $time_start,4);
		
	print "<br>60min: $totaltime sec<br><br>";
	$time_start = microtime_float();

	
	
	
	// Update data table with 10 min data
	
	// Only if last update was > 1 hour ago
	if($settings['LastUpdate_UnixTime']<time()-3610)
	{	

		$data = $request->getLast24Hours();		
		
		$row = explode(",", $data['val']);
		
		$total = count($row);
		$time = strtotime($data['tm']);
		
		for($t=0;$t<$total;$t++)
		{
			
			for($TenMinLoop=0;$TenMinLoop<10;$TenMinLoop++)
			{	
				$mtime = $time + ( $t * $data['dt'] ) + $TenMinLoop*60;
				$low=$gen->IsLowKwh(date('Y-m-d H:i:00',$mtime));
				
				if ( $low == 0 ) {
				  $tariff=(float)$settings['cpkwh'];
				} else {
				  $tariff=(float)$settings['cpkwh_low'];
				}
				
				switch ($TenMinLoop):
					case 4:
						$db->add10MinuteData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round((str_replace("\"", "",$row[$t]))*1.02+2,0), $low, $tariff );
						break;
					case 5:
						$db->add10MinuteData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",$row[$t])/1.02-2,0), $low, $tariff );
						break;
					default:
						$db->add10MinuteData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",$row[$t]),0), $low, $tariff );
				endswitch;
				
			}
			
		}
	
	}

	
	
	
	$time_end = microtime_float();
	$totaltime = round($time_end - $time_start,4);
		
	print "<br>1day: $totaltime sec<br><br>";
	$time_start = microtime_float();

	
	
	
	// Update data table with 1 hour data
	
	// Only if last update was > 1 day ago
	if($settings['LastUpdate_UnixTime']<time()-24*3600)
	{	

		$data = $request->getLast7Days();		
		
		$row = explode(",", $data['val']);
		
		$total = count($row);
		$time = strtotime($data['tm']);
		
		for($t=0;$t<$total;$t++)
		{

			for($HourLoop=0;$HourLoop<60;$HourLoop++)
			{	
				$mtime = $time + ( $t * $data['dt'] ) + $HourLoop*60;
				$low=$gen->IsLowKwh(date('Y-m-d H:i:00',$mtime));
				
				if ( $low == 0 ) {
				  $tariff=(float)$settings['cpkwh'];
				} else {
				  $tariff=(float)$settings['cpkwh_low'];
				}
				
				switch ($HourLoop):
					case 18:
						$db->add1HourData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round((str_replace("\"", "",$row[$t]))*1.02+2,0), $low, $tariff );
						break;
					case 24:
						$db->add1HourData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round((str_replace("\"", "",$row[$t]))/1.02+2,0), $low, $tariff );
						break;
					case 30:
						$db->add1HourData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",$row[$t])*1.02-2,0), $low, $tariff );
						break;
					case 36:
						$db->add1HourData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",$row[$t])/1.02-2,0), $low, $tariff );
						break;
					default:
						$db->add1HourData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",$row[$t]),0), $low, $tariff );
				endswitch;
	
			}
			
		}
		
	}

	
	
	
	$time_end = microtime_float();
	$totaltime = round($time_end - $time_start,4);
		
	print "<br>1week: $totaltime sec<br><br>";
	$time_start = microtime_float();	

	
	
	
	// Update data table with 1 day data					//WORKING (BUT VERY SLOW DUE TO THE ALMOST 50k INSERTED(31*24*60)POINTS) | BETA FEATURE!!
	
	// Only if last update was > 1 week ago
	if($settings['LastUpdate_UnixTime']<time()-7*24*3600)
	{	

		print "<br>update 1 day data<br>";

		
		$ThisMonthdefault = date("m");
		//$ThisMonthdefault = 7;								//replace $ThisMonth (=now) by value
		$ThisMonthURL = $_GET["Month"];

		if ($ThisMonthURL > 0) {								//USE Custom value if given in URL, ELSE use Default value
			$ThisMonth = $ThisMonthURL;
			}
		else {
			$ThisMonth = $ThisMonthdefault;
		}
		
		//$db->updateSettings('LastUpdate_UnixTime', 0);
		
		
		// Update data table with 1 day data
		$data = $request->getThisMonth($ThisMonth);	
		
		$row = explode(", ", $data['val']);
		
		$totaldays = count($row);
		
		if ($ThisMonth == date("m")) {						//Indien de te updaten maand de huidige is, -1 doen zodat vandaag niet wordt geupdate (t/m 24 uur vanavond)
			$totaldays = $totaldays - 1;
		}
		
		print "Totaldays: $totaldays";
		
		
		$time = strtotime($data['tm']);

		
		print "<br>";		
		print "$totaldays days/month";
		print "<br>";
		
		print date('Y-m-d H:i:00',$time);
		print "<br>";
		
		print $data['un'];
		print "<br>";

		print $data['dt'];
		print "<br>";
		print "<br>";		

		print_r ($row);
		print "<br>";
		print "<br>";		

		
		
		
		$ThisDay = date("d");
		//$totaldays = $ThisDay-1;
		//$totaldays = $ThisDay;		//replace $totaldays (=rowcount) by value
		

		
		for($t=0;$t<$totaldays;$t++)						//LOOP FROM DAY 1 TILL TOTAL DAYS/MONTH
		{

			
			for($DayLoop=0;$DayLoop<1440;$DayLoop++)
			{	
				$mtime = $time + ( $t * $data['dt'] ) + $DayLoop*60;
				$low=$gen->IsLowKwh(date('Y-m-d H:i:00',$mtime));
				
				if ( $low == 0 ) {
				  $tariff=(float)$settings['cpkwh'];
				} else {
				  $tariff=(float)$settings['cpkwh_low'];
				}
				
				switch ($DayLoop%60):
					case (0):
						$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000*1.02+2,0), $low, $tariff );
						break;
					case (2):
						$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000/1.02-2,0), $low, $tariff );
						break;
					case (4):
						$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000*1.02+2,0), $low, $tariff );
						break;
					case (6):
						$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000/1.02-2,0), $low, $tariff );
						break;
					case (8):
						$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000*1.02+2,0), $low, $tariff );
						break;
					case (10):
						$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000/1.02-2,0), $low, $tariff );
						break;
					case (12):
						$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000*1.02+2,0), $low, $tariff );
						break;
					case (14):
						$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000/1.02-2,0), $low, $tariff );
						break;
					case (16):
						$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000*1.02+2,0), $low, $tariff );
						break;
						default:
						$db->add1DayData( date('Y-m-d H:i:00',$mtime), $data['un'], $data['dt'], round(str_replace("\"", "",str_replace(',','.',$row[$t]))/24*1000,0), $low, $tariff );
				endswitch;
				
			}
			
			
		}
	
	}

	
	
	
	$time_end = microtime_float();
	$totaltime = round($time_end - $time_start,4);
		
	print "<br>1month: $totaltime sec<br><br>";
	$time_start = microtime_float();		

	
	
	
	/*
	
	{
		
	print "<br>update -1 data<br>";		

		$time = time()-(3600*24*2);
		$nu = time();
		for ($i = $time; $i < $nu ;$i = $i + 60 ) {
			$db->addMissingMinuteData( date('Y-m-d H:i:00',$i));
		}
	}

	$time_end = microtime_float();
	$totaltime = round($time_end - $time_start,4);
	
	print "-1: $totaltime sec<br><br>";
	$time_start = microtime_float();
	
	*/

	
?>
