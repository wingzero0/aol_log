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
$para = ParameterParser($argc, $argv);
$obj = new aol_tf_to_rerank($para);
$obj->Reduction();