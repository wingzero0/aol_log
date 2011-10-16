<?php
// This program will re-rank the aol original itemrank list by 
// sense traffic at a specific hour
// The program will read an itemrank file, a sense traffic file and 
// similarity file

// The following is the format of the itemrank file.
//
// query \t rank \t url \n
// 
// the format of sense traffic
// sense_name \t prob \n
// 
// the format of sense similarity
// urls \t sense \t similarity_score \n
 
include_once("aol_utility.php");
class aol_rerank extends aol_utility {
	protected $item_fp = null;
	protected $sim_fp = null;
	protected $traffice_fp = null;
	protected $itemarnk = array();
	protected $merge_rank = array();
	protected $merge_score = array();
	protected $sim = array();
	protected $traffic = array();
	protected $sense_score = array();
	protected $alpha = 0.5;
	public function __construct($para){
		if ( isset($para["itemrank"]) ){
			$this->item_fp = fopen($para["itemrank"], "r");
			if ($this->item_fp == NULL){
				fprintf(STDERR, "%s can't be opened\n", $para["itemrank"]);
				exit(-1);
			}
		}else{
			fprintf(STDERR,"please specify the input file with option \"-itemrank\"\n");
			exit(-1);
		}
		if ( isset($para["sense_traffic"]) ){
			$this->traffic_fp = fopen($para["sense_traffic"], "r");
			if ($this->traffic_fp == NULL){
				fprintf(STDERR, "%s can't be opened\n", $para["sense_traffic"]);
				exit(-1);
			}
		}else{
			fprintf(STDERR,"please specify the input file with option \"-sense_traffic\"\n");
			exit(-1);
		}
		if ( isset($para["similarity"]) ){
			$this->sim_fp = fopen($para["similarity"], "r");
			if ($this->sim_fp == NULL){
				fprintf(STDERR, "%s can't be opened\n", $para["similarity"]);
				exit(-1);
			}
		}else{
			fprintf(STDERR,"please specify the input file with option \"-similarity\"\n");
			exit(-1);
		}
		$this->ReadingInput();
		parent::__construct($para);
		
	}
	private function ReadingInput() {
		while (!feof($this->item_fp)){
			$line = fgets($this->item_fp);
			if (empty($line) || $line == "\n"){
				continue;
			}
			//preg_split($pattern, $subject, $limit = null, $flags = null);
			$list = preg_split("/\t|\n/", $line);
			$q = $list[0];
			//$u = preg_quote($list[2]);
			$u = $list[2];
			$rank = $list[1]; 
			$this->itemrank[$q][$u] = intval($rank);
		}
		fclose($this->item_fp);
		
		while (!feof($this->sim_fp)){
			$line = fgets($this->sim_fp);
			if (empty($line) || $line == "\n"){
				continue;
			}
			$list = preg_split("/\t|\n/", $line);
			//$list = split("\t", $line);
			//$u = preg_quote($list[0]);//
			$u = $list[0];
			$sense = $list[1];
			$score = $list[2]; 
			$this->sim[$u][$sense] = doubleval($score);
		}
		//print_r($this->sim);
		fclose($this->sim_fp);
		
		while (!feof($this->traffic_fp)){
			$line = fgets($this->traffic_fp);
			if (empty($line) || $line == "\n"){
				continue;
			}
			$list = preg_split("/\t|\n/", $line);
			//$list = split("\t", $line);
			$sense = $list[0];
			$score = $list[1]; 
			$this->traffic[$sense] = doubleval($score);
		}
		//print_r($this->traffic);
		fclose($this->traffic_fp);
	}
	
	private function query_newrank($q){
		$this->sense_score[$q] = array();
		$this->sense_rank[$q] = array();
		
		// compute new rank
		foreach ($this->itemrank[$q] as $u => $r){
			$sum = 0.0;
			if (!isset($this->sim[$u])){
				//skip the url the we haven't crawl the ram content
				continue;
			}			
			foreach ($this->traffic as $sense => $v){
				//echo "$u-$sense-\n";
				$sum += $v * $this->sim[$u][$sense];
			}
			$this->sense_score[$q][$u] = $sum;
			
		}
		arsort($this->sense_score[$q]);
		$rank = 1;
		foreach ($this->sense_score[$q] as $u => $v){
			$this->sense_rank[$q][$u] = $rank;
			$rank++;
		}
	}
	private function query_merge($q){
		$this->merge_score[$q] = array();
		$this->merge_rank[$q] = array();
		foreach ($this->itemrank[$q] as $u => $r){
			if (!isset($this->sim[$u])){
				//skip the url the we haven't crawl the ram content
				continue;
			}
			$r_s = $this->sense_rank[$q][$u];
			$score = $this->alpha * 1.0/ (double) $r_s + 
				(1- $this->alpha) * 1.0 / (double) $r;
			$this->merge_score[$q][$u] = $score;
		}
		arsort($this->merge_score[$q]);
		$rank = 1;
		foreach ($this->merge_score[$q] as $u => $v){
			$this->merge_rank[$q][$u] = $rank;
			$rank++;
		}
	}
	public function query_rerank($q){
		$this->query_newrank($q);
		$this->query_merge($q);
		//print_r($this->merge_rank[$q]);
	}
	public static function AolRerank($argc, $argv){
		// sample command
		// php aol_rerank.php -itemrank rerank_input.txt -sense_traffic traffic.txt -similarity sim.txt
		$para = ParameterParser($argc, $argv);
		$obj = new aol_rerank($para);
		$querys = array_keys($obj->itemrank);
		foreach ($querys as $q){
			$obj->query_rerank($q);
		}
		foreach ($obj->merge_rank as $q => $ranking){
			foreach ($ranking as $u => $rank){
				fprintf($obj->output_fp, "%s\t%d\t%s\n", $q, $rank, $u);
			}
		}
		//print_r($obj->merge_rank);
	}
}
aol_rerank::AolRerank($argc,$argv);
?>