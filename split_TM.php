<?php
/* this program will cp the records of some specify region 
 * from ttt table of trendmicro log
 */  
include_once("kit_lib.php");
$database_cnn = "TrendMirco";
mysql_select_db($database_cnn,$b95119_cnn);

class split_TM {
	public $c = NULL;
	public $original_table = "ttt";
	public $new_table = "tmp"; 
	private $TM_url_path = NULL;
	private $save_file = NULL;
	public function __construct($para){
		if (isset($para["user_country"])){
			$this->c = $para["user_country"];
		}else{
			echo "-user_country missing, the object failed to be created\n";
			return -1;
		}
		if (isset($para["tm_url_path"])){
			$this->TM_url_path = $para["tm_url_path"];
		}else{
			echo "-tm_url_path missing, the object failed to be created\n";
			return -1;
		}
		if (isset($para["new_table"])){
			$this->new_table = $para["new_table"];
		}
		if (isset($para["o"])){
			$this->save_file = $para["o"];
		}
	}
	public function cp_specify_region(){
		$sql = sprintf("
			CREATE TABLE IF NOT EXISTS  `%s` (
				`UID` INT( 11 ) NOT NULL ,
				`URL` BIGINT( 20 ) NOT NULL ,
				`C` VARCHAR( 5 ) DEFAULT NULL ,
				`DC` VARCHAR( 5 ) DEFAULT NULL ,
				`HID` INT( 11 ) NOT NULL ,
				`RA` VARCHAR( 10 ) DEFAULT NULL ,
				`QID` INT( 11 ) NOT NULL ,
				`TIME` INT( 11 ) NOT NULL ,
				PRIMARY KEY (  `UID` ,  `URL` ,  `TIME` ) ,
				KEY  `QID` (  `QID` ) ,
				KEY  `HID` (  `HID` ) ,
				KEY  `C` (  `C` ) ,
				KEY  `DC` (  `DC` ) ,
				KEY  `RA` (  `RA` )
			) ENGINE = MYISAM DEFAULT CHARSET = latin1;
		", $this->new_table);
		mysql_query($sql) or die(mysql_error());
		$sql = sprintf(
			"insert into `%s` (`UID`, `URL`, `C`, `DC`, `HID`, `RA`, `QID`, `TIME`)
			select * from `%s` where `C` = '%s'",
			$this->new_table, $this->original_table ,$this->c
		);
		mysql_query($sql) or die(mysql_error());
	}
	public function dump_new_table_timestamp(){
		$sql = sprintf(
			"select `TIME` from `%s`",
			$this->new_table
		);
		$result = mysql_query($sql) or die(mysql_error());
		for ($i = 0;$i< 24;$i++){
			$flow[$i] = 0;
		}
		while($row = mysql_fetch_row($result)){
			$str = gmdate("H", intval($row[0]));
			$flow[intval($str)] +=1 ;
		}
		foreach($flow as $i => $v){
			echo $i."\t".$v."\n";
		}
	}
	
	public function get_real_url($id) {
		$lv1 = $id / 1000000000;
		$path1 = sprintf("%02ld",$lv1);
		$remaindar = $id % 1000000000;
		$lv2 = $remaindar / 10000000;
		$path2 = sprintf("%02ld",$lv2);
		$remaindar %= 10000000;
		$lv3 = $remaindar / 10000;
		$path3 = sprintf("%03ld",$lv3);
		$remaindar %= 10000;
		$lv4 = $remaindar;
		$file_name = $this->TM_url_path.$path1."/".$path2."/".$path3;
		//echo $file_name."\n";
		$fp = fopen($file_name, "r");
		if ($fp == NULL){
			fprintf(STDERR, "%s can't be open\n", $file_name);
			return NULL;
		}
		$real_url = NULL;
		$counter = 0;
		while (!feof($fp) && $counter < $lv4){
			fgets($fp); // drop the line
			$counter++;
		}
		if (feof($fp)){
			fprintf(STDERR, "$lv4 can't be reach\n");
			fclose($fp);
			return NULL;
		}
		$real_url = fgets($fp);
		if ($real_url == NULL){
			fprintf(STDERR, "url not found (id = %d)\n", $id);
			fclose($fp);
			return NULL;
		}
		fclose($fp);
		$real_url = preg_replace("/\n/", NULL, $real_url);
		return $real_url;
	}
	
	public function dump_url_and_type(){
		$fp = NULL;
		if ($this->save_file == NULL){
			$fp = STDOUT;
		}else{
			$fp = fopen($this->save_file, "w");
			if ($fp == NULL){
				fprintf(STDERR,"%s can't be open\n",$this->save_file);
				return -1;
			}
		}
		$sql = sprintf(
			"select `URL`, `RA`, `TIME` from `%s` limit 9000000",
			//limit 0, 1000",
			$this->new_table
		);
		$result = mysql_query($sql) or die(mysql_error());
		while ($row = mysql_fetch_row($result)){
			$RA = $row[1];
			$uid = $row[0];
			$hour = gmdate("H", intval($row[2]));
			$url = $this->get_real_url(intval($row[0]));
			fprintf($fp, "%s\t%s\t%s\t%s\n", $uid,$url,$RA,$hour); 
		}
		fclose($fp);
	}
	public static function split_country($para){
		$s = new split_TM($para);
		//$s->cp_specify_region();
		//$s->dump_new_table_timestamp();
		$s->dump_url_and_type();
		//echo $s->get_real_url(8001477091);
	}
	
}

$para = ParameterParser($argc, $argv);
split_TM::split_country($para);

?>
