<?php
// This program read the input query and select the corresponding urls. 
// The urls are the 10 highest freqs for the query in AOL.
// Then, it will output a ranking list.
// You can specify a parameter "-html_content content_path". 
// It will check the urls whether they are in the content_path or not
// If the file exists, it will be copied to content_path_output (auto generate)
// If not, it will generate a stderr message to notify the user.
include_once("aol_utility.php");

class aol_itemrank extends aol_utility {
	public $ranking_list;
	private $dbs;
	private $s_querys;
	private $targetTB;
	private $html_path;
	public function __construct($para){
		// if html_path (-html_content) attribute is null,
		// the object will not check whether the URL content exists when it output UID 
		$this->dbs[0] = "cikm2011";
		$this->dbs[1] = "b95119";
		if ( isset($para["file"]) ){
			$this->filename = $para["file"];
		}else{
			fprintf(STDERR,"please specify the input file with option \"-file\"\n");
			exit(-1);
		}
		if ( isset($para["TB"]) ){
			$this->targetTB = $para["TB"];
		}else{
			fprintf(STDERR,"please specify the target table with option \"-TB\"\n");
			exit(-1);
		}
		if ( isset($para["html_content"]) ){
			$this->html_path = $para["html_content"];
		}else{
			$this->html_path = null;
			//exit(-1);
		}
		$this->s_querys = array();
		parent::__construct($para);
		
	}
	
	private function q_u_average_itemrank($s_query, $s_url){
		//$this->switch_db(0);
		$counter = 0;
		$sum = 0;
		for ($i = 1;$i <= 10;$i++){
			$sql = sprintf(
				"select count(*), sum(`ItemRank`)
				from `aol.%d.ct` 
				where `Query` = '%s' and `ClickURL` = '%s'",
				$i, $s_query, $s_url);
			$result = mysql_query($sql) or die($sql."\n".mysql_error());
			
			if ($row = mysql_fetch_row($result)){
				$counter +=$row[0];
				$sum +=$row[1];
			}
		}
		$avg = (double) $sum / (double) $sum;
		return $avg;
	}
	
	public function GetQueryAllItemRank($s_q){
		$s_urls = $this->select_candidate_urls($s_q, 10);
		if ($s_urls == NULL || count($s_urls) <= 1){
			return NULL;
		}
		
		// speed up by extracting "$this->switch_db(0)" from q_u_avarage_itemrank()
		$this->switch_db(0);
		$this->ranking_list[$s_q] = array();
		foreach ($s_urls as $i => $s_u){
			$this->ranking_list[$s_q][$s_u] = $this->q_u_average_itemrank($s_q, $s_u);
		}
		arsort($this->ranking_list[$s_q]);
		return array_keys($this->ranking_list[$s_q]);
	}
	protected function switch_db($num){
		$database_cnn = $this->dbs[$num];
		mysql_select_db($database_cnn);
	}
	
	public function select_candidate_urls($s_query, $limit = -1){
		$this->switch_db(1);
		$sql = sprintf("select * from `%s` where `query` = '%s'", 
			$this->targetTB, $s_query);
		$result = mysql_query($sql) or die(mysql_error()."\nerror query\n".$sql);
		$urls = array();
		while($row = mysql_fetch_row($result)){
			$s_url = $this->convert_safe_str($row[2]);
			$urls[$s_url] = 0;
			for ($i = 3; $i< 27; $i++){
				$urls[$s_url] += intval($row[$i]);
			}
		}
		arsort($urls);
		
		if ($limit == -1 || count($urls) <= $limit){
			return array_keys($urls);
		}else{
			$counter = 0;
			foreach ($urls as $i => $v){
				$new_urls[$i] = $urls[$i];
				//echo $i."\n";
				$counter++;
				if ($counter >= $limit){
					break;
				}
			}
			return array_keys($new_urls);
		}
	}
	public function GetQueryFromFile(){
		$fp = fopen($this->filename, "r");
		if ($fp == null){
			fprintf(STDERR,"%s can't be read\n",$this->filename);
			return NULL;
		}
		while (!feof($fp)){
			$line = fgets($fp);
			$query = $this->cut_last_newline($line);
			if (empty($query)){
				continue;
			}
			$this->s_querys[] = $this->convert_safe_str($query);
		}
		fclose($fp);
		return $this->s_querys;
	}
	protected function CheckHtmlContent($url, $uid){
		if ($this->html_path == null){
			return true;// skip the checking
		}
		system("mkdir ".$this->html_path."_selected");
		$ret = system("ls ".$this->html_path."/".$uid);
		if (empty($ret)){
			//fprintf($this->err_fp, $url."\t".$uid."\tnot found\n");
			fprintf($this->err_fp, $url."\t".$uid."\n");
			return false;
		}else{
			system("cp ".$this->html_path."/".$uid." ".$this->html_path."_selected/");
		}
		return true;
	}
	public static function GetAolItemRank($argc, $argv){
		// sample command
		// php aol_itemrank.php -TB aol_24_clean -file input.txt 
		// -o ItemrankWithUID.txt -err UncatchURL.txt
		$para = ParameterParser($argc, $argv);
		$obj = new aol_itemrank($para);
		$s_querys = $obj->GetQueryFromFile();
		print_r($s_querys);
		foreach ($s_querys as $i => $s_q){
			$ranking = $obj->GetQueryAllItemRank($s_q);
			echo $s_q."\n";
			fprintf($obj->output_fp, "%s\n", $s_q);
			//print_r($ranking);
			$obj->switch_db(1);
			foreach ($ranking as $j => $url){
				$uid = $obj->getUID($url);
				$obj->CheckHtmlContent($url, $uid);
				fprintf($obj->output_fp, "%d\t%s\t%d\n", $j, $url, $uid);
			}
		}
	}
	public function getUID($s_url){
		$sql = sprintf("select `uid` from `uid_clean` where `url` = '%s'", 
			$s_url);
		$result = mysql_query($sql) or die(mysql_error()."\nerror query\n".$sql);
		$num = mysql_num_rows($result);
		if ($num <= 0 ){
			return 0; // not match
		}else if ($row = mysql_fetch_row($result)){
			return intval($row[0]);
		}else{
			return -1; // error
		}
	}
}

aol_itemrank::GetAolItemRank($argc,$argv);
?>