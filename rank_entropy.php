<?php
/* this program what to calcuate the query 's rank entropy
 * we define the rank entropy of a (q,u) pair as follow:
 * 	if url rank at position i for q with t hours, 
 * 	then the prob of it be ranked in i is t/24.
 * 	so we can calculate each ranked prob.
 *    finally, the rank entropy is the sum of sum i prob() 
 *
 *
 * if the url ranking in the query will change over day, it entropy will be large
 */
include("statistics.php");
mysql_select_db($database_cnn,$b95119_cnn);

class RankEntropy extends Statistics{
	public $topk = 10;
	public function __construct($para){
		if (isset($para["topk"])){
			$this->topk = intval($para["topk"]);
		}
		parent::__construct($para);
	}
	public function select_candidate_urls($q){
		$sql = sprintf("select * from `%s` where `query` = '%s'", 
			$this->DB, $q);
		$result = mysql_query($sql) or die(mysql_error()."\nerror query\n".$sql);
		$urls = array();
		while($row = mysql_fetch_row($result)){
			//echo $row[2]."\n";
			$urls[$row[2]] = 0;
			for ($i = 3; $i< 27; $i++){
				$urls[$row[2]] += intval($row[$i]);
			}
		}
		arsort($urls);
		//print_r($urls);
		if (count($urls) <= 10){
			return array_keys($urls);
		}else{
			$counter = 0;
			foreach ($urls as $i => $v){
				$new_urls[$i] = $urls[$i];
				//echo $i."\n";
				$counter++;
				if ($counter >= 10){
					break;
				}
			}
			return array_keys($new_urls);
		}
	}
	
	public function rank_cross_time($q, $urls){
		// implement notes: there are two part in this function,
		// the first one operate in column, the second one operate in row.		
		
		//$this->createDBTable();
		for ($t = 0;$t < 24;$t++){
			$clicks = array(); // clear
			foreach($urls as $i => $u){
				$clicks[$u] = $this->select_time_click($t, $q, $u);
			}
			arsort($clicks);
			
			// I need an associate index and a numeric index.
			// I don't know how to due with the array index in this situation
			// so I copy the array myself to make a corresponding numeric index
			$i = 0;
			$click_n = array(); // clear
			$click_u = array(); // clear
			foreach($clicks as $u => $c){
				$click_n[$i] = $c;
				$click_u[$i] = $u;
				$i++;
			}
			
			// the first one should always exists. if not, it will be an error
			$last_click = $click_n[0];
			$num = 1;
			$rank_value = 1;
			$rank[$t][1][] = $click_u[0];// first url must be assigned to rank 1
			$u_inverted[$t][$click_u[0]] = 1;
			for($i = 1; $i < count($click_n); $i++){
				$num +=1;
				if ($last_click > $click_n[$i]){
					$rank_value = $num;
					$last_click = $click_n[$i];
				}
				// url assign to rank
				$rank[$t][$rank_value][] = $click_u[$i];
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
	
	private function insertRank($query, $url, $t_rank) {
		$sql = sprintf("
			insert into `%s.rank` 
			(
				`query`, `url`, 
				`0`,
				`1`,
				`2`,
				`3`,
				`4`,
				`5`,
				`6`,
				`7`,
				`8`,
				`9`,
				`10`,
				`11`,
				`12`,
				`13`,
				`14`,
				`15`,
				`16`,
				`17`,
				`18`,
				`19`,
				`20`,
				`21`,
				`22`,
				`23`
			)values(
				'%s', '%s', 
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d	
			)", $this->DB,
			$query, $url, 
			$t_rank[0],
			$t_rank[1],
			$t_rank[2],
			$t_rank[3],
			$t_rank[4],
			$t_rank[5],
			$t_rank[6],
			$t_rank[7],
			$t_rank[8],
			$t_rank[9],
			$t_rank[10],
			$t_rank[11],
			$t_rank[12],
			$t_rank[13],
			$t_rank[14],
			$t_rank[15],
			$t_rank[16],
			$t_rank[17],
			$t_rank[18],
			$t_rank[19],
			$t_rank[20],
			$t_rank[21],
			$t_rank[22],
			$t_rank[23]
		);
		$result = mysql_query($sql) or die(mysql_error()."\nerror query\n".$sql);
		return;
	}
	
	public function CreateDBTable() {
		$sql = sprintf("
			CREATE  TABLE IF NOT EXISTS `%s.rank` (
				`id` int( 11  )  NOT  NULL  AUTO_INCREMENT ,
				`query` varchar( 128  )  COLLATE utf8_unicode_ci NOT  NULL ,
				`url` varchar( 255  )  COLLATE utf8_unicode_ci NOT  NULL ,
				`0` int( 11  )  NOT  NULL ,
				`1` int( 11  )  NOT  NULL ,
				`2` int( 11  )  NOT  NULL ,
				`3` int( 11  )  NOT  NULL ,
				`4` int( 11  )  NOT  NULL ,
				`5` int( 11  )  NOT  NULL ,
				`6` int( 11  )  NOT  NULL ,
				`7` int( 11  )  NOT  NULL ,
				`8` int( 11  )  NOT  NULL ,
				`9` int( 11  )  NOT  NULL ,
				`10` int( 11  )  NOT  NULL ,
				`11` int( 11  )  NOT  NULL ,
				`12` int( 11  )  NOT  NULL ,
				`13` int( 11  )  NOT  NULL ,
				`14` int( 11  )  NOT  NULL ,
				`15` int( 11  )  NOT  NULL ,
				`16` int( 11  )  NOT  NULL ,
				`17` int( 11  )  NOT  NULL ,
				`18` int( 11  )  NOT  NULL ,
				`19` int( 11  )  NOT  NULL ,
				`20` int( 11  )  NOT  NULL ,
				`21` int( 11  )  NOT  NULL ,
				`22` int( 11  )  NOT  NULL ,
				`23` int( 11  )  NOT  NULL ,
				PRIMARY  KEY (  `id`  ) ,
				KEY  `Query` (  `query`  ) ,
				KEY  `Url` (  `url`  )  ) ENGINE  =  MyISAM  DEFAULT CHARSET  = utf8 COLLATE  = utf8_unicode_ci;
		", $this->DB);
		$result = mysql_query($sql) or die(mysql_error()."\nerror query\n".$sql);
		//echo $sql;
		$sql = sprintf("TRUNCATE TABLE `%s.rank`",$this->DB);
		$result = mysql_query($sql) or die(mysql_error()."\nerror query\n".$sql);
		
		return;
	}	
	private function select_time_click($t,$q,$u){
		$sql = sprintf("
			select `%d` from `%s` 
			where `query` = '%s' and `url` = '%s'", 
			$t, $this->DB, $q, $u
		);
		$result = mysql_query($sql) or die(mysql_error()."\nerror query\n".$sql);
		if($row = mysql_fetch_row($result)){
			return intval($row[0]);
		}
		// it won't reach here.
		return -1;
	}
	
	public function getEntropy($q){
		$sql = sprintf("select * from `%s.rank` where `query` = '%s'", 
			$this->DB, $q);
		$result = mysql_query($sql) or die(mysql_error()."\nerror query\n".$sql);
		$num = mysql_num_rows($result);
		$urls = array();
		$entropy["average"] = 0.0;
		$max = 0.0;
		$entropy["max_u"] = NULL;
		while($row = mysql_fetch_row($result)){
			$box = array(); // clear 
			for ($i = 3; $i< 27; $i++){
				if ( !isset($box["rank".$row[$i]]) ){
					$box["rank".$row[$i]]  = 0;
				}
				$box["rank".$row[$i]] +=1; 
			}
			$entropy[$row[2]] = 0.0;
			foreach ($box as $r => $v){
				$p[$r] = ((double) $v )/  24.0;
				$entropy[$row[2]] -= $p[$r] * log($p[$r]);
			}
			$entropy["average"] += $entropy[$row[2]];
			if ($max < $entropy[$row[2]] ){
				$entropy["max_u"] = $row[2];
				$max = $entropy[$row[2]];
			}
		}
		if ($num == 0 ){
			return NULL;
		}else{
			$entropy["average"] /= ((double) $num);
			$entropy["max_value"] = $max;
			return $entropy;
		}
	}
	public static function TopK($argc, $argv){
		$para = ParameterParser($argc, $argv);
		$re = new RankEntropy($para);
		$querys = $re->FindQuery();
		
		$re->createDBTable(); //init DB table
		foreach ($querys as $i => $q){ 
			$urls = $re->select_candidate_urls($q);
			//echo $q."\t";
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
	public static function test($argc, $argv){
		$para = ParameterParser($argc, $argv);
		$re = new RankEntropy($para);
		$querys = $re->FindQuery();
		foreach ($querys as $i => $q){ 
			$urls = $re->select_candidate_urls($q);
			echo $q."\t";
			//print_r($urls);
			$u_inverted = $re->rank_cross_time($q, $urls);
			//print_r($u_inverted);
		}
		/*
		$entropy["global_max_value"] = 0.0;
		$entropy["global_max_url"] = NULL;
		foreach ($querys as $i => $q){ 
			$entropy[$q] = $re->getEntropy($q);
			if ($entropy["global_max_value"] < $entropy[$q]["max_value"]){
				$entropy["global_max_url"] = $q."\t".$entropy[$q]["max_u"];
				$entropy["global_max_value"] = $entropy[$q]["max_value"];
			}
		}
		print_r($entropy);*/
	} 
}

RankEntropy::TopK($argc, $argv);
?>
