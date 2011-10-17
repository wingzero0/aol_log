<?php
// this file duel with the ranking issue
// it reads the query as it's input and then generate corresponding 
// ranking. 

include_once("aol_ranking.php");

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
		$rank[$s_q] = array();
		foreach ($s_urls as $s_u){
			$sql = sprintf(
				"select `%d` from `%s.rank` 
				where `query` = '%s' and `url` = '%s'",
				$t, $this->targetTB,$s_q, $s_u);
			$result = $this->mysql_query_error_output($sql);
			if ($row = mysql_fetch_row($result)){
				$rank[$s_q][$s_u] = intval($row[0]);
			}else{
				fprintf(STDERR, "query:%s\turl:%s\tnot found in aol_clean_24\n",
					$s_q,$s_u
				);
			}			
		}
		
		asort($rank[$s_q]); 
		// the value from the targetTB.rank is already ranked by click
		// done by rank entropy. 
		// we also need the score.
		return $rank[$s_q];
	}
	public function DayGTRanking($s_q){
		$s_urls = $this->select_candidate_urls($s_q, 10);
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
				foreach ($ranking as $url => $r){
					fprintf($obj->output_t[$t], "%s\t%s\t-%d\n", $s_q,$url, $r);
				}
			}
		}
	}	
}

aol_gt_ranking::GetGTRanking($argc,$argv);
?>