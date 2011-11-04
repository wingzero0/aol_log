<?php
// this program want to know whether if a url is dominate in one sense of 
//Trend micro result 

// sample usage
// php aol_sense_distribution.php -o output.txt -input_dir trend_result_path  
require_once("aol_utility.php");

class aol_sense_distribution extends aol_utility {
	protected $dh; //dir source
	protected $dirName; // dir name
	public $dis; // distribution
	public $sum;
	public $entropy;
	public function __construct($para){ 
		if ( isset($para["input_dir"]) && is_dir($para["input_dir"]) ){
			$this->dh = opendir($para["input_dir"]);
			$this->dirName = $para["input_dir"]; 
			if ($this->dh == FALSE){
				fprintf(STDERR,"%s can't be open\n", $para["input_dir"]);
				exit(-1);
			}
		}else{
			fprintf(STDERR,"please specify the input file with option \"-input\"\n");
			exit(-1);
		}
		parent::__construct($para);
	}
	public function ReadDir(){
		while (($file = readdir($this->dh)) !== false) {
			$ret = preg_match("/result_/", $file, $matches);
			if ($ret > 0){
				//echo $this->dirName."/".$file."\n";
				$this->AddRecord($this->dirName."/".$file);
			}
		}
		ksort($this->dis);
		//print_r($this->dis);
	}
	public function FindAllMajority(){
		$this->ReadDir();
		foreach ($this->dis as $senseNum => $URLs){
			$entropy = $this->Entropy($senseNum);
		}
		//asort($this->entropy);
		foreach($this->dis as $senseNum => $URLs){
			foreach ($URLs as $URL => $v){
				$per = (double) $v / (double) $this->sum[$senseNum];
				if ($per >= 0.3){
					//$this->Output($senseNum, $URL, $v);
					fprintf($this->output_fp, "%d\t%s\t%d\t%d\n", $senseNum, $URL, $v, $this->sum[$senseNum]);
					//break;
				}else{
					fprintf($this->output_fp, "%d\t%d\tentropy:%lf\n", $senseNum, $this->sum[$senseNum], $this->entropy[$senseNum]);
					break;
				}
			} 
		}
		print_r($this->dis[33]);
		print_r($this->entropy[33]."\n");
		print_r($this->dis[52]);
		print_r($this->entropy[52]."\n");
	}
	public function Output($senseNum, $URL, $v) {
		//$counter = 0;
		//foreach ($this->dis[$senseNum] as $URL => $v){
			//if ($counter > $numOutput){
				//break;
			//}
			fprintf($this->output_fp, "%d\t%s\t%d\t%d\n", $senseNum, $URL, $v, $this->sum[$senseNum]);
			//$counter++;
		//}
	}
	public function Entropy($senseNum){
		// calulate entropy
		arsort($this->dis[$senseNum]);
		$sum = 0;
		foreach($this->dis[$senseNum] as $URL => $v){
			$sum += $v;
		}
		$this->sum[$senseNum] = $sum;
		$this->entropy[$senseNum] = 0.0;
		foreach($this->dis[$senseNum] as $URL => $v){
			if ($v > 0){
				$p = (double) $v / (double) $sum;
				$this->entropy[$senseNum] -= $p * log10($p);
			}
		}
		return $this->entropy[$senseNum];
	}
	public function AddRecord($filename){
		$fp = fopen($filename, "r");
		if ($fp == NULL){
			fprintf(STDERR, "%s can't be opened\n", $filename);
			return -1;
		}
		while (!feof($fp)){
			$tmp = fgets($fp);
			$line = $this->cut_last_newline($tmp);
			if (empty($line)){
				continue;
			}
			$list = $this->split_tab($line);
			$ret = $this->ParseSite($list[1]);
			if ($ret == NULL){
				continue;
			}
			if ( !isset($this->dis[intval($list[2])][$ret]) ){
				$this->dis[intval($list[2])][$ret] = 0;
			}
			$this->dis[intval($list[2])][$ret] += 1;
		}
		fclose($fp);
	}
	public function ParseSite($URL){
		$pattern = "/(.*?)\//";
		$ret = preg_match($pattern, $URL, $matches);
		if ($ret > 0 ){
			//echo $matches[0]."\n";
			return $matches[1];
		}else{
			fprintf($this->err_fp, "%s not match\n", $URL);
			return NULL;
		}
	}
	public function __destruct(){
		closedir($this->dh);
		parent::__destruct();
	}
}
$para = ParameterParser($argc, $argv);
$obj = new aol_sense_distribution($para);
$obj->FindAllMajority();
?>