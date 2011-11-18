<?php
// Class TrendMicroStatistics want to define some common function 
// for statistics on `TrendMicro` database. 

// Class QueryStatistics want to get the distribution of the query

include_once("trendmicro_utility.php");
mysql_select_db($database_cnn,$b95119_cnn);

class TrendMicroStatistics extends TrendMicro_utility {
	public $targetTB;
	public function __construct($para){
		parent::__construct($para);
	}
	public function run(){
		
	}
}


// sample usage:
// php xxx.php -TB US_recrod -o tmp.txt
class QueryStatistics extends TrendMicroStatistics{
	public $QueryRecords = array();
	public $QueryTerms = array();
	public function __construct($para){
		if ( isset($para["TB"]) ){
			$this->targetTB = $para["TB"];
		}else{
			fprintf(STDERR,"please specify the target table with option \"-TB\"\n");
			exit(-1);
		}
		parent::__construct($para);
	}
	public function GetQueryRecords(){
		$sql = sprintf(
			"select `QID`, count(`QID`)
			from `%s` 
			where `QID` != 0 group by `QID`",
			$this->targetTB);
		
		$result = mysql_query($sql) or die($sql."\t". mysql_error());
		while($row = mysql_fetch_row($result)){
			$this->QueryRecords[$row[0]] = $row[1]; 
		}
		//print_r($this->QueryRecords);
		arsort($this->QueryRecords);
		return $this->QueryRecords;
	}
	public function GetQueryTerms(){
		$num = count($this->QueryRecords);
		$num /= 4;
		$counter = 0;
		foreach ($this->QueryRecords as $i => $times){
			if ($counter >= $num){
				break;
			}
			
			$sql = sprintf(
				"select `QID`, `Query`
				from `Query` 
				where `QID` = %d",
				$i);
		
			$result = mysql_query($sql) or die($sql."\t". mysql_error());
			if ($row = mysql_fetch_row($result)){
				$this->QueryTerms[$i] = $row[1];
				fprintf($this->output_fp, "%d\t%s\t%d\n", $i, $row[1], $times);
			}else{
				fprintf($this->err_fp, "the term of qid = $i not found\n");
			}
			$counter++;			
		}
		return $this->QueryTerms;
	}
	public function run(){
		$this->GetQueryRecords();
		$this->GetQueryTerms();
	}
}