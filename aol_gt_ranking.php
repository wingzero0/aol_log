<?php
// this file duel with the ranking issue
// it reads the query as it's input and then generate corresponding 
// ranking. 

include_once("aol_utility.php");

class ranking extends aol_utility {
	public $query_file = null;
	public $targetTB = null;
	public function __construct($para){
		$database_cnn = "b95119";
		mysql_select_db($database_cnn);
		if ( isset($para["query_file"]) ){
			$this->query_file = $para["query_file"];
		}else{
			fprintf(STDERR,"please specify the input file with option \"-query_file\"\n");
			exit(-1);
		}
		if ( isset($para["TB"]) ){
			$this->targetTB = $para["TB"];
		}else{
			fprintf(STDERR,"please specify the target table with option \"-TB\"\n");
			exit(-1);
		}
		parent::__construct($para);
	}
	public function select_candidate_urls($s_query, $limit = -1){
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
	public function mysql_query_error_output($sql){	
		$result = mysql_query($sql) or die($sql."\n".mysql_error());
		return $result;
	}

	public function GetQueryFromFile(){
		$fp = fopen($this->query_file, "r");
		if ($fp == null){
			fprintf(STDERR,"%s can't be read\n",$this->query_file);
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
}

class aol_gt_ranking extends ranking{
	public $click_rank = array(); // 3dim array
	public $output_t = array();
	public function __construct($para){
		if ( isset($para["o"]) ){
			$this->output_prefix = $para["o"];
			for ($t = 0;$t < 24;$t++){
				$this->click_rank[$t] = array();
				$this->output_t[$t] = fopen($this->output_prefix.".".$t, "w");
				if ($this->output_t[$t] == NULL){
					fprintf(STDERR,$this->output_prefix.".".$t." can't be open\n");
					exit(-1);
				}
			}
		}else{
			fprintf(STDERR,"please specify the output prefix with option \"-o\"\n");
			exit(-1);
		}
		parent::__construct($para);
	}
	public function __destruct(){
		for ($t = 0;$t < 24;$t++){
			fclose($this->output_t[$t]);
		}
		parent::__destruct();
	}
	public function HourGTRanking($s_q, $s_urls, $t){
		$click[$s_q] = array();
		foreach ($s_urls as $s_u){
			$sql = sprintf(
				"select `%d` from `%s` 
				where `query` = '%s' and `url` = '%s'",
				$t, $this->targetTB,$s_q, $s_u);
			$result = $this->mysql_query_error_output($sql);
			if ($row = mysql_fetch_row($result)){
				$click[$s_q][$s_u] = intval($row[0]);
			}else{
				fprintf(STDERR, "query:%s\turl:%s\tnot found in aol_clean_24\n",
					$s_q,$s_u
				);
			}			
		}
		//here should consider the tie ranking (平手該怎辦？)
		arsort($click[$s_q]);
		// sort by the click, but only return the url ranking
		return array_keys($click[$s_q]);
	}
	public function DayGTRanking($s_q){
		$s_urls = $this->select_candidate_urls($s_q);
		for ($t = 0;$t < 24;$t++){
			$this->click_rank[$t][$s_q] = array();
			$this->click_rank[$t][$s_q] = $this->HourGTRanking($s_q, $s_urls, $t);
		}
	}
	public static function GetGTRanking($argc,$argv){
		// sample command
		// php aol_gt_ranking.php -TB aol_24_clean -query_file query.txt 
		// -o GTRanking
		$para = ParameterParser($argc, $argv);
		$obj = new aol_gt_ranking($para);
		$s_querys = $obj->GetQueryFromFile();
		//print_r($s_querys);
		foreach ($s_querys as $i => $s_q){
			$obj->DayGTRanking($s_q);
		}
		for ($t = 0;$t < 24;$t++){
			foreach ($obj->click_rank[$t] as $s_q => $ranking){
				foreach ($ranking as $j => $url){
					fprintf($obj->output_t[$t], "%s\t%d\t%s\n", $s_q,$j+1, $url);
				}
			}
		}
	}	
}

aol_gt_ranking::GetGTRanking($argc,$argv);
?>