<?php

class Request {

	private $source;
	private $format;
	private $password;
	private $data;
	private $opts;
	private $optsSetSes;
	private $cookie;
    
	public function __construct() {
	
		$this->source = 'http://'.YL_ADDRESS.'/';
		$this->format = '&f=j'; // JSON
		$this->password = YL_PASSWORD;
		$this->opts = array( 
			CURLOPT_RETURNTRANSFER => true, 
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 10
		);	
	}       
      

    /**
     * Get live data
     */
	public function getLiveData() {
	
		$data['cookie'] = $this->setCurlSession(); 	
	
		$curl = new Curl();
		$curl->retry = 24;
		
		$curl->addSession( $this->source.'a'.$this->format, $this->opts );

		$result = $curl->exec();
		$curl->clear();		
		
		$this->delCookie();
		 
		return $result;
	} 	
	

    /**
     * Set curl session
     */
	public function setCurlSession() {
	
		if($this->password != '')
		{
			$curl = new Curl();
			$curl->retry = 2;
			
			$this->cookie = tempnam(sys_get_temp_dir(), 'YL_KOEK_');
			$this->opts[CURLOPT_COOKIEFILE] = $this->cookie;			
			
			$optsSet = array( 
				CURLOPT_RETURNTRANSFER => true, 
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_COOKIEJAR => $this->cookie			
			);			
			
			$curl->addSession( $this->source.'L?w='.$this->password, $optsSet );
	
			$curl->exec();
			$result = $curl->info();		
			$curl->clear();		
			
			return $result;
		}
	} 	
	
	
    /**
     * Check if password protected and delete old cookie
     */
	public function delCookie() {	
		
		if($this->password != '')
		{
			unlink($this->cookie);
		}			
	}
	
	/**
     * Get last hour | 1min data
     */
	public function getLastHour() {
	
		// Check for password and create cookie
		$data['cookie'] = $this->setCurlSession();

		$curl = new Curl();
		$curl->retry = 2;
		
		$curl->addSession( $this->source.'V?h=1'.$this->format, $this->opts );
		$curl->addSession( $this->source.'V?h=2'.$this->format, $this->opts );		
		
		$result = $curl->exec();
		$info = $curl->info();
		$error = $curl->error();
		$curl->clear();	

		// Check for password and delete cookie
		$this->delCookie();	
		
		$part1 = json_decode($result[0], true);
		$part2 = json_decode($result[1], true);

		$values = array_merge($part2['val'], $part1['val']);

		foreach($values as $k => $v){
			if($v == NULL){
				unset($values[$k]);
			}
			elseif($v == '*')
			{
				$values[$k] = '0';
			}
		}
		$val = implode('","', $values);
		
		$data['un'] = $part2['un'];
		$data['tm'] = $part2['tm'];
		$data['dt'] = $part2['dt'];
		$data['val'] = $val;

		$data['info'] = $info;
		$data['error'] = $error;

		return $data;
	}  		 

	
	/**
     * Get last 8 hours | 10min data
     */
	public function getLast24Hours() {
	
		// Check for password and create cookie
		$data['cookie'] = $this->setCurlSession();

		
		$curl = new Curl();
		$curl->retry = 2;
		
		$curl->addSession( $this->source.'V?w=1'.$this->format, $this->opts );
		$curl->addSession( $this->source.'V?w=2'.$this->format, $this->opts );
		$curl->addSession( $this->source.'V?w=3'.$this->format, $this->opts );		

		
		$result = $curl->exec();
		$info = $curl->info();
		$error = $curl->error();
		$curl->clear();	

		
		// Check for password and delete cookie
		$this->delCookie();	
		
		$part1 = json_decode($result[0], true);
		$part2 = json_decode($result[1], true);
		$part3 = json_decode($result[2], true);
				

	/*
	echo "<br><br>";
	print_r ($part1);
	echo "<br><br>";
		
	echo "<br><br>";
	print_r ($part2);
	echo "<br><br>";
	
	echo "<br><br>";
	print_r ($part3);
	echo "<br><br>";
	*/	
	
		$values = array_merge($part3['val'],$part2['val'], $part1['val']);

	/*
	echo "<br><br>";
	print_r ($values);
	echo "<br><br>";
	*/
	
	
		foreach($values as $k => $v){
			if($v == NULL){
				unset($values[$k]);
			}
			elseif($v == '*')
			{
				$values[$k] = '0';
			}
		}
		$val = implode('","', $values);
		
		$data['un'] = $part3['un'];
		$data['tm'] = $part3['tm'];
		$data['dt'] = $part3['dt'];
		$data['val'] = $val;

		
		$data['info'] = $info;
		$data['error'] = $error;

		
		return $data;
	}  		

	
	/**
     * Get last 7 days | 1 hour data
     */
	public function getLast7Days() {
	
		// Check for password and create cookie
		$data['cookie'] = $this->setCurlSession();
		
		$curl = new Curl();
		$curl->retry = 2;
		
		//$curl->addSession( $this->source.'V?d=0', $this->opts );			//REMOVE THIS LINE BECAUSE THIS WILL ADD UNEXISTING DATA FOR TODAY (UNTIL 23:59 HR) 
		$curl->addSession( $this->source.'V?d=1'.$this->format, $this->opts );
		$curl->addSession( $this->source.'V?d=2'.$this->format, $this->opts );
		$curl->addSession( $this->source.'V?d=3'.$this->format, $this->opts );
		$curl->addSession( $this->source.'V?d=4'.$this->format, $this->opts );
		$curl->addSession( $this->source.'V?d=5'.$this->format, $this->opts );
		$curl->addSession( $this->source.'V?d=6'.$this->format, $this->opts );

		
		$result = $curl->exec();
		$info = $curl->info();
		$error = $curl->error();
		$curl->clear();	

		
		// Check for password and delete cookie
		$this->delCookie();	
		
		/*
		$part1 = array();
		$part2 = array();
		$part3 = array();
		$part4 = array();
		$part5 = array();
		$part6 = array();
		$part7 = array();
		*/
		
		//$part1 = json_decode($result[0], true);									//REMOVED THIS LINE BECAUSE THIS WILL ADD UNEXISTING DATA FOR TODAY (UNTIL 23:59 HR) 
		$part1 = json_decode($result[0], true);										//START WITH PART1 HERE
		$part2 = json_decode($result[1], true);
		$part3 = json_decode($result[2], true);
		$part4 = json_decode($result[3], true);
		$part5 = json_decode($result[4], true);
		$part6 = json_decode($result[5], true);


	/*
	echo "<br><br>";
	print_r ($part1);
	echo "<br><br>";

	echo "<br><br>";
	print_r ($part2);
	echo "<br><br>";

	echo "<br><br>";
	print_r ($part3);
	echo "<br><br>";
	*/	
	
		$values = array_merge($part6['val'],$part5['val'],$part4['val'],$part3['val'],$part2['val'],$part1['val']);		// PART7 REMOVED!!

	/*
	echo "<br><br>";
	print_r ($values);
	echo "<br><br>";
	*/
	
	
		foreach($values as $k => $v){
			if($v == NULL){
				unset($values[$k]);
			}
			elseif($v == '*')
			{
				$values[$k] = '0';
			}
		}
		$val = implode('","', $values);
		
		$data['un'] = $part6['un']; //kWh																				// PART7 REMOVED!!
		$data['tm'] = $part6['tm']; //2022-02-02 00:00:00
		$data['dt'] = $part6['dt']; //3600
		$data['val'] = $val;

		
		$data['info'] = $info;
		$data['error'] = $error;

		
		return $data;
	}  		

	
	/**
     * Get last month | 1 day data
     */
	public function getThisMonth($ThisMonth) {
	
		// Check for password and create cookie
		$data['cookie'] = $this->setCurlSession();

		
		$curl = new Curl();
		$curl->retry = 2;
		
		$curl->addSession( $this->source.'V?m='.$ThisMonth.$this->format, $this->opts );

		
		$result = $curl->exec();
		$info = $curl->info();
		$error = $curl->error();
		$curl->clear();	

		
		// Check for password and delete cookie
		$this->delCookie();	
		
		$part1 = json_decode($result, true);
	
	/*
	echo "<br><br>";
	print_r ($part1);
	echo "<br><br>";

	echo "<br><br>";
	print_r ($part2);
	echo "<br><br>";
	*/
	
		$values = array_merge($part1['val']);
	
	/*
	echo "<br><br>";
	print_r ($values);
	echo "<br><br>";
	*/
	
		foreach($values as $k => $v){
			if($v == NULL){
				unset($values[$k]);
			}
			elseif($v == '*')
			{
				$values[$k] = '0';
			}
		}
		$val = implode('", "', $values);
		
		$data['un'] = $part1['un'];
		$data['tm'] = $part1['tm'];
		$data['dt'] = $part1['dt'];
		$data['val'] = $val;

		
		$data['info'] = $info;
		$data['error'] = $error;

		
		return $data;
	}  	
	
	


	
    /**
     * Get specific month
     */
	public function getSpecificMonth($month) {

		// Check for password and create cookie
		$this->setCurlSession();

		$curl = new Curl();
		$curl->retry = 2;
		
		$curl->addSession( $this->source.'V?m='.$month.$this->format, $this->opts );

		$result = $curl->exec();
		$curl->clear();	
		
		// Check for password and delete cookie
		$this->delCookie();	

		$json = json_decode($result, true);
		
		$values = $json['val'];
		foreach($values as $k => $v){
			if($v == NULL){
				unset($values[$k]);
			}
			elseif($v == '*')
			{
				$values[$k] = '0';
			}
		}
		$val = implode('", "', $values);
		
		$data['un'] = $json['un'];
		$data['tm'] = $json['tm'];
		$data['dt'] = $json['dt'];
		$data['val'] = $val;

		return $data;
	} 	
}

?>
