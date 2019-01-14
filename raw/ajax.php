<?php
ini_set('memory_limit', '-1');
include "../inc/settings.inc.php";
include "classes/curl.class.php";
include "classes/request.class.php";	
include "classes/database.class.php";
include "classes/generic.class.php";

session_start();

$request = new Request();
$db = new Database();
$gen = new Generic();
$settings = $db->getSettings();

if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != false)
{

	if(isset($_GET['a']) && $_GET['a'] == 'live')
	{
		echo $request->getLiveData();
	}
	elseif(isset($_GET['a']) && ( $_GET['a'] == 'day' || $_GET['a'] == 'week' || $_GET['a'] == 'month' || $_GET['a'] == 'year'  ) && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];
		$range =  $_GET['a'];
		
		
		if ( $range == 'day' ) {
			$rows = $db->getSpecificDay($sqlDate);
		} 
		elseif ( $range == 'week') {
			$week = date('W',strtotime($sqlDate));
			$year = date('Y',strtotime($sqlDate));
	
			$begin = date("Y-m-d", strtotime($year."W".$week));
			$end = date("Y-m-d", strtotime($year."W".$week)+(6*86400));		
				
			$rows = $db->getSpecificRange($begin, $end);
		}
		elseif ( $range == 'month') {

			$begin = date("Y-m-d",strtotime('first day of this month',strtotime($sqlDate))); 
			$end = date("Y-m-d",strtotime('last day of this month',strtotime($sqlDate))); 
		
			$rows = $db->getSpecificRange($begin, $end);
				
		}
		elseif ( $range == 'year') {

			$begin = date('Y-m-d', strtotime("-1 year",time()));
			//$end = date('Y-m-d',strtotime("+1 day",strtotime($sqlDate)));
			$end = date('Y-m-d',strtotime("+1 day",time()));

			$rows = $db->getSpecificRange($begin, $end);
		}


		if(count($rows) == 0)
		{
		
			echo '{"ok": 0, "msg":"Geen data beschikbaar op deze datum", "start": "'. $sqlDate .'", "val": " 0, 0", "kwh": 0, "price": 0}';
		
		}
		else
		{
			$i=0;
			$otime=99999999999999999999;
			
			foreach($rows as $k)
			{
				$row = explode(",", $k->value);
				$total = count($row);
				
				$time = strtotime($k->time);
				if ( $time < $otime ) $otime = $time;
			        	
				$timeAr[$i][] = $time;
				$dataAr[$i] = $row;
				
				for($t=1;$t<$total;$t++)
				{
					$timeAr[$i][$t] = $timeAr[$i][$t-1] +  (int)$k->delta;
				}
				$i++;
			}
			
			$timeStr = '';
			foreach($timeAr as $k)
			{
				$timeStr .= implode(",", $k);
			}
			
			// Create JS data string
			$i=0;
			$dataStr = '';
			
			foreach($dataAr as $k)
			{
				$dataStr .= ($i!=0 ? "," : "").implode(",", $k);
				$i++;
			}	
			
			// Output data
			$startTime = date('Y-m-d-H-i',$otime); 
			echo '{"ok": 1, "start": "'. $startTime .'", "val": "'. str_replace("\"", "", $dataStr) .'"}';	
		
		}
			
	}
	elseif(isset($_GET['a']) && $_GET['a'] == 'calculate_day' && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];
		
		// Get data from specific day
		$costs = $gen->calculateDayKwhCosts($sqlDate);	
			
		// Output data
		echo '{"ok": 1, "kwh": "'. number_format($costs['kwh'], 2, ',', '') .'", "kwhLow": "'. number_format($costs['kwhLow'], 2, ',', '') .'", "price": "'. number_format($costs['price'], 2, ',', '') .'", "priceLow": "'. number_format($costs['priceLow'], 2, ',', '') .'", "priceTotal": "'. number_format($costs['priceTotal'], 2, ',', '') .'", "kwhTotal": "'. number_format($costs['kwhTotal'], 2, ',', '') .'"}';	
		
			
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'calculate_week' && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];
		
		$week = date('W',strtotime($sqlDate));
		$year = date('Y',strtotime($sqlDate));
	
		$start = date("Y-m-d", strtotime($year."W".$week));
		$end = date("Y-m-d", strtotime($year."W".$week)+(6*86400));
		
		// Calculate totals/costs
		$costs = $gen->calculateRangeKwhCosts($start, $end);
		
		// Output data
		echo '{"ok": 1, "kwh": "'. number_format($costs['kwh'], 2, ',', '') .'", "kwhLow": "'. number_format($costs['kwhLow'], 2, ',', '') .'", "price": "'. number_format($costs['price'], 2, ',', '') .'", "priceLow": "'. number_format($costs['priceLow'], 2, ',', '') .'", "priceTotal": "'. number_format($costs['priceTotal'], 2, ',', '') .'", "kwhTotal": "'. number_format($costs['kwhTotal'], 2, ',', '') .'"}';	
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'calculate_month' && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];

		$begin = date("Y-m-d",strtotime('first day of this month',strtotime($sqlDate))); 
		$end = date("Y-m-d",strtotime('last day of this month',strtotime($sqlDate))); 

		
		// Calculate totals/costs
		$costs = $gen->calculateRangeKwhCosts($begin, $end);
		
		// Output data
		echo '{"ok": 1, "kwh": "'. number_format($costs['kwh'], 2, ',', '') .'", "kwhLow": "'. number_format($costs['kwhLow'], 2, ',', '') .'", "price": "'. number_format($costs['price'], 2, ',', '') .'", "priceLow": "'. number_format($costs['priceLow'], 2, ',', '') .'", "priceTotal": "'. number_format($costs['priceTotal'], 2, ',', '') .'", "kwhTotal": "'. number_format($costs['kwhTotal'], 2, ',', '') .'"}';	
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'calculate_year' && isset($_GET['date']))
	{	
		
		$sqlDate = $_GET['date'];
		
		$start = date('Y-m-d', strtotime("-1 year",strtotime($sqlDate)));
		$end = date('Y-m-d',strtotime("+1 day",strtotime($sqlDate)));
						
		// Calculate totals/costs
		$costs = $gen->calculateRangeKwhCosts($start, $end);
		
		// Output data
		echo '{"ok": 1, "kwh": "'. number_format($costs['kwh'], 2, ',', '') .'", "kwhLow": "'. number_format($costs['kwhLow'], 2, ',', '') .'", "price": "'. number_format($costs['price'], 2, ',', '') .'", "priceLow": "'. number_format($costs['priceLow'], 2, ',', '') .'", "priceTotal": "'. number_format($costs['priceTotal'], 2, ',', '') .'", "kwhTotal": "'. number_format($costs['kwhTotal'], 2, ',', '') .'"}';	
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'get_meter')
	{	
		// Calculate totals/costs
		$meter  = $db->getMeterstand('0');
		$meterl = $db->getMeterstand('1');
		$islow  = $gen->IsLowKwh(date('Y-m-d H:i:00',time()));
				
		// Output data
		echo '{"ok": 1, "meter": "'. number_format($meter, 3, ',', '') .'", "meterl": "'. number_format($meterl, 3, ',', '') .'", "islow": "'. number_format($islow, 0, ',', '') .'"}';	
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'get_islowkwh' && isset($_GET['date']))
	{	
		// Calculate totals/costs
		$islow = $gen->IswLowKwh($date);
				
		// Output data
		echo '{"ok": 1, "islow": "'. number_format($islow, 1, ',', '') .'"}';	
	}	
	elseif(isset($_GET['a']) && $_GET['a'] == 'saveSettings')
	{
	
		$excludedFields = array(
			'password',
			'confirmpassword',
			'cpkwhlow_start_hour',
			'cpkwhlow_start_min',
			'cpkwhlow_end_hour',
			'cpkwhlow_end_min',
			'metercal'
		
		);
		
		foreach($_POST as $k => $v)
		{
			$$k = $v;
			if(!in_array($k, $excludedFields))
			{
				$db->updateSettings($k, $v);
			}
		}
		
		$cpkwhlow_start = $cpkwhlow_start_hour.":".$cpkwhlow_start_min;
		$cpkwhlow_end = $cpkwhlow_end_hour.":".$cpkwhlow_end_min;
		
		$db->updateSettings('cpkwhlow_start', $cpkwhlow_start);
		$db->updateSettings('cpkwhlow_end', $cpkwhlow_end);
		
		// file_put_contents('php://stderr', print_r(' PRE METERCAL ', TRUE));
		
		foreach($metercal as $v)
		{
			// file_put_contents('php://stderr', print_r($v['time']. $v['count']. $v['islow'], TRUE));
			$db->updateMeterc($v['time'], $v['count'], $v['islow']);
		}
		
		// file_put_contents('php://stderr', print_r(' POST METERCAL ', TRUE));

	
		if($password != "" && $confirmpassword != "" && $password == $confirmpassword)
		{
			$db->updateLogin(sha1($password));
		}
		
		echo '{"ok": 1, "msg":"Instellingen succesvol opgeslagen"}';	
		
	}
	elseif(isset($_GET['a']) && $_GET['a'] == 'calculate_range' && isset($_GET['stime']) && isset($_GET['etime']))
	{	
		$stime = $_GET['stime'];
		$etime = $_GET['etime'];
		
		$costs = $gen->calculateTimeRangeKwhCosts($stime,$etime);
		
		// Output data
		echo '{"ok": 1, "kwh": "'. number_format($costs['kwh'], 2, ',', '') .'", "kwhLow": "'. number_format($costs['kwhLow'], 2, ',', '') .'", "price": "'. number_format($costs['price'], 2, ',', '') .'", "priceLow": "'. number_format($costs['priceLow'], 2, ',', '') .'", "priceTotal": "'. number_format($costs['priceTotal'], 2, ',', '') .'", "kwhTotal": "'. number_format($costs['kwhTotal'], 2, ',', '') .'"}';	
	}	
	else
	{
		echo "Error!";
	}
}
else
{
	echo "Login required!";
}
?>
