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

//$TimeBackDay = "-1 ".$settings['TimeBackDay'];		//CHANGE THIS IN SETTINGS TO DAY/WEEK/MONTH/YEAR
//$TimeBackWeek = "-1 ".$settings['TimeBackWeek'];	//CHANGE THIS IN SETTINGS TO DAY/WEEK/MONTH/YEAR
//$TimeBackMonth = "-1 ".$settings['TimeBackMonth'];	//CHANGE THIS IN SETTINGS TO DAY/WEEK/MONTH/YEAR
//$TimeBackYear = "-1 ".$settings['TimeBackYear'];	//CHANGE THIS IN SETTINGS TO DAY/WEEK/MONTH/YEAR

$TimeBackDay = "-1 day";							//CHANGE THIS IN CASE SETTINGS IS DISABLED | DAY/WEEK/MONTH/YEAR
$TimeBackWeek = "-1 week ";						//CHANGE THIS IN CASE SETTINGS IS DISABLED | DAY/WEEK/MONTH/YEAR
$TimeBackMonth = "-1 month ";						//CHANGE THIS IN CASE SETTINGS IS DISABLED | DAY/WEEK/MONTH/YEAR
$TimeBackYear = "-1 year ";						//CHANGE THIS IN CASE SETTINGS IS DISABLED | DAY/WEEK/MONTH/YEAR


if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != false)
{

	if(isset($_GET['a']) && $_GET['a'] == 'live')
	{
		echo $request->getLiveData();
	}
	elseif(isset($_GET['a']) && ( $_GET['a'] == 'day' || $_GET['a'] == 'week' || $_GET['a'] == 'month' || $_GET['a'] == 'year'  ) && isset($_GET['date']))
	{	
		$sqlDate = $_GET['date'];
		//$sqlDate2 = $_GET['date'];

		$range =  $_GET['a'];

		if ( $range == 'day' ) {
			//Date1
			$rows = $db->getSpecificDay($sqlDate);
			//Date2
			$sqlDate2 = date ( 'Y-m-d' , strtotime ( $TimeBackDay , strtotime ( $sqlDate ) ) );
			$rows2 = $db->getSpecificDay($sqlDate2);
		} 
		elseif ( $range == 'week') {
			//Date1
			$week = date('W',strtotime($sqlDate));
			$year = date('Y',strtotime($sqlDate));
	
			$begin = date("Y-m-d", strtotime($year."W".$week));
			$end = date("Y-m-d", strtotime($year."W".$week)+(6*86400));		
				
			$rows = $db->getSpecificRange($begin, $end);
			
			//Date2
			$sqlDate2 = date ( 'Y-m-d' , strtotime ( $TimeBackWeek , strtotime ( $sqlDate ) ) );
			$week = date('W',strtotime($sqlDate2));
			$year = date('Y',strtotime($sqlDate2));
	
			$begin = date("Y-m-d", strtotime($year."W".$week));
			$end = date("Y-m-d", strtotime($year."W".$week)+(6*86400));		
				
			$rows2 = $db->getSpecificRange($begin, $end);
		}
		elseif ( $range == 'month') {
			//Date1
			$begin = date("Y-m-d",strtotime('first day of this month',strtotime($sqlDate))); 
			$end = date("Y-m-d",strtotime('last day of this month',strtotime($sqlDate))); 
		
			$rows = $db->getSpecificRange($begin, $end);
			
			//Date2
			$sqlDate2 = date ( 'Y-m-d' , strtotime ( $TimeBackMonth , strtotime ( $sqlDate ) ) );
			$begin = date("Y-m-d",strtotime('first day of this month',strtotime($sqlDate2))); 
			$end = date("Y-m-d",strtotime('last day of this month',strtotime($sqlDate2))); 
		
			$rows2 = $db->getSpecificRange($begin, $end);

		}
		elseif ( $range == 'year') {
			//Date1
			$begin = date('Y-m-d', strtotime("-1 year",time()));
			//$end = date('Y-m-d',strtotime("+1 day",strtotime($sqlDate)));
			$end = date('Y-m-d',strtotime("+1 day",time()));

			$rows = $db->getSpecificRange($begin, $end);
			
			//Date2
			$sqlDate2 = date ( 'Y-m-d' , strtotime ( $TimeBackYear , strtotime ( $sqlDate ) ) );
			$begin = date('Y-m-d', strtotime("-2 year",time()));
			//$end = date('Y-m-d',strtotime("+1 day",strtotime($sqlDate)));
			$end = date('Y-m-d',strtotime("-1 year +1 day",time()));

			$rows2 = $db->getSpecificRange($begin, $end);
			}

		if(count($rows) == 0)
		{
		
			echo '{"ok": 0, "msg":"Geen data beschikbaar op deze datum", "start": "'. $sqlDate .'", "val": " 0, 0", "kwh": 0, "price": 0}';
		
		}
		else
		{
			// part1
			$i=0;
			$otime=99999999999999999999;
			
			$leeg[] = 0;
			$prevtime = time();
			$dataStr = '';
			$it=0;

			foreach($rows as $k)
			{

				$total = substr_count($k->value, ",") + 1;

				
				if ($k->time < $otime) {
					$otime = $k->time;
				}

				$diff = floor(($k->time - $prevtime) / 60) - $total;
				if($i > 0 && $diff > 0) {
					for($j = 0; $j < $diff; $j++) {
						$dataStr .= ',0';
					}
				}

				$dataStr .= ($i!=0 ? "," : "").$k->value;

				$prevtime = $k->time;
				$i++;
			}	


			
			// part2			$i=0;
			$i=0;
			$otime2=99999999999999999999;
			
			$leeg[] = 0;
			$prevtime = time();
			$dataStr2 = '';
			$it=0;

			foreach($rows2 as $k)
			{

				$total2 = substr_count($k->value, ",") + 1;

				
				if ($k->time < $otime2) {
					$otime2 = $k->time;
				}

				$diff = floor(($k->time - $prevtime) / 60) - $total2;
				if($i > 0 && $diff > 0) {
					for($j = 0; $j < $diff; $j++) {
						$dataStr2 .= ',0';
					}
				}

				$dataStr2 .= ($i!=0 ? "," : "").$k->value;

				$prevtime = $k->time;
				$i++;
			}	

			
			if (empty($dataStr2)){
				$dataStr2="0";
			}
			
			//$db->updateSettings('Variable1', $dataStr);	
			//$db->updateSettings('Variable2', $dataStr2);	

			//$db->updateSettings('Variable4', '"val": "'. str_replace("\"", "", $dataStr) .'"');
			//$db->updateSettings('Variable5', '"val2": "'. str_replace("\"", "", $dataStr2) .'"');
			
			
			// Output data
			$startTime = date('Y-m-d-H-i',$otime); 
			echo '{"ok": 1, "start": "'. $startTime .'", "val": "'. str_replace("\"", "", $dataStr) .'", "val2": "'. str_replace("\"", "", $dataStr2) .'"}';


			
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
