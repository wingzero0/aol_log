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

?>