<?php
// this program want rewrite the format of the input file
// the input file is generated of one of aol program, 
// and it will be rewritten with the format of other output.

// sample usage
// php aol_format_reduction.php -input input.txt -o output.txt 
include_once("aol_utility.php");

class aol_format_reduction extends aol_utility {
	public $infp;
	public function __construct($para){ 
		if ( isset($para["input"]) ){
			$this->infp = fopen($para["input"], "r");
			if ($this->infp == NULL){
				fprintf(STDERR, "%s can't be opened\n", $para["input"]);
				exit(-1);
			}
		}else{
			fprintf(STDERR,"please specify the input file with option \"-input\"\n");
			exit(-1);
		}
		parent::__construct($para);
	}
	public function readinput() {
	}
	public function Reduction() {

	}
	public function __destruct(){
		fclose($this->infp);
	}
}

class aol_tf_to_rerank extends aol_format_reduction {
	public function readinput(){
		$line = fgets($this->infp); // first line is the column summery
		$list = preg_split("/\t|\n/", $line);
		$counter = 0;
		//print_r($list);
		for ($i = 1;$i < count($list);$i++){// first one is uid
			if (empty($list[$i])){
				continue;
			}
			$sense[$counter] = $list[$i];
			$counter++;
		}

		// connect db to get the corresponding URL of the uid
		//print_r($sense);
		$database_cnn = "b95119";
		mysql_select_db($database_cnn);
		$sql = sprintf("select * from `uid_clean`");
		$result = mysql_query($sql) or die($sql."\n".mysql_error());
		while($row = mysql_fetch_row($result)){
			$url[$row[0]] = $row[1];
		}
		mysql_free_result($result);

		while (!feof($this->infp)){
			$line = fgets($this->infp);
			if (empty($line)){
				continue;
			}
			$list = preg_split("/\t|\n/", $line);
			$uid = intval($list[0]);
			$counter = 0;
			for ($i = 1;$i < count($list);$i++){// first one is uid
				if (empty($list[$i])){
					continue;
				}
				$sim[$counter] = doubleval($list[$i]);
				$counter++;
			}

			//fprintf(STDERR, $uid."\t".$url[$uid]."\t");

			for ($i = 0;$i < count($sense);$i++){
				//fscanf($this->infp, "%d",$sim);

				//echo "$sim\t";
				fprintf($this->output_fp, "%s\t%s\t%lf\n",$url[$uid], $sense[$i],$sim[$i]);
			}
			//echo "\n";
			//fprintf($this->output_fp, "\n");
		}
	}
	public function Reduction() {
		$this->readinput();
	}
}

class traffic_csv_to_rerank extends aol_format_reduction {
	public $traffic = array();
	protected $sum ;
	public function readinput(){
		$this->sum = 0;
		while (!feof($this->infp)){
			$line = fgets($this->infp); // first line is the column summery
			if (empty($line)){
				continue;
			}
			$list = preg_split("/ |\t|\n/", $line);
			$pattern = "/\"class(.*)\"/";

			$sense = preg_replace($pattern, "\${1}_parsed", $list[0]);

			$this->traffic[$sense] = doubleval($list[1]);
			$this->sum += doubleval($list[1]);
		}
		print_r($this->traffic);
	}
	protected function calc_prob_and_output() {
		foreach ($this->traffic as $s => $v){
			$this->traffic[$s] /= $this->sum;
			if ($this->traffic[$s] != 0.0){
				fprintf($this->output_fp, "%s\t%lf\n", $s, $this->traffic[$s]);
			}
		}
		//print_r($this->traffic);
	}
	protected function writeoutput(){

	}
	public function Reduction() {
		$this->readinput();
		$this->calc_prob_and_output();
	}
}
class ranking_to_csv_display extends aol_format_reduction {
	public $gt_num;
	public $it_fp;
	public $gt_fp = array();
	public $ds_fp;
	public $gt_rank = array();
	public $gt_score = array();
	public $it_rank = array();
	public $sense_rank = array();
	public $sense_check = array();
	public $ra_fp;
	public $sense_dict;

	public function __construct($para){
		if (isset($para["o"])){
			$this->output_fp = fopen($para["o"], "w");
			if ($this->output_fp == NULL){
				fprintf(STDERR, "%s can't be opened\n", $para["o"]);
				$this->output_fp = STDOUT;
			}
		}else{
			$this->output_fp = STDOUT;
		}

		if ( !isset($para["gt_num"])){
			fprintf(STDERR, "specify the -gt_num\n");
			exit(-1);
		}
		$this->gt_num = intval($para["gt_num"]);

		for ($i = 0;$i < $this->gt_num; $i++){
			if ( isset($para["gt".$i]) ){
				$this->gt_fp[$i] = fopen($para["gt".$i], "r");
				if ($this->gt_fp[$i] == NULL){
					fprintf(STDERR, "%s can't be opened\n", $para["gt".$i]);
					exit(-1);
				}
			}else{
				fprintf(STDERR,"please specify the input file with option \"-gt$i\"\n");
				exit(-1);
			}
		}
		if ( isset($para["itemrank"]) ){
			$this->it_fp = fopen($para["itemrank"], "r");
			if ($this->it_fp == NULL){
				fprintf(STDERR, "%s can't be opened\n", $para["itemrank"]);
				exit(-1);
			}
		}else{
			fprintf(STDERR,"please specify the input file with option \"-itemrank\"\n");
			exit(-1);
		}
		if ( isset($para["dumpscore"]) ){
			$this->ds_fp = fopen($para["dumpscore"], "r");
			if ($this->ds_fp == NULL){
				fprintf(STDERR, "%s can't be opened\n", $para["dumpscore"]);
				exit(-1);
			}
		}else{
			fprintf(STDERR,"please specify the input file with option \"-dumpscore\"\n");
			exit(-1);
		}

		if ( isset($para["RA"]) ){
			$this->ra_fp = fopen($para["RA"], "r");
			if ($this->ra_fp == NULL){
				fprintf(STDERR, "%s can't be opened\n", $para["RA"]);
				exit(-1);
			}
		}else{
			fprintf(STDERR,"please specify the input file with option \"-RA\"\n");
			exit(-1);
		}
	}
	public function readinput() {
		// read the input with a trick;
		// the q,u pairs in ds_fp will be the subset of gt_fp(=it_fp)
		// so we can drop the pair which does not appear in ds_fp

		$fp = $this->ra_fp;
		while (!feof($fp)){
			$line = fgets($fp);
			$line = $this->cut_last_newline($line);
			if (empty($line)){
				continue;
			}
			$list = $this->split_tab($line);
			$sense_num = $list[0];
			$sense_name = $list[1];
			$this->sense_dict[$sense_num] = $sense_name;
		}

		// read ds_fp first
		$fp = $this->ds_fp;
		while (!feof($fp)){
			$line = fgets($fp);
			$line = $this->cut_last_newline($line);
			if (empty($line)){
				continue;
			}
			$list = $this->split_tab($line);
			$q = $list[0];
			$url = $list[1];
			$sense_str = $this->parse_sense($list[4]);
			$this->sense_check[$q][$url] = $sense_str;
			$this->sense_rank[$q][] = $url; // set the order.
		}

		// read gt
		for ($i = 0;$i< count($this->gt_fp);$i++){
			$fp = $this->gt_fp[$i];
			while (!feof($fp)){
				$line = fgets($fp);
				$line = $this->cut_last_newline($line);
				if (empty($line)){
					continue;
				}
				$list = $this->split_tab($line);
				$q = $list[0];
				$url = $list[1];
				$rank = intval($list[2]);
				if ( isset($this->sense_check[$q][$url]) ){
					$this->gt_rank[$q][$i][] = $url;
					$this->gt_score[$q][$i][$url] = $rank;
				}
			}
		}

		// read itemrank
		$fp = $this->it_fp;
		while (!feof($fp)){
			$line = fgets($fp);
			$line = $this->cut_last_newline($line);
			if (empty($line)){
				continue;
			}
			$list = $this->split_tab($line);
			$q = $list[0];
			$url = $list[1]; // ignore the rank;
			if ( isset($this->sense_check[$q][$url]) ){
				$this->it_rank[$q][] = $url;
			}
		}


	}
	protected function writeoutput(){
		//print_r($this->sense_rank);
		//print_r($this->gt_rank);
		//print_r($this->it_rank);
		fprintf($this->output_fp, "query\t");//column name
		for ($i = 0;$i< count($this->gt_fp); $i++){
			fprintf($this->output_fp, "gt%d\t", $i);//column name
		}
		fprintf($this->output_fp, "itemrank\t");//column name
		fprintf($this->output_fp, "our rank\t");//column name
		fprintf($this->output_fp, "sense\n");//column name
		foreach ($this->sense_rank as $q => $ranking ){
			foreach ($ranking as $rank => $url){
				fprintf($this->output_fp, "%s\t", $q);//query
				for ($i = 0;$i< count($this->gt_fp); $i++){
					fprintf($this->output_fp, "%s %d\t", 
						$this->gt_rank[$q][$i][$rank], $this->gt_score[$q][$i][$this->gt_rank[$q][$i][$rank]]);// gt
				}
				fprintf($this->output_fp, "%s\t", $this->it_rank[$q][$rank]); // itemrank
				fprintf($this->output_fp, "%s\t", $this->sense_rank[$q][$rank]); // sense rank
				fprintf($this->output_fp, "%s\n", $this->sense_check[$q][$url]); // sense
			}
			fprintf($this->output_fp, "\n"); // newline
		}
	}
	public function Reduction() {
		//sample usage
		//php aol_format_reduction.php -gt_num 3 -gt0 ground_truth/gt_ranking.18 
		//-gt1 ground_truth/gt_ranking.19 -gt2 ground_truth/gt_ranking.20
		//-itemrank data_txt/Itemrank.txt 
		//-dumpscore dumpWithSort/dumpSortWithTopSense.0.5.txt
		//-RA data_txt/RA.txt
		$this->readinput();
		$this->writeoutput();
	}
	private function parse_sense($sense_str){
		// none complete;
		$new_str = $sense_str;
		$pattern = "/\((.{1,3})_parsed\)/"; 
		$ret = preg_match_all($pattern, $sense_str, $matches);
		//print_r($matches);
		//print_r($this->sense_dict);
		for($i = 0;$i< $ret;$i++){
			$replacement = "(".$this->sense_dict[$matches[1][$i]].")";
			$new_str = preg_replace($pattern, $replacement, $new_str, $limit = 1);
		}
		//echo $new_str."\n";
		return $new_str;
	}
	public function __destruct(){
		for ($i = 0;$i< count($this->gt_fp);$i++){
			fclose($this->gt_fp[$i]);
		}
		fclose($this->it_fp);
		fclose($this->ds_fp);
		fclose($this->ra_fp);
		if ($this->output_fp != STDOUT){
			fclose($this->output_fp);
		}
	}
}
$para = ParameterParser($argc, $argv);
//$obj = new aol_tf_to_rerank($para);
$obj = new traffic_csv_to_rerank($para);
//$obj = new ranking_to_csv_display($para);
$obj->Reduction();
?>
