<?php
/* this php will contain some statistic class, which for 
 * AOL 24 hour timestamp log
 * the base Statistics class will count the hourly query-url click count under
 * certain boundary
 * the NavieCluster will find out the similar urls-click behavior under a 
 * certain query.
 * the Entropy will find out if the urls-click behavior is larger than 
 * the threshold
 */
include_once("connection.php");
mysql_select_db($database_cnn,$b95119_cnn);

class Statistics{
	public $lb = -1;
	public $up = -1;
	public $DB = NULL;
	public function __construct($para){
		if (isset($para["TB"])){
			$this->DB = $para["TB"];
		}else{
			echo "para['TB'] missing, the object failed to be created\n";
			return -1;
		}
		if (isset($para["low"])){
			$this->lb = intval($para["low"]);
		}
		if (isset($para["up"])){
			$this->up = intval($para["up"]);
		}
	}
	function CountQueryURLPair(){
		$sum = 0.0;
		for ($i = 0; $i < 24;$i++){
			$sum += (double) $this->CountQueryURLHourlyPair($i);
		}
		$average = $sum / 24.0;
		echo $sum."\n";
		echo $average."\n";
		return $sum;
	}
	function CountQueryURLHourlyPair($t){
		// when this->lb == -1 and this->up == -1, it will get the all records.
		$query = sprintf("
			select `query`, `url` from `%s` where `%d` > %d
			", $this->DB, $t ,$this->lb);
		if ($this->up > 0){
			$query = sprintf("%s and `%d` < %d", $query, $t, $this->up);
		}
		$result = mysql_query($query) or die(mysql_error()."\nerror query\n".$query);
		$num = mysql_num_rows($result);
		return $num;
	}
}

class NavieCluster extends Statistics{
	public $q;
	public $Threshold = 3;
	public $Cluster;
	public $group = array(); // from group index to query-url 
	public $group_invert = array(); // from query-url to group index
	function __construct($para){
		parent::__construct($para);
		if (isset($para["Threshold"])){
			$this->Threshold = intval($para["Threshold"]);
		}
	}
	private function FindQuery(){
		$query = sprintf("select distinct `query` from `%s` ", $this->DB);
		$result = mysql_query($query) or die(mysql_error()."\nerror query\n".$query);		
		$this->q = array();
		while ($row = mysql_fetch_row($result)){
			$this->q[] = preg_replace("/\'/", "\\\'", $row[0]);
		}
		return $this->q;
	}
	public function ClusterUrlPairs(){
		$this->FindQuery();
		$this->Cluster = array();
		for ($i= 0;$i< count($this->q);$i++){
			$query = sprintf("select * from `%s` where `query` = '%s'", 
				$this->DB, $this->q[$i]);	
			$result = mysql_query($query) or die(mysql_error()."\nerror query\n".$query);
			$tmp = $this->_FindCluster($result);
			if (!empty($tmp)){
				$this->Cluster[$this->q[$i]] = $tmp;
			}
		}
		//print_r($this->Cluster);
		return $this->Cluster;
	}
	private function _FindCluster($result){
		$rows = array();
		while ($row = mysql_fetch_row($result)){
			$rows[] = $row;
		}
		$GroupSet = array();
		foreach ($rows as $i => $unGroupURL){
			$CreateFlag = true;
			foreach($GroupSet as $j => $Group){
				foreach($Group as $k => $GroupURL){
					if ($this->isSimilar($unGroupURL, $GroupURL)){
						$GroupSet[$j][] = $unGroupURL;
						$CreateFlag = false;
						break;
					}
				}
				if ($CreateFlag == false){
					break;
				}
			}
			if ($CreateFlag == true){
				$newGroupIndex = count($GroupSet);
				$GroupSet[$newGroupIndex] = array();
				$GroupSet[$newGroupIndex][0] = $unGroupURL;
			}
		}
		foreach($GroupSet as $j => $Group){
			foreach($Group as $k => $GroupURL){
				$output[$j][$k] = $GroupURL[2];
			}
		}
		return $output;
	}
	private function isSimilar($row1,$row2){
		$counter = 0;
		for ($i = 3; $i < 27; $i++){
			if ( abs($row1[$i] - $row2[$i]) < $this->Threshold ){
				$counter++;
			}
		}
		if ($counter >=24) {
			return true;
		}else{
			return false;
		}
	}
	public function displaySimilar(){
		foreach ($this->Cluster as $q => $cluster){
			foreach ($cluster as $urls){
				if (count($urls) > 1){
					echo $q."\n";
					foreach ($urls as $u){
						echo "\t".$u."\n";
					}
				}
			}
		}
	}
}

class Entropy extends Statistics{
	private $query_weight = array();
	private $url_weight = array();
	private $query_url_weight = array();
	private $safe_q = null;
	private $unsafe_q = null;
	public function __construct($para){
		parent::__construct($para);
		if (!isset($para["low"])){
			$this->lb = 1;
		}
	}
	private function FindQuery(){
		$query = sprintf("select distinct `query` from `%s` ", $this->DB);
		$result = mysql_query($query) or die(mysql_error()."\nerror query\n".$query);		
		$this->safe_q = array();
		$this->unsafe_q = array();

		while ($row = mysql_fetch_row($result)){
			$this->safe_q[] = preg_replace("/\'/", "\\\'", $row[0]);
			$this->unsafe_q[] = $row[0];
		}
		return $this->safe_q;
	}
	public function AverageInHour($t){
		// it calculate the average entropy and the max in time t
		$result = $this->_AverageInHour($t);
		$ret["average"] = $result["average"];
		$ret["max_set"] = $result["max_set"];
		print_r($ret);
		return $ret;
	}
	public function _AverageInHour($t){
		// it calculate the average entropy and the max in time t
		if ($this->safe_q == NULL){
			$this->FindQuery();
		}
		$sum = 0.0;
		$weight_sum = 0.0;
		$max_set = null;
		$max = 0.0;
		foreach($this->safe_q as $i => $v){
			$Q_URLs[$v] = $this->SpecificQ_URLsInHour($v, $t);
			$sum += $Q_URLs[$v]["entropy"] * $Q_URLs[$v]["click"];
			$weight_sum += $Q_URLs[$v]["click"];
			if ($max < $Q_URLs[$v]["entropy"]) {
				$max_set = array(); // clear
				$max_set[$v] = $Q_URLs[$v];
				$max = $Q_URLs[$v]["entropy"];
			}else if ($max == $Q_URLs[$v]["entropy"]){
				$max_set[$v] = $Q_URLs[$v]; // add more
			}
		}
		$ret["Q_URLs"] = $Q_URLs;
		$ret["average"] = $sum / $weight_sum;
		$ret["max_set"] = $max_set;
		$ret["sum"] = $sum;
		$ret["weight_sum"] = $weight_sum;
		//echo $ret["average"];
		//print_r($ret);
		return $ret;
	}
	public function DistributionInHour($t){
		// it calculate the distribution of entropy and the max in time t
		// it will replace the function _AverageInHour in the future
		if ($this->safe_q == NULL){
			$this->FindQuery();
		}
		$sum = 0.0;
		$weight_sum = 0.0;
		$max_set = null;
		$max = 0.0;
		$distribution = array();
		foreach($this->safe_q as $i => $v){
			$Q_URLs[$v] = $this->SpecificQ_URLsInHour($v, $t);
			$sum += $Q_URLs[$v]["entropy"] * $Q_URLs[$v]["click"];
			$weight_sum += $Q_URLs[$v]["click"];
			if ($max < $Q_URLs[$v]["entropy"]) {
				$max_set = array(); // clear
				$max_set[$v] = $Q_URLs[$v];
				$max = $Q_URLs[$v]["entropy"];
			}else if ($max == $Q_URLs[$v]["entropy"]){
				$max_set[$v] = $Q_URLs[$v]; // add more
			}
			$string_entropy = sprintf("%.2lf", $Q_URLs[$v]["entropy"]);
			if (!isset($distribution[$string_entropy])){
				$distribution[$string_entropy]["click"] = 0.0;
			}
			$distribution[$string_entropy]["click"] += $Q_URLs[$v]["click"];
			$distribution[$string_entropy]["Q_URLs"][$v] = $Q_URLs[$v];
		}
		foreach($distribution as $i => $v){
			$distribution[$i]["prob"] = $distribution[$i]["click"] / $weight_sum;
		}
		ksort($distribution);
		$ret["distribution"] = $distribution;
		//print_r($distribution);
		$ret["Q_URLs"] = $Q_URLs;
		$ret["average"] = $sum / $weight_sum;
		$ret["max_set"] = $max_set;
		$ret["sum"] = $sum;
		$ret["weight_sum"] = $weight_sum;
		return $ret;
	}
	public function AverageInDay(){
		// it calculate the average entropy and the max in a day
		if ($this->safe_q == NULL){
			$this->FindQuery();
		}
		$sum = 0.0;
		$weight_sum = 0.0;
		$max_set = null;
		$max = 0.0;
		for ($t = 0; $t < 24;$t++){
			$result = $this->_AverageInHour($t);
			//$max_set[$t] = $result["max_set"];
			$sum += $result["sum"];
			$weight_sum += $result["weight_sum"];
			echo $t."\n";
			print_r($result["max_set"]);
			foreach ($result["max_set"] as $i => $v){
				if ($max < $result["max_set"][$i]["entropy"]) {
					$max_set = array(); // clear
					$max_set[$t] = $result["max_set"];
					$max = $result["max_set"][$i]["entropy"];
				}else if ($max == $result["max_set"][$i]["entropy"]){
					$max_set[$t] = $result["max_set"]; // add more
				}
				break;
			}
		}
		//print_r($Q_URLs);
		$ret["average"] = $sum / $weight_sum;
		$ret["max_set"] = $max_set;
		//echo $ret["average"];
		print_r($ret);
		return $ret;

	}
	public function DistributionInDay(){
		// it calculate the average entropy and the max in a day
		if ($this->safe_q == NULL){
			$this->FindQuery();
		}
		$sum = 0.0;
		$weight_sum = 0.0;
		$max_set = null;
		$max = 0.0;
		$distribution = array();
		for ($t = 0; $t < 24;$t++){
			$result = $this->DistributionInHour($t);
			$sum += $result["sum"];
			$weight_sum += $result["weight_sum"];
			echo $t."\n";
			//print_r($result["max_set"]);
			foreach ($result["max_set"] as $i => $v){
				if ($max < $result["max_set"][$i]["entropy"]) {
					$max_set = array(); // clear
					$max_set[$t] = $result["max_set"];
					$max = $result["max_set"][$i]["entropy"];
				}else if ($max == $result["max_set"][$i]["entropy"]){
					$max_set[$t] = $result["max_set"]; // add more
				}
				break;
			}
			foreach ($result["distribution"] as $string_entropy => $dist_value){
				if (!isset($distribution[$string_entropy])){
					$distribution[$string_entropy]["click"] = 0.0;
				}
				$distribution[$string_entropy]["click"] += $dist_value["click"];
				$distribution[$string_entropy]["Q_URLs"][$t] = $dist_value["Q_URLs"];
			}
		}
		foreach($distribution as $i => $v){
			$distribution[$i]["prob"] = $distribution[$i]["click"] / $weight_sum;
		}
		ksort($distribution);
		$ret["distribution"] = $distribution;
		$ret["average"] = $sum / $weight_sum;
		$ret["max_set"] = $max_set;
		//echo $ret["average"];
		//print_r($ret);
		return $ret;

	}	
	public function SpecificQ_URLsInHour($q, $t){
		// measure the entropy of Query q => URls in time t
		// it will return the entropy of the $q and the total click caused by $q
		// in bound
		$query = sprintf("
			select `url`, `%d` from `%s` where `query` = '%s' and `%d` >= %d
			", $t, $this->DB, $q , $t , $this->lb );
		$result = mysql_query($query) or die(mysql_error()."\nerror query\n".$query);
		/*
		$num = mysql_num_rows($result);
		if ($num == 0){
			//echo "no result for ".$q." in ".$t." under lower bound =".$this->lb."\n";
			return 0.0;
		}*/

		$sum = 0.0;
		while($row = mysql_fetch_row($result)){
			$sum += doubleval($row[1]);
			$url[$row[0]] = doubleval($row[1]);
		}

		// outsize bound will consider as other.
		$query = sprintf("
			select sum(`%d`) from `%s` where `query` = '%s' and `%d` < %d
			", $t, $this->DB, $q , $t , $this->lb );
		$result = mysql_query($query) or die(mysql_error()."\nerror query\n".$query);
		$counter = 0;
		while($row = mysql_fetch_row($result)){
			$sum += doubleval($row[0]);
			$url["LOWREBOUND"] = doubleval($row[0]);
			if ($counter >0){
				echo "error: counter should be 0\n";
			}
		}


		$entropy = 0.0;
		foreach ($url as $i => $v){
			if ($v > 0.0){
				$p = $v / $sum;
				$entropy -= $p * log($p);
			}
		}

		$ret["entropy"] = $entropy;
		$ret["click"] = $sum;

		return $ret;
	}
	public function NonSpecificQ_URLsInHour($t){	
		// measure the entropy of URls being click in time t
		// it will return the entropy and the total click in time t
		
		// in bound
		$query = sprintf("
			select `url`, `%d` from `%s` where `%d` >= %d
			", $t, $this->DB, $t , $this->lb );
		$result = mysql_query($query) or die(mysql_error()."\nerror query\n".$query);
		
		$sum = 0.0;
		while($row = mysql_fetch_row($result)){
			$sum += doubleval($row[1]);
			$url[$row[0]] = doubleval($row[1]);
		}

		// outsize bound will consider as other.
		$query = sprintf("
			select sum(`%d`) from `%s` where `%d` < %d
			", $t, $this->DB, $t , $this->lb );
		$result = mysql_query($query) or die(mysql_error()."\nerror query\n".$query);
		$counter = 0;
		while($row = mysql_fetch_row($result)){
			$sum += doubleval($row[0]);
			$url["LOWREBOUND"] = doubleval($row[0]);
			if ($counter >0){
				echo "error: counter should be 0\n";
			}
		}


		$entropy = 0.0;
		foreach ($url as $i => $v){
			if ($v > 0.0){
				$p = $v / $sum;
				$entropy -= $p * log($p);
			}
		}

		$ret["entropy"] = $entropy;
		$ret["click"] = $sum;

		return $ret;
	}
	public function NonSpecificQ_DistributionInDay(){
		// it calculate the distribution of entropy and 
		// find the max value accross the time t in day
		$sum = 0.0;
		$weight_sum = 0.0;
		$max_set = null;
		$max = 0.0;
		$distribution = array();

		for ($t = 0;$t < 24;$t++){
			$T_Result[$t] = $this->NonSpecificQ_URLsInHour($t);

			$sum += $T_Result[$t]["entropy"] * $T_Result[$t]["click"]; // for calculating mean
			$weight_sum += $T_Result[$t]["click"];
			if ($max < $T_Result[$t]["entropy"]) {
				$max_set = array(); // clear
				$max_set[$t] = $T_Result[$t];
				$max = $T_Result[$t]["entropy"];
			}else if ($max == $T_Result[$t]["entropy"]){
				$max_set[$t] = $T_Result[$t]; // add more
			}
			// for calculation distribution
			$string_entropy = sprintf("%.2lf", $T_Result[$t]["entropy"]);
			if (!isset($distribution[$string_entropy])){
				$distribution[$string_entropy]["click"] = 0.0;
			}
			$distribution[$string_entropy]["click"] += $T_Result[$t]["click"];
			$distribution[$string_entropy]["T_Result"][$t] = $T_Result[$t];
		}
		foreach($distribution as $i => $v){
			$distribution[$i]["prob"] = $distribution[$i]["click"] / $weight_sum;
		}
		ksort($distribution);
		$ret["distribution"] = $distribution;
		//print_r($distribution);
		$ret["T_Result"] = $T_Result;
		$ret["average"] = $sum / $weight_sum;
		$ret["max_set"] = $max_set;
		$ret["sum"] = $sum;
		$ret["weight_sum"] = $weight_sum;
		return $ret;
	}
}

function ParameterParser($argc, $argv){
	$para = array();
	for ($i = 0; $i< $argc - 1; $i++){
		$ret = preg_match("/^-(.*)/", $argv[$i], $match);
		if ($ret == 1){
			$para[$match[1]] = $argv[$i+1];
			$i = $i +1;
		}
	}
	return $para;
}
?>
