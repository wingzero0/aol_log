<?php
include("statistics.php");
//mysql_select_db($database_cnn,$b95119_cnn);

class URLIntersection extends Statistics{
	// this class want to check the intersection between AOL and TrendMicro
	// it will mansure two things
	// first one, it will check how many domain names both appear in two logs.
	// second one, it will check how many URLs appear in tow logs.
	private $aDB;
	private $tDB;
	public $aol_host;
	public $aol_host_map;
	public $aol_urls;
	public $tm_host;
	public $tm_host_map;
	public function __construct($para){
		if (isset($para["AOL_TB"])){
			$this->aDB = $para["AOL_TB"];
		}else{
			echo "para['AOL_TB'] missing, the object failed to be created\n";
			return -1;
		}
		if (isset($para["TREND_MICRO_HOST_TB"])){
			$this->tDB1 = $para["TREND_MICRO_HOST_TB"];
		}else{
			echo "para['TREND_MICRO_HOST_TB'] missing, the object failed to be created\n";
			return -1;
		}
		if (isset($para["TREND_MICRO_TTT_TB"])){
			$this->tDB2 = $para["TREND_MICRO_TTT_TB"];
		}else{
			echo "para['TREND_MICRO_TTT_TB'] missing, the object failed to be created\n";
			return -1;
		}
	}
	public function get_aol_urls(){
		mysql_select_db("b95119");
		$sql = sprintf("select `url` from `%s` group by `url` order by `url` asc",
			$this->aDB
		);
		$result = mysql_query($sql) or die("error query:".$sql."\n".mysql_error());
		$urls = array();
		while( $row = mysql_fetch_row($result) ) {
			$urls[$row[0]] = 1; // set
		}
		$this->aol_urls = array_keys($urls);
		return $this->aol_urls;
	}
	public function get_aol_host(){
		$this->get_aol_urls();
		
		$pattern = "@^(?:http(?:s?)://)?([^/]+)@i";
		//$pattern = "@^(?:http://)?([^/]+)@i";
		$this->aol_host_map = array(); // clear and reset
		foreach ($this->aol_urls as $url){
			$ret = preg_match($pattern, $url, $matches);
			if ($ret < 0){
				echo "unmatch:".$url."\n";
			}
			$this->aol_host_map[$matches[1]] = 1; 
		}
		$this->aol_host = array_keys($this->aol_host_map);
		return $this->aol_host;
	}
	public function get_tm_host(){
		mysql_select_db("TrendMirco") or die(mysql_error());
		$sql = sprintf("select distinct `HOST` from `%s` order by `HOST` asc",
			$this->tDB1
		);
		$result = mysql_query($sql) or die("error query:".$sql."\n".mysql_error());
		$this->tm_host_map = array(); // clear and reset
		while( $row = mysql_fetch_row($result) ) {
			$this->tm_host_map[$row[0]] = 1; // set
		}
		$this->tm_host = array_keys($this->tm_host_map);
		return $this->tm_host;
	}
	public function intersection(){
		$counter = 0;
		foreach ($this->aol_host as $host) {
			if ( isset($this->tm_host_map[$host]) ){
				$counter++;
			}
		}
		echo $counter."\n";
		return $counter;
	}
	
	public function intersection_type_statistic(){
		mysql_select_db("TrendMirco") or die(mysql_error());
		$RA = array();
		$counter = 0;
		foreach ($this->aol_host as $host) {
			if ( isset($this->tm_host_map[$host]) ){
				$sql = sprintf(
					"select `RA` from `%s` where `HID` in (
						select `HID` from `%s` where `HOST` = '%s'
					)
					limit 1",
					$this->tDB2, $this->tDB1,$host
				);
				$result = mysql_query($sql) or die("error query:".$sql."\n".mysql_error());
				if ($row = mysql_fetch_row($result)){
					if ( !isset($RA[$row[0]]) ){
						$RA[$row[0]] = 0;
					}
					$RA[$row[0]] += 1;
					$Host_RA[$host] = $row[0];
				}else{
					echo "no result\n";
					die($host);
				}
				$counter++;
			}
		}
		echo $counter."\n";
		$ret[0] = $RA;
		$ret[1] = $Host_RA;
		return $ret;
	}
	
	public static function intersection_count($argc, $argv){
		$para = ParameterParser($argc, $argv);
		$inter = new URLIntersection($para);
		$aol_host = $inter->get_aol_host();
		//print_r($aol_host);
		echo "aol host:".count($aol_host)."\n";
		$tm_host = $inter->get_tm_host();
		//print_r($tm_host);
		echo "tm host:".count($tm_host)."\n";
		$inter->intersection();
	}
	public static function test($argc, $argv){
		$para = ParameterParser($argc, $argv);
		$inter = new URLIntersection($para);
		$aol_host = $inter->get_aol_host();
		$tm_host = $inter->get_tm_host();
		$ret = $inter->intersection_type_statistic();
		print_r($ret);
	}
	 
}

URLIntersection::test($argc, $argv);
?>
