<?php
/* this program inherit the class of rank_entropy.
 * but it ranks the two URLs with same ranking number 
 * when their difference is smaller than 10% of the click of the larger one.
 * 
 *
 * add one more class 
 * I change the condition. It will treat two URLs as different 
 * if the distance is bigger than 5 clicks      
 */
require_once("rank_entropy.php");
class PercentageRankEntropy extends RankEntropy{
	public function __construct($para){
		if (isset($para["topk"])){
			$this->topk = intval($para["topk"]);
		}
		parent::__construct($para);
		$this->newTB = $this->DB.".p.rank";
	}
	public function rank_cross_time($q, $urls){
		// implement notes: there are two part in this function,
		// the first one operate in column, the second one operate in row.		
		
		for ($t = 0;$t < 24;$t++){
			$clicks = array(); // clear
			foreach($urls as $i => $u){
				$clicks[$u] = $this->select_time_click($t, $q, $u);
			}
			arsort($clicks);
			
			$i = 0;
			$click_n = array(); // clear
			$click_u = array(); // clear
			foreach($clicks as $u => $c){
				$click_n[$i] = $c;
				$click_u[$i] = $u;
				$i++;
			}
			
			// the first one should always exists. if not, it will be an error
			$num = 1;
			$rank_value = 1;
			$rank[$t][1][] = $click_u[0];// first url must be assigned to rank 1
			$u_inverted[$t][$click_u[0]] = 1;
			for($i = 1; $i < count($click_n); $i++){
				$num +=1;
				$diff = $click_n[$i - 1] - $click_n[$i];
				if ( ($diff > $click_n[$i - 1] * 0.1) ){
				//if ( ($diff > $click_n[$i - 1] * 0.1) && ($click_n[$i -1] > 10)){ 
					// if difference is bigger than the 10% of the higher click
					// and the click num is bigger than 10
					$rank_value = $num; // $num will always increase in each loop, but the rank_value may not.
				}
				// url assign to rank
				$rank[$t][$rank_value][] = $click_u[$i];// one rank may contain more than one URL.
				// inverted index
				$u_inverted[$t][$click_u[$i]] = $rank_value;
			}
		}
		
		foreach($urls as $i => $u){
			// collect t_rank and insert into DB
			for ($t = 0;$t < 24;$t++){
				$t_rank[$t] = $u_inverted[$t][$u];
			}
			$this->insertRank($q, $u, $t_rank);
		}
		return $u_inverted;
	}
	public static function TopK($argc, $argv){
		$para = ParameterParser($argc, $argv);
		$re = new PercentageRankEntropy($para);
		$querys = $re->FindQuery();
		
		$re->createDBTable(); //init DB table
		foreach ($querys as $i => $q){ 
			$urls = $re->select_candidate_urls($q);
			if (count($urls) <= 1 ){
				continue;
			}
			$u_inverted = $re->rank_cross_time($q, $urls);
			//print_r($u_inverted);
		}
		
		// get all value;
		foreach ($querys as $i => $q){ 
			$entropy[$q] = $re->getEntropy($q);
			/*
			if ($entropy["global_max_value"] < $entropy[$q]["max_value"]){
				$entropy["global_max_url"] = $q."\t".$entropy[$q]["max_u"];
				$entropy["global_max_value"] = $entropy[$q]["max_value"];
			}*/
			
			$max_value[] = $entropy[$q]["max_value"];
			$avg_value[] = $entropy[$q]["average"];
		}
		rsort($max_value);
		rsort($avg_value);
		$num = $re->topk;
		if ( $num <= count($max_value) ){
			// select the lower score in top k
			$l_max_value = $max_value[$num - 1];
			$l_avg_value = $avg_value[$num - 1];
		}else{
			$l_max_value = $max_value[$count($max_value)];
			$l_avg_value = $avg_value[$count($avg_value)];
		}
		
		foreach ($entropy as $q => $e){ 
			if ( $e["max_value"] >= $l_max_value ){
				$output_max[$q] = $e;
			}
			if ( $e["average"] >= $l_avg_value ){
				$output_avg[$q] = $e;
			}
		}
		
		printf("select from max(count = %d, select = %d)\n", count($output_max), $num);
		foreach ($output_max as $q => $e){ 
			echo $q."\t".$e["max_value"]."\n";
		}
		//print_r($output_max);
		printf("\nselect from avg(count = %d, select = %d)\n", count($output_avg), $num);
		foreach ($output_avg as $q => $e){ 
			echo $q."\t".$e["average"]."\n";
		}
		//print_r($output_avg);
	}
} 

class DiffRankEntropy extends RankEntropy{
	public $diff = 5;
	public function __construct($para){
		if (isset($para["topk"])){
			$this->topk = intval($para["topk"]);
		}
		if (isset($para["diff"])){
			$this->diff = intval($para["diff"]);
		}
		parent::__construct($para);
		$this->newTB = $this->DB.".d.rank";
	}
	public function rank_cross_time($q, $urls){
		// implement notes: there are two part in this function,
		// the first one operate in column, the second one operate in row.		
		
		for ($t = 0;$t < 24;$t++){
			$clicks = array(); // clear
			foreach($urls as $i => $u){
				$clicks[$u] = $this->select_time_click($t, $q, $u);
			}
			arsort($clicks);
			
			$i = 0;
			$click_n = array(); // clear
			$click_u = array(); // clear
			foreach($clicks as $u => $c){
				$click_n[$i] = $c;
				$click_u[$i] = $u;
				$i++;
			}
			
			// the first one should always exists. if not, it will be an error
			$num = 1;
			$rank_value = 1;
			$rank[$t][1][] = $click_u[0];// first url must be assigned to rank 1
			$u_inverted[$t][$click_u[0]] = 1;
			for($i = 1; $i < count($click_n); $i++){
				$num +=1;
				$diff = $click_n[$i - 1] - $click_n[$i];
				if ( $diff >= $this->diff ){
					$rank_value = $num; // $num will always increase in each loop, but the rank_value may not.
				}
				// url assign to rank
				$rank[$t][$rank_value][] = $click_u[$i];// one rank may contain more than one URL.
				// inverted index
				$u_inverted[$t][$click_u[$i]] = $rank_value;
			}
		}
		
		foreach($urls as $i => $u){
			// collect t_rank and insert into DB
			for ($t = 0;$t < 24;$t++){
				$t_rank[$t] = $u_inverted[$t][$u];
			}
			$this->insertRank($q, $u, $t_rank);
		}
		return $u_inverted;
	}
	public static function TopK($argc, $argv){
		$para = ParameterParser($argc, $argv);
		$re = new DiffRankEntropy($para);
		$querys = $re->FindQuery();
		
		$re->createDBTable(); //init DB table
		foreach ($querys as $i => $q){ 
			$urls = $re->select_candidate_urls($q);
			if (count($urls) <= 1 ){
				continue;
			}
			$u_inverted = $re->rank_cross_time($q, $urls);
			//print_r($u_inverted);
		}
		
		// get all value;
		foreach ($querys as $i => $q){ 
			$entropy[$q] = $re->getEntropy($q);
			
			$max_value[] = $entropy[$q]["max_value"];
			$avg_value[] = $entropy[$q]["average"];
		}
		rsort($max_value);
		rsort($avg_value);
		$num = $re->topk;
		if ( $num <= count($max_value) ){
			// select the lower score in top k
			$l_max_value = $max_value[$num - 1];
			$l_avg_value = $avg_value[$num - 1];
		}else{
			$l_max_value = $max_value[$count($max_value)];
			$l_avg_value = $avg_value[$count($avg_value)];
		}
				
		foreach ($entropy as $q => $e){ 
			if ( $e["max_value"] >= $l_max_value ){
				$output_max[$q] = $e;
			}
			if ( $e["average"] >= $l_avg_value ){
				$output_avg[$q] = $e;
			}
		}
		
		arsort($output_max);
		printf("select from max(count = %d, select = %d)\n", count($output_max), $num);
		foreach ($output_max as $q => $e){ 
			echo $q."\t".$e["max_value"]."\n";
		}
		//print_r($output_max);
		arsort($output_avg);
		printf("\nselect from avg(count = %d, select = %d)\n", count($output_avg), $num);
		foreach ($output_avg as $q => $e){ 
			echo $q."\t".$e["average"]."\n";
		}
		//print_r($output_avg);
	}
} 
?>